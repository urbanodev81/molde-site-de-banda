<?php

declare(strict_types=1);

function folha(string $arquivo): string
{
    return (string) file_get_contents(resource_path('css/'.$arquivo));
}

it('a folha do site declara todo token que os widgets usam', function () {
    $a11y = folha('a11y.css');

    $tokens = [
        'surface', 'fg', 'fg-subtle',
        'border', 'border-input', 'border-strong',
        'accent', 'accent-hover', 'accent-subtle', 'accent-ring',
    ];

    foreach ($tokens as $token) {
        expect($a11y)->toContain('--'.$token.':');
    }
});

it('⚠️ a tinta é a MESMA do painel, não uma segunda paleta', function () {
    $a11y = folha('a11y.css');
    $painel = folha('app.css');

    preg_match_all('/--([a-z-]+): (\d+ \d+ \d+);/', $a11y, $noSite, PREG_SET_ORDER);

    expect($noSite)->not->toBeEmpty();

    foreach ($noSite as [, $token, $valor]) {
        expect($painel)->toContain('--'.$token.': '.$valor.';');
    }
});

it('o preflight mínimo dos widgets NÃO escapa para o resto do site', function () {
    $a11y = folha('a11y.css');

    foreach (['box-sizing: border-box', 'background-color: transparent'] as $regra) {
        expect($a11y)->toContain($regra);
    }

    $regras = (string) preg_replace('#/\*.*?\*/#s', '', $a11y);

    expect($regras)->not->toMatch('/^\s*(button|input|select|textarea|\*)[\s,{]/m');

    $blocos = preg_split('/\}/', $regras);
    $comPreflight = array_filter(
        $blocos,
        fn ($b) => str_contains($b, 'background-color: transparent') || str_contains($b, 'box-sizing'),
    );

    foreach ($comPreflight as $bloco) {
        expect($bloco)->toContain('#acessibilidade');
    }
});

it('o site monta o widget no nó que a folha veste', function () {
    expect((string) file_get_contents(resource_path('js/site.js')))
        ->toContain("ancora.id = 'acessibilidade'");

    expect(folha('a11y.css'))->toContain('#acessibilidade');
});
