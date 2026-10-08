<?php

declare(strict_types=1);

use App\Models\CentralOutbox;
use App\Models\User;
use App\Services\Central\OutboxDoCentral;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function centralConfiguradoParaAOutbox(): void
{
    app()->detectEnvironment(fn () => 'local');
    config([
        'services.central.base_url' => 'http://central.test',
        'services.central.project_token' => 'token-de-teste',
    ]);
}

test('o relato de backup chega na hora, com a chave de idempotência, e sai da outbox', function () {
    centralConfiguradoParaAOutbox();
    Http::fake(['central.test/*' => Http::response(['ok' => true], 201)]);

    $this->artisan('backup:reportar', ['--status' => 'falha', '--mensagem' => 'backup:run falhou no satélite'])
        ->expectsOutputToContain('Backup relatado ao Central (falha)')
        ->assertSuccessful();

    Http::assertSent(fn (Request $r) => $r->url() === 'http://central.test/api/backups/report'
        && $r->hasHeader('Authorization', 'Bearer token-de-teste')
        && Str::isUuid($r->header('Idempotency-Key')[0])
        && $r['status'] === 'falha'
        && filled($r['ocorreu_em']));

    expect(CentralOutbox::count())->toBe(0);
});

test('com o Central fora do ar o relato fica guardado e o comando do backup não falha', function () {
    centralConfiguradoParaAOutbox();
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    $this->artisan('backup:reportar', ['--status' => 'sucesso'])
        ->expectsOutputToContain('ficou na outbox')
        ->assertSuccessful();

    $linha = CentralOutbox::sole();

    expect($linha->tipo)->toBe(OutboxDoCentral::BACKUP_RELATADO)
        ->and($linha->tentativas)->toBe(1)
        ->and($linha->ultimo_erro)->toContain('Connection refused')
        ->and($linha->proxima_tentativa_em->isFuture())->toBeTrue();
});

test('quando o Central volta, a reentrega usa a MESMA chave e a hora em que o backup rodou', function () {
    centralConfiguradoParaAOutbox();
    Http::fake(['central.test/*' => Http::sequence()->push('', 503)->push(['ok' => true], 201)]);

    $this->travelTo('2026-10-05 03:00:00');
    $this->artisan('backup:reportar', ['--status' => 'sucesso']);
    $linha = CentralOutbox::sole();

    $this->artisan('central:entregar-outbox');
    Http::assertSentCount(1);

    $this->travelTo('2026-10-05 03:02:00');
    $this->artisan('central:entregar-outbox')->expectsOutputToContain('1 entregue(s)');

    $chaves = Http::recorded()->map(fn ($par) => $par[0]->header('Idempotency-Key')[0])->unique();

    expect($chaves->all())->toBe([$linha->id])
        ->and(Http::recorded()->last()[0]['ocorreu_em'])->toStartWith('2026-10-05T03:00:00')
        ->and(CentralOutbox::count())->toBe(0);
});

test('422 do Central descarta em vez de insistir: tentar de novo não conserta payload errado', function () {
    centralConfiguradoParaAOutbox();
    Http::fake(['central.test/*' => Http::response(['errors' => ['status' => ['inválido']]], 422)]);

    $this->artisan('backup:reportar', ['--status' => 'sucesso']);

    $linha = CentralOutbox::sole();
    expect($linha->descartado_em)->not->toBeNull()
        ->and($linha->ultimo_erro)->toContain('422');

    $this->travel(2)->hours();
    $this->artisan('central:entregar-outbox');
    Http::assertSentCount(1);
});

test('token recusado (401) não descarta: é conserto do outro lado, e o relato espera', function () {
    centralConfiguradoParaAOutbox();
    Http::fake(['central.test/*' => Http::response('', 401)]);

    $this->artisan('backup:reportar', ['--status' => 'sucesso']);

    expect(CentralOutbox::sole()->descartado_em)->toBeNull();
});

test('a espera cresce a cada falha: 1, 5, 15 e depois 60 minutos', function () {
    centralConfiguradoParaAOutbox();
    Http::fake(['central.test/*' => Http::response('', 500)]);
    $this->travelTo('2026-10-05 03:00:00');

    $outbox = app(OutboxDoCentral::class);
    $linha = $outbox->registrar(OutboxDoCentral::BACKUP_RELATADO, ['status' => 'sucesso']);

    $esperas = [];
    foreach (range(1, 5) as $_) {
        $outbox->entregarPendentes();
        $linha->refresh();
        $esperas[] = (int) now()->diffInMinutes($linha->proxima_tentativa_em);
        $this->travelTo($linha->proxima_tentativa_em);
    }

    expect($esperas)->toBe([1, 5, 15, 60, 60]);
});

test('relato que passou de 72 horas sem entrega é descartado, não reentregue', function () {
    centralConfiguradoParaAOutbox();
    Http::fake(['central.test/*' => Http::response('', 500)]);

    $outbox = app(OutboxDoCentral::class);
    $outbox->registrar(OutboxDoCentral::BACKUP_RELATADO, ['status' => 'sucesso']);
    $outbox->entregarPendentes();

    $this->travel(73)->hours();
    $r = $outbox->entregarPendentes();

    expect($r['descartadas'])->toBe(1)
        ->and(CentralOutbox::sole()->ultimo_erro)->toContain('Venceu sem entrega');
    Http::assertSentCount(1);
});

test('duas execuções ao mesmo tempo não entregam a mesma linha duas vezes', function () {
    centralConfiguradoParaAOutbox();
    Http::fake(['central.test/*' => Http::response(['ok' => true], 201)]);

    $outbox = app(OutboxDoCentral::class);
    $linha = $outbox->registrar(OutboxDoCentral::BACKUP_RELATADO, ['status' => 'sucesso']);

    $reservar = new ReflectionMethod($outbox, 'reservar');
    expect($reservar->invoke($outbox, $linha))->toBeTrue()
        ->and($reservar->invoke($outbox, $linha))->toBeFalse();

    Http::assertNothingSent();
});

test('descartado sai da tabela depois de 30 dias', function () {
    centralConfiguradoParaAOutbox();
    Http::fake(['central.test/*' => Http::response('', 422)]);

    $this->artisan('backup:reportar', ['--status' => 'sucesso']);

    $this->travel(29)->days();
    $this->artisan('central:entregar-outbox');
    expect(CentralOutbox::count())->toBe(1);

    $this->travel(2)->days();
    $this->artisan('central:entregar-outbox');
    expect(CentralOutbox::count())->toBe(0);
});

test('sem integração configurada nada é gravado nem enviado', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['services.central.base_url' => null, 'services.central.project_token' => null]);
    Http::fake();

    $this->artisan('backup:reportar', ['--status' => 'sucesso'])
        ->expectsOutputToContain('integração com o Central desligada')
        ->assertSuccessful();

    expect(CentralOutbox::count())->toBe(0);
    Http::assertNothingSent();
});

test('em ambiente de teste a outbox não grava nem envia, mesmo com o Central configurado', function () {
    config(['services.central.base_url' => 'http://central.test', 'services.central.project_token' => 'token-de-teste']);
    Http::fake();

    $this->artisan('backup:reportar', ['--status' => 'sucesso'])->assertSuccessful();

    expect(CentralOutbox::count())->toBe(0);
    Http::assertNothingSent();
});

test('tipo que o Central não reconhece por chave não entra na outbox', function () {
    centralConfiguradoParaAOutbox();

    app(OutboxDoCentral::class)->registrar('presenca.pingada', []);
})->throws(InvalidArgumentException::class);

function quemEntra(): User
{
    return User::create([
        'name' => 'Ana', 'email' => 'ana@exemplo.test', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
}

test('o login vai ao Central pela outbox, com a hora em que aconteceu e a chave', function () {
    centralConfiguradoParaAOutbox();
    Http::fake(['central.test/*' => Http::response(['status' => 'ok'])]);
    $user = quemEntra();

    $this->travelTo('2026-10-05 09:30:00');
    event(new Login('web', $user, false));

    Http::assertSent(fn (Request $r) => $r->url() === 'http://central.test/api/access-logs/login'
        && Str::isUuid($r->header('Idempotency-Key')[0])
        && $r['user_identifier'] === 'ana@exemplo.test'
        && $r['user_name'] === 'Ana'
        && str_starts_with($r['occurred_at'], '2026-10-05T09:30:00'));

    expect(CentralOutbox::count())->toBe(0);
});

test('com o Central fora do ar o login acontece e fica guardado para depois', function () {
    centralConfiguradoParaAOutbox();
    Http::fake(fn () => throw new ConnectionException('Connection refused'));
    $user = quemEntra();

    event(new Login('web', $user, false));

    expect(CentralOutbox::sole()->tipo)->toBe(OutboxDoCentral::LOGIN_REPORTADO);
});

test('outbox quebrada não derruba o login de ninguém', function () {
    centralConfiguradoParaAOutbox();
    Http::fake();
    $user = quemEntra();
    $this->mock(OutboxDoCentral::class)->shouldReceive('registrar')->andThrow(new RuntimeException('tabela sumiu'));

    event(new Login('web', $user, false));

    Http::assertNothingSent();
});

test('se o Central não atende uma linha, a rodada para em vez de esperar a rede por todas', function () {
    centralConfiguradoParaAOutbox();
    $chamadas = 0;
    Http::fake(function () use (&$chamadas) {
        $chamadas++;

        throw new ConnectionException('Connection timed out');
    });

    $outbox = app(OutboxDoCentral::class);
    foreach (range(1, 3) as $i) {
        $outbox->registrar(OutboxDoCentral::LOGIN_REPORTADO, ['user_identifier' => "u{$i}@exemplo.test", 'user_name' => "U{$i}"]);
    }

    expect($outbox->entregarPendentes())->toBe(['entregues' => 0, 'adiadas' => 1, 'descartadas' => 0])
        ->and($chamadas)->toBe(1)

        ->and(CentralOutbox::where('tentativas', 0)->count())->toBe(2);
});
