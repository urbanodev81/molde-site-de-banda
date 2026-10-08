<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Testing\TestResponse;

dataset('paginas-abertas', ['/login']);

it('as listas do painel, que são as páginas com mais pedaços, também cabem', function (string $rota) {
    $this->seed(PerfisESeguranca::class);
    $admin = User::create([
        'name' => 'Admin', 'email' => 'admin@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $admin->syncRoles([Perfis::ADMINISTRADOR]);

    $resposta = $this->actingAs($admin)->get($rota)->assertOk();

    expect(substr_count((string) $resposta->headers->get('Link'), 'rel='))->toBeLessThanOrEqual(6)
        ->and(tamanhoDoCabecalho($resposta))->toBeLessThan(3600);
})->with(['/painel/locais', '/painel/shows', '/painel/fotos', '/painel/usuarios']);

function tamanhoDoCabecalho(TestResponse $resposta): int
{
    $tamanho = 0;

    foreach (explode("\r\n", (string) $resposta->baseResponse->headers) as $linha) {
        $tamanho += strlen($linha) + 2;
    }

    return $tamanho;
}

it('o cabeçalho Link de preload tem teto', function (string $rota) {
    $resposta = $this->get($rota)->assertOk();

    expect(substr_count((string) $resposta->headers->get('Link'), 'rel='))->toBeGreaterThan(0)->toBeLessThanOrEqual(6);
})->with('paginas-abertas');

it('o cabeçalho inteiro fica bem abaixo dos 4 KB do nginx', function (string $rota) {
    expect(tamanhoDoCabecalho($this->get($rota)->assertOk()))->toBeLessThan(3600);
})->with('paginas-abertas');
