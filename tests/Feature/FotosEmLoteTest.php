<?php

declare(strict_types=1);

use App\Enums\TipoShow;
use App\Mail\ListaDeFotos;
use App\Models\Foto;
use App\Models\Local;
use App\Models\Show;
use App\Models\TipoGaleria;
use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(PerfisESeguranca::class);

    $this->banda = User::create([
        'name' => 'Banda', 'email' => 'banda@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $this->banda->syncRoles([Perfis::BANDA]);

    $this->producao = User::create([
        'name' => 'Produção', 'email' => 'producao@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $this->producao->syncRoles([Perfis::PRODUCAO]);
});

function fotoNoDisco(array $atributos = []): Foto
{
    $caminho = 'fotos/'.uniqid('', true).'.jpg';
    Storage::disk('public')->put($caminho, 'imagem');

    return Foto::create(['arquivo_path' => $caminho, 'publicada' => true, ...$atributos]);
}

it('arquivar pela linha guarda o registro E o arquivo, e a foto vai para a aba de arquivadas', function () {
    $foto = fotoNoDisco(['legenda' => 'Sala cheia']);

    $this->actingAs($this->banda)->delete(route('painel.fotos.destroy', $foto))->assertRedirect();

    expect(Foto::withTrashed()->find($foto->id)?->trashed())->toBeTrue();
    Storage::disk('public')->assertExists($foto->arquivo_path);

    $this->actingAs($this->banda)->get(route('painel.fotos.index'))
        ->assertInertia(fn (AssertableInertia $p) => $p->where('fotos.data', [])->where('totais.arquivadas', 1));

    $this->actingAs($this->banda)->get(route('painel.fotos.index', ['arquivadas' => 1]))
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->where('arquivadas', true)
            ->where('fotos.data.0.uuid', (string) $foto->uuid)
            ->where('fotos.data.0.arquivada', true));
});

it('em lote: tira do site, publica, arquiva e restaura — e nada some do disco', function () {
    $a = fotoNoDisco();
    $b = fotoNoDisco();
    $ids = [(string) $a->uuid, (string) $b->uuid];
    $lote = fn (string $acao) => $this->actingAs($this->banda)
        ->post(route('painel.fotos.lote'), ['acao' => $acao, 'ids' => $ids])
        ->assertRedirect()->assertSessionHasNoErrors();

    $lote('despublicar');
    expect(Foto::where('publicada', false)->count())->toBe(2);

    $lote('publicar');
    expect(Foto::where('publicada', true)->count())->toBe(2);

    $lote('arquivar')->assertSessionHas('sucesso', '2 fotos arquivadas.');
    expect(Foto::count())->toBe(0)->and(Foto::onlyTrashed()->count())->toBe(2);
    Storage::disk('public')->assertExists($a->arquivo_path);

    $lote('restaurar')->assertSessionHas('sucesso', '2 fotos restauradas.');
    expect(Foto::count())->toBe(2);
});

it('não existe ação de excluir em lote', function () {
    $foto = fotoNoDisco();

    $this->actingAs($this->banda)
        ->post(route('painel.fotos.lote'), ['acao' => 'excluir', 'ids' => [(string) $foto->uuid]])
        ->assertSessionHasErrors('acao');

    expect(Foto::count())->toBe(1);
});

it('quem não gerencia a galeria não roda ação em lote nem envia lista', function () {
    $foto = fotoNoDisco();
    Mail::fake();

    $this->actingAs($this->producao)
        ->post(route('painel.fotos.lote'), ['acao' => 'arquivar', 'ids' => [(string) $foto->uuid]])
        ->assertForbidden();

    $this->actingAs($this->producao)
        ->post(route('painel.fotos.lote.enviar'), ['ids' => [(string) $foto->uuid], 'email' => 'x@teste.local'])
        ->assertForbidden();

    expect(Foto::count())->toBe(1);
    Mail::assertNothingSent();
});

it('a folha de impressão traz só as fotos marcadas', function () {
    $marcada = fotoNoDisco(['legenda' => 'Entra na folha']);
    fotoNoDisco(['legenda' => 'Fica de fora']);

    $this->actingAs($this->banda)
        ->get(route('painel.fotos.imprimir', ['ids' => [(string) $marcada->uuid]]))
        ->assertOk()
        ->assertSee('Entra na folha')
        ->assertDontSee('Fica de fora');
});

it('o e-mail leva só o que está no site: foto de evento particular fica de fora mesmo marcada', function () {
    Mail::fake();

    $tipo = TipoGaleria::create(['nome' => 'Ensaio', 'slug' => 'ensaio', 'publicado' => true]);
    $doSite = fotoNoDisco(['legenda' => 'Ensaio de quinta', 'tipo_galeria_id' => $tipo->id]);

    $local = Local::create(['nome' => 'Casa de alguém', 'slug' => 'casa-de-alguem']);
    $festa = Show::create([
        'titulo' => 'Aniversário', 'local_id' => $local->id, 'tipo' => TipoShow::Particular,
        'comeca_em' => now()->subWeek(),
    ]);
    $particular = fotoNoDisco(['legenda' => 'Festa fechada', 'show_id' => $festa->id]);

    $this->actingAs($this->banda)->post(route('painel.fotos.lote.enviar'), [
        'ids' => [(string) $doSite->uuid, (string) $particular->uuid],
        'email' => 'casa@teste.local',
        'mensagem' => 'Segue o material.',
    ])->assertRedirect()->assertSessionHasNoErrors();

    Mail::assertSent(ListaDeFotos::class, function (ListaDeFotos $m) {
        $titulos = array_column($m->fotos, 'titulo');

        return $m->hasTo('casa@teste.local')
            && $m->hasReplyTo('banda@teste.local')
            && $titulos === ['Ensaio de quinta'];
    });
});

it('marcar só foto que não está no site não envia e-mail nenhum', function () {
    Mail::fake();
    $semTipo = fotoNoDisco(['legenda' => 'Guardada']);

    $this->actingAs($this->banda)
        ->post(route('painel.fotos.lote.enviar'), ['ids' => [(string) $semTipo->uuid], 'email' => 'casa@teste.local'])
        ->assertSessionHasErrors('ids');

    Mail::assertNothingSent();
});

it('o e-mail renderiza com o link de cada foto', function () {
    $html = (new ListaDeFotos(
        [['titulo' => 'Sala cheia', 'contexto' => 'Bar do Centro', 'credito' => 'Ana', 'url' => 'https://exemplo.test/f.jpg']],
        $this->banda,
        'Segue.',
    ))->render();

    expect($html)->toContain('https://exemplo.test/f.jpg')->toContain('Sala cheia')->toContain('Segue.');
});
