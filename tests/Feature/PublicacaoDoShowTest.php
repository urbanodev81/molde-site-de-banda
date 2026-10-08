<?php

declare(strict_types=1);

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Models\Local;
use App\Models\Show;

beforeEach(function () {
    $this->local = Local::create(['nome' => 'Bar do Zé', 'slug' => 'bar-do-ze']);
});

function criarShow(array $atributos = []): Show
{
    return Show::create([
        'local_id' => test()->local->id,
        'comeca_em' => now()->addWeek(),
        'status' => StatusShow::Confirmado,
        'tipo' => TipoShow::Publico,
        'publicado' => true,
        ...$atributos,
    ]);
}

it('publica o show confirmado, público e futuro', function () {
    $show = criarShow();

    expect(Show::query()->publicaveis()->futuros()->pluck('id'))->toContain($show->id)
        ->and($show->motivosParaNaoAparecer())->toBe([]);
});

it('esconde o rascunho', function () {
    $show = criarShow(['status' => StatusShow::Rascunho]);

    expect(Show::query()->publicaveis()->pluck('id'))->not->toContain($show->id)
        ->and($show->motivosParaNaoAparecer())->not->toBeEmpty();
});

it('esconde o cancelado', function () {
    $show = criarShow(['status' => StatusShow::Cancelado]);

    expect(Show::query()->publicaveis()->pluck('id'))->not->toContain($show->id);
});

it('NUNCA publica evento particular — endereço de festa privada não vai para a internet', function () {
    $show = criarShow(['tipo' => TipoShow::Particular, 'endereco_livre' => 'Rua da casa de alguém, 100']);

    expect(Show::query()->publicaveis()->pluck('id'))->not->toContain($show->id);

    $this->get('/')->assertDontSee('Rua da casa de alguém');
});

it('esconde o que está marcado como não publicado', function () {
    $show = criarShow(['publicado' => false]);

    expect(Show::query()->publicaveis()->pluck('id'))->not->toContain($show->id);
});

it('mantém o show realizado no site, porque a agenda passada é prova social', function () {
    $show = criarShow(['status' => StatusShow::Realizado, 'comeca_em' => now()->subMonth()]);

    expect(Show::query()->publicaveis()->passados()->pluck('id'))->toContain($show->id);
});

it('encerra sozinho o show confirmado cuja data passou', function () {
    $show = criarShow(['comeca_em' => now()->subDay()]);

    $this->artisan('shows:encerrar')->assertSuccessful();

    expect($show->fresh()->status)->toBe(StatusShow::Realizado);
});
