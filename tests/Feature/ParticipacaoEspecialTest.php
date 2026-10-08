<?php

declare(strict_types=1);

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Models\Local;
use App\Models\ParticipacaoEspecial;
use App\Models\Show;
use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;

beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
    $this->local = Local::create(['nome' => 'Bar do Centro', 'slug' => 'bar-do-centro']);
});

function noiteComConvidado(array $extra = []): Show
{
    return Show::create([
        'local_id' => test()->local->id,
        'comeca_em' => now()->subWeek(),
        'status' => StatusShow::Realizado,
        'tipo' => TipoShow::Publico,
        'publicado' => true,
        ...$extra,
    ]);
}

function convidado(array $extra = []): ParticipacaoEspecial
{
    return ParticipacaoEspecial::create([
        'nome' => 'Joana Guitarra',
        'funcao' => 'Guitarra',
        'descricao' => 'Tocou Sweet Child com a gente.',
        'publicada' => true,
        'autorizacao_imagem_em' => '2026-09-10',
        ...$extra,
    ]);
}

function usuariaDaBanda(): User
{
    $u = User::create([
        'name' => 'Banda', 'email' => 'banda@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $u->syncRoles([Perfis::BANDA]);

    return $u;
}

it('aparece na página da noite em que tocou', function () {
    $noite = noiteComConvidado();
    convidado()->shows()->attach($noite);

    $this->get(route('site.show', $noite))
        ->assertOk()
        ->assertSee('Participação especial')
        ->assertSee('Joana Guitarra')
        ->assertSee('Tocou Sweet Child com a gente.');
});

it('aparece na página da banda, com link para as noites', function () {
    $noite = noiteComConvidado();
    convidado(['instagram' => 'https://instagram.com/joana'])->shows()->attach($noite);

    $this->get(route('site.banda'))
        ->assertOk()
        ->assertSee('Participações especiais')
        ->assertSee('Joana Guitarra')
        ->assertSee(route('site.show', $noite), false)
        ->assertSee('aria-label="Instagram de Joana Guitarra"', false);
});

it('⚠️ sem autorização de imagem não aparece em lugar nenhum do site', function () {
    $noite = noiteComConvidado();
    convidado(['autorizacao_imagem_em' => null])->shows()->attach($noite);

    $this->get(route('site.show', $noite))->assertOk()->assertDontSee('Joana Guitarra');
    $this->get(route('site.banda'))->assertOk()->assertDontSee('Joana Guitarra');
});

it('⚠️ despublicada também não aparece', function () {
    $noite = noiteComConvidado();
    convidado(['publicada' => false])->shows()->attach($noite);

    $this->get(route('site.show', $noite))->assertOk()->assertDontSee('Joana Guitarra');
    $this->get(route('site.banda'))->assertOk()->assertDontSee('Joana Guitarra');
});

it('⚠️ não leva link para a noite de evento particular', function () {
    $festa = noiteComConvidado(['tipo' => TipoShow::Particular]);
    $pessoa = convidado();
    $pessoa->shows()->attach($festa);

    $this->get(route('site.banda'))
        ->assertOk()
        ->assertSee('Joana Guitarra')
        ->assertDontSee('/agenda/'.$festa->slug, false);
});

it('a banda cadastra, liga às noites e desmarca pelo painel', function () {
    $noite = noiteComConvidado();
    $banda = usuariaDaBanda();

    $this->actingAs($banda)
        ->post(route('painel.participacoes.store'), [
            'nome' => 'Joana Guitarra', 'funcao' => 'Guitarra', 'publicada' => '1',
            'shows' => [$noite->id], 'autorizacao_imagem_em' => '2026-09-10',
        ])
        ->assertRedirect(route('painel.participacoes.index'));

    $pessoa = ParticipacaoEspecial::firstWhere('nome', 'Joana Guitarra');
    expect($pessoa->shows()->pluck('shows.id')->all())->toBe([$noite->id]);

    $this->actingAs($banda)
        ->put(route('painel.participacoes.update', $pessoa), ['nome' => 'Joana Guitarra'])
        ->assertRedirect(route('painel.participacoes.index'));

    expect($pessoa->fresh())->publicada->toBeFalse()
        ->and($pessoa->shows()->count())->toBe(0);
});

it('recusa autorização com data no futuro', function () {
    $this->actingAs(usuariaDaBanda())
        ->post(route('painel.participacoes.store'), ['nome' => 'X', 'autorizacao_imagem_em' => now()->addDay()->toDateString()])
        ->assertSessionHasErrors('autorizacao_imagem_em');
});

it('as telas abrem para a banda e ficam fechadas para a produção', function () {
    $banda = usuariaDaBanda();
    $this->actingAs($banda)->get('/painel/participacoes')->assertOk();
    $this->actingAs($banda)->get('/painel/participacoes/criar/nova')->assertOk();

    $producao = User::create([
        'name' => 'Produção', 'email' => 'producao@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $producao->syncRoles([Perfis::PRODUCAO]);

    $this->actingAs($producao)->get('/painel/participacoes')->assertForbidden();
});
