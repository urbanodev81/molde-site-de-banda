<?php

declare(strict_types=1);

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Enums\TipoVideo;
use App\Mail\ListaDoPainel;
use App\Models\Contratacao;
use App\Models\Depoimento;
use App\Models\Integrante;
use App\Models\Local;
use App\Models\Material;
use App\Models\Musica;
use App\Models\ParticipacaoEspecial;
use App\Models\Pergunta;
use App\Models\Publicacao;
use App\Models\Show;
use App\Models\TipoEspaco;
use App\Models\TipoGaleria;
use App\Models\User;
use App\Models\Video;
use App\Support\Lote\ListasEmLote;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(PerfisESeguranca::class);

    $conta = fn (string $nome, ?string $perfil) => tap(User::create([
        'name' => $nome, 'email' => str($nome)->slug().'@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]), fn (User $u) => $perfil && $u->syncRoles([$perfil]));

    $this->admin = $conta('Alex', Perfis::ADMINISTRADOR);
    $this->semPerfil = $conta('Sem perfil', null);
});

function itemDaLista(string $lista, int $n = 1): Model
{
    return match ($lista) {
        'shows' => Show::create([
            'titulo' => "Noite {$n}", 'comeca_em' => now()->addDays($n)->setTime(21, 0),
            'status' => StatusShow::Confirmado, 'tipo' => TipoShow::Publico, 'publicado' => true,
        ]),
        'locais' => Local::create(['nome' => "Bar {$n}", 'slug' => "bar-{$n}", 'ativa' => true]),
        'integrantes' => Integrante::create(['nome' => "Integrante {$n}", 'ativa' => true]),
        'participacoes' => ParticipacaoEspecial::create(['nome' => "Convidada {$n}", 'publicada' => true]),
        'videos' => Video::create([
            'titulo' => "Vídeo {$n}", 'tipo' => TipoVideo::Arquivo, 'arquivo_mp4_path' => "videos/v{$n}.mp4", 'publicado' => true,
        ]),
        'musicas' => Musica::create(['titulo' => "Música {$n}", 'artista' => 'Banda', 'publicada' => true]),
        'tipos-galeria' => TipoGaleria::create(['nome' => "Ensaio {$n}", 'slug' => "ensaio-{$n}", 'publicado' => true]),
        'tipos-espaco' => TipoEspaco::create(['nome' => "Galpão {$n}", 'publicado' => true]),
        'perguntas' => Pergunta::create(['pergunta' => "Dúvida {$n}?", 'resposta' => 'Sim.', 'publicada' => true]),
        'depoimentos' => Depoimento::create(['autor' => "Dono {$n}", 'texto' => 'Lotou a casa.', 'publicado' => true]),
        'publicacoes' => Publicacao::create(['titulo' => "Matéria {$n}", 'link' => "https://jornal.teste/materia-{$n}", 'publicada' => true]),
        'materiais' => Material::create(['titulo' => "Logo {$n}", 'arquivo_path' => "materiais/logo{$n}.svg", 'publico' => true]),
        'contratacoes' => Contratacao::create(['nome' => "Pedido {$n}", 'email' => "pedido{$n}@teste.local"]),
        'usuarios' => User::create([
            'name' => "Conta {$n}", 'email' => "conta{$n}@teste.local", 'password' => 'senha-de-teste-123',
            'ativo' => true, 'email_verified_at' => now(),
        ]),
    };
}

function chaveDe(string $lista, Model $item): string|int
{
    $chave = ListasEmLote::chave(ListasEmLote::de($lista));

    return $chave === 'id' ? $item->id : (string) $item->uuid;
}

dataset('listas', fn () => array_keys(ListasEmLote::todas()));

it('toda lista do registro tem as rotas com a permissão na ROTA', function (string $lista) {
    $definicao = ListasEmLote::de($lista);

    expect(Route::getRoutes()->getByName("painel.{$lista}.lote")->gatherMiddleware())
        ->toContain("can:{$definicao['gerenciar']}");
    expect(Route::getRoutes()->getByName("painel.{$lista}.imprimir")->gatherMiddleware())
        ->toContain("can:{$definicao['ver']}");
    expect(Route::has("painel.{$lista}.lote.enviar"))->toBe($definicao['publicos'] !== null);
})->with('listas');

it('só as listas com face pública e sem dado pessoal enviam por e-mail', function () {
    $enviam = array_keys(array_filter(ListasEmLote::todas(), fn (array $l) => $l['publicos'] !== null));

    expect($enviam)->toEqualCanonicalizing(['shows', 'videos', 'musicas', 'materiais', 'publicacoes']);
});

it('em lote: desliga, liga, arquiva e restaura, sem apagar nada', function (string $lista) {
    $definicao = ListasEmLote::de($lista);
    $modelo = $definicao['modelo'];
    $antes = $modelo::withTrashed()->count();
    $itens = [itemDaLista($lista, 1), itemDaLista($lista, 2)];
    $ids = array_map(fn ($i) => chaveDe($lista, $i), $itens);
    $pk = array_map(fn ($i) => $i->getKey(), $itens);

    $lote = fn (string $acao) => $this->actingAs($this->admin)
        ->post(route("painel.{$lista}.lote"), ['acao' => $acao, 'ids' => $ids]);

    if ($definicao['coluna'] !== null) {
        $lote('desligar')->assertRedirect()->assertSessionHasNoErrors();
        expect($modelo::whereKey($pk)->where($definicao['coluna'], false)->count())->toBe(2);

        $lote('ligar')->assertSessionHasNoErrors();
        expect($modelo::whereKey($pk)->where($definicao['coluna'], true)->count())->toBe(2);
    } else {
        $lote('desligar')->assertSessionHasErrors('acao');
    }

    $lote('arquivar')->assertSessionHasNoErrors();
    expect($modelo::whereKey($pk)->count())->toBe(0)
        ->and($modelo::onlyTrashed()->whereKey($pk)->count())->toBe(2);

    $lote('restaurar')->assertSessionHasNoErrors();
    expect($modelo::whereKey($pk)->count())->toBe(2)

        ->and($modelo::withTrashed()->count())->toBe($antes + 2);
})->with('listas');

it('não existe ação de excluir em lote', function (string $lista) {
    $item = itemDaLista($lista);

    $this->actingAs($this->admin)
        ->post(route("painel.{$lista}.lote"), ['acao' => 'excluir', 'ids' => [chaveDe($lista, $item)]])
        ->assertSessionHasErrors('acao');

    expect($item->fresh())->not->toBeNull()->and($item->fresh()->trashed())->toBeFalse();
})->with('listas');

it('quem não tem a permissão não roda ação em lote nem imprime', function (string $lista) {
    $item = itemDaLista($lista);
    $ids = [chaveDe($lista, $item)];

    $this->actingAs($this->semPerfil)->post(route("painel.{$lista}.lote"), ['acao' => 'arquivar', 'ids' => $ids])->assertForbidden();
    $this->actingAs($this->semPerfil)->get(route("painel.{$lista}.imprimir", ['ids' => $ids]))->assertForbidden();

    expect($item->fresh()->trashed())->toBeFalse();
})->with('listas');

it('a tela abre nas duas abas e a de arquivados traz só o arquivado', function (string $lista) {
    $fica = itemDaLista($lista, 1);
    $sai = itemDaLista($lista, 2);
    $sai->delete();

    $this->actingAs($this->admin)->get(route("painel.{$lista}.index"))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p->where('arquivados', false)->where('totais.1', 1));

    $this->actingAs($this->admin)->get(route("painel.{$lista}.index", ['arquivados' => 1]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p->where('arquivados', true)->where('totais.1', 1));
})->with('listas');

it('a folha de impressão traz só o que foi marcado, com a situação', function (string $lista) {
    $definicao = ListasEmLote::de($lista);

    $marcado = itemDaLista($lista, 1)->fresh();
    $fora = itemDaLista($lista, 2)->fresh();

    $this->actingAs($this->admin)
        ->get(route("painel.{$lista}.imprimir", ['ids' => [chaveDe($lista, $marcado)]]))
        ->assertOk()
        ->assertSee($definicao['linha']($marcado)['titulo'])
        ->assertDontSee($definicao['linha']($fora)['titulo']);
})->with('listas');

it('arquivar material pela linha guarda o arquivo no disco', function () {
    Storage::disk('public')->put('materiais/rider.pdf', 'pdf');
    $material = Material::create(['titulo' => 'Rider', 'arquivo_path' => 'materiais/rider.pdf', 'publico' => true]);

    $this->actingAs($this->admin)->delete(route('painel.materiais.destroy', $material))->assertRedirect();

    Storage::disk('public')->assertExists('materiais/rider.pdf');
    expect(Material::onlyTrashed()->count())->toBe(1);

    auth()->logout();
    $this->get('/imprensa')->assertDontSee('Rider');
});

it('o e-mail leva só o que está no site', function () {
    Mail::fake();

    $noAr = Material::create(['titulo' => 'Logo em vetor', 'arquivo_path' => 'materiais/logo.svg', 'publico' => true]);
    $interno = Material::create(['titulo' => 'Contrato modelo', 'arquivo_path' => 'materiais/contrato.pdf', 'publico' => false]);
    $arquivado = Material::create(['titulo' => 'Logo antigo', 'arquivo_path' => 'materiais/velho.svg', 'publico' => true]);
    $arquivado->delete();

    $this->actingAs($this->admin)->post(route('painel.materiais.lote.enviar'), [
        'ids' => [(string) $noAr->uuid, (string) $interno->uuid, (string) $arquivado->uuid],
        'email' => 'imprensa@teste.local',
    ])->assertRedirect()->assertSessionHasNoErrors();

    Mail::assertSent(ListaDoPainel::class, fn (ListaDoPainel $m) => $m->hasTo('imprensa@teste.local')
        && $m->hasReplyTo('alex@teste.local')
        && array_column($m->itens, 'titulo') === ['Logo em vetor']);
});

it('show particular marcado como publicado não sai por e-mail', function () {
    Mail::fake();

    $festa = Show::create([
        'titulo' => 'Aniversário da Ana', 'comeca_em' => now()->addWeek(),
        'status' => StatusShow::Confirmado, 'tipo' => TipoShow::Particular, 'publicado' => true,
    ]);

    $this->actingAs($this->admin)
        ->post(route('painel.shows.lote.enviar'), ['ids' => [(string) $festa->uuid], 'email' => 'x@teste.local'])
        ->assertSessionHasErrors('ids');

    Mail::assertNothingSent();
});

it('e-mail de conta arquivada recusa a conta nova com mensagem, não com erro 500', function () {
    $arquivada = User::create([
        'name' => 'Antiga', 'email' => 'antiga@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $arquivada->delete();

    $dados = [
        'name' => 'Nova', 'email' => 'antiga@teste.local', 'perfis' => [Perfis::ADMINISTRADOR], 'ativo' => true,
        'password' => 'Senha-Longa-De-Teste-9731', 'password_confirmation' => 'Senha-Longa-De-Teste-9731',
    ];

    $this->actingAs($this->admin)->post(route('painel.usuarios.store'), $dados)
        ->assertSessionHasErrors(['email' => 'Já existe uma conta arquivada com este e-mail. Restaure-a na aba de arquivadas em vez de criar outra.']);

    $this->actingAs($this->admin)
        ->put(route('painel.usuarios.update', $this->semPerfil), [...$dados, 'password' => null, 'password_confirmation' => null])
        ->assertSessionHasErrors('email');

    expect(User::withTrashed()->where('email', 'antiga@teste.local')->count())->toBe(1);
});

it('ninguém desativa nem arquiva a própria conta em lote', function () {
    $outra = itemDaLista('usuarios');
    $ids = [(string) $this->admin->uuid, (string) $outra->uuid];

    $this->actingAs($this->admin)->post(route('painel.usuarios.lote'), ['acao' => 'desligar', 'ids' => $ids]);
    $this->actingAs($this->admin)->post(route('painel.usuarios.lote'), ['acao' => 'arquivar', 'ids' => $ids])
        ->assertSessionHas('sucesso', '1 conta arquivada. A sua própria conta ficou como estava.');

    expect($this->admin->fresh()->ativo)->toBeTrue()
        ->and($this->admin->fresh()->trashed())->toBeFalse()
        ->and($outra->fresh()->trashed())->toBeTrue();
});

it('o tipo "Shows" da galeria não é arquivado em lote, mas sai do site', function () {
    $shows = TipoGaleria::query()->where('slug', 'shows')->first()
        ?? TipoGaleria::create(['nome' => 'Shows', 'slug' => 'shows', 'publicado' => true]);

    $this->actingAs($this->admin)->post(route('painel.tipos-galeria.lote'), ['acao' => 'arquivar', 'ids' => [$shows->id]]);
    expect($shows->fresh()->trashed())->toBeFalse();

    $this->actingAs($this->admin)->post(route('painel.tipos-galeria.lote'), ['acao' => 'desligar', 'ids' => [$shows->id]]);
    expect($shows->fresh()->publicado)->toBeFalse();
});
