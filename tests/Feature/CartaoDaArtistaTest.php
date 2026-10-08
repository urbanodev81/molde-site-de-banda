<?php

declare(strict_types=1);

use App\Models\Integrante;
use Database\Seeders\ConteudoInicial;

function integranteNoPalco(int $ordem, array $extra = []): Integrante
{
    return Integrante::create([
        'nome' => "Integrante $ordem",
        'ordem' => $ordem,
        'ativa' => true,
        'recorte_path' => "sementes/integrante-0$ordem.png",
        'palco_esquerda' => 19,
        'palco_largura' => 33,
        'palco_base' => 0,
        ...$extra,
    ]);
}

it('abre um cartão para quem tem o que contar', function () {
    integranteNoPalco(1, [
        'nome' => 'Ana',
        'instrumento' => 'percussão, cordas & voz',
        'bio' => 'Começou no coral da escola e nunca mais largou o microfone.',
        'instagram' => 'https://instagram.com/ana',
        'autorizacao_imagem_em' => '2026-09-08',
    ]);

    $html = $this->get('/')->getContent();

    expect($html)

        ->toContain('class="elenco__toque"')
        ->toContain('aria-expanded="false"')
        ->toContain('Ver detalhes de Ana')
        ->toContain('class="artista"')
        ->toContain('Começou no coral da escola')
        ->toContain('https://instagram.com/ana');
});

it('⚠️ NÃO abre cartão para quem não tem nada além da foto', function () {
    integranteNoPalco(1);

    $html = $this->get('/')->getContent();

    expect($html)
        ->toContain('class="elenco__m"')
        ->not->toContain('elenco__toque')
        ->not->toContain('class="artista"');
});

it('o cartão é o MESMO nas duas versões do topo', function () {
    integranteNoPalco(1, [
        'nome' => 'Ana',
        'bio' => 'Mesma bio nas duas.',
        'autorizacao_imagem_em' => '2026-09-08',
    ]);

    $home = $this->get('/')->getContent();
    $previa = $this->get('/previa/kit')->getContent();

    foreach (['elenco__toque', 'class="artista"', 'Mesma bio nas duas.', 'Ver detalhes de Ana'] as $marca) {
        expect($home)->toContain($marca)
            ->and($previa)->toContain($marca);
    }
});

it('a estrela e o raio existem nas DUAS versões', function () {
    $home = $this->get('/')->getContent();
    $previa = $this->get('/previa/kit')->getContent();

    foreach ([$home, $previa] as $html) {
        expect(substr_count($html, 'class="adesivo'))->toBe(2)
            ->and($html)->toContain('sementes/estrela.webp')
            ->and($html)->toContain('sementes/raio.webp');
    }
});

it('mostra as redes DELA quando ela tem', function () {
    integranteNoPalco(1, [
        'nome' => 'Carol',
        'autorizacao_imagem_em' => '2026-09-08',
        'instagram' => 'https://instagram.com/carol',
        'tiktok' => 'https://tiktok.com/@carol',
    ]);

    $html = $this->get('/')->getContent();

    expect($html)
        ->toContain('https://instagram.com/carol')
        ->toContain('https://tiktok.com/@carol')
        ->toContain('Instagram de Carol')

        ->not->toContain('Redes da banda');
});

it('⚠️ sem redes dela, cai nas da banda — e AVISA que são da banda', function () {
    integranteNoPalco(1, ['nome' => 'Carol', 'autorizacao_imagem_em' => '2026-09-08']);

    $html = $this->get('/')->getContent();

    expect($html)
        ->toContain('Redes da banda')
        ->toContain('Instagram da banda')
        ->toContain('amelhorbandaoficial');
});

it('⚠️ a abertura do cartão sobrevive a "menos movimento"', function () {
    $folha = file_get_contents(resource_path('css/site.css'));

    expect($folha)
        ->toContain('--y: 0 !important')
        ->toContain('--s: 1 !important')
        ->toContain('transition: opacity .3s ease, visibility .3s !important')

        ->toContain('translate: var(--x) var(--y)')
        ->toContain('scale: var(--s)');
});

it('⚠️ o seeder não duplica o palco ao rodar duas vezes', function () {
    $this->seed(ConteudoInicial::class);
    $this->seed(ConteudoInicial::class);

    expect(Integrante::count())->toBe(3)
        ->and(Integrante::orderBy('ordem')->pluck('nome')->all())->toBe(['Ana', 'Bia', 'Carol']);
});
