<?php

declare(strict_types=1);

use App\Models\Integrante;

it('não publica integrante sem autorização de imagem registrada', function () {
    $sem = Integrante::create(['nome' => 'Sem autorização', 'ativa' => true]);

    expect(Integrante::query()->publicaveis()->pluck('id'))->not->toContain($sem->id)
        ->and($sem->autorizada())->toBeFalse()
        ->and($sem->motivosParaNaoAparecer())->not->toBeEmpty();

    $this->get('/')->assertDontSee('Sem autorização');
});

it('publica quando a autorização está registrada', function () {
    $com = Integrante::create([
        'nome' => 'Com autorização',
        'ativa' => true,
        'autorizacao_imagem_em' => now()->subDay(),
    ]);

    expect(Integrante::query()->publicaveis()->pluck('id'))->toContain($com->id);
});

it('não publica integrante inativa, mesmo autorizada', function () {
    $inativa = Integrante::create([
        'nome' => 'Saiu da banda',
        'ativa' => false,
        'autorizacao_imagem_em' => now()->subYear(),
    ]);

    expect(Integrante::query()->publicaveis()->pluck('id'))->not->toContain($inativa->id);
});
