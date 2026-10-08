<?php

declare(strict_types=1);

function contraste(string $a, string $b): float
{
    $lum = function (string $hex): float {
        $hex = ltrim($hex, '#');
        $canais = array_map(
            fn ($c) => ($c = hexdec($c) / 255) <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4,
            [substr($hex, 0, 2), substr($hex, 2, 2), substr($hex, 4, 2)],
        );

        return 0.2126 * $canais[0] + 0.7152 * $canais[1] + 0.0722 * $canais[2];
    };

    $la = $lum($a);
    $lb = $lum($b);

    return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
}

function tokensDoClaro(): array
{
    $css = (string) file_get_contents(resource_path('css/site.css'));

    $inicio = strpos($css, ":root[data-tema='claro'] {");
    expect($inicio)->not->toBeFalse('o bloco do tema claro sumiu da folha');

    $bloco = substr($css, $inicio, strpos($css, '}', $inicio) - $inicio);

    preg_match_all('/--([a-z0-9-]+):\s*(#[0-9a-fA-F]{6})/', $bloco, $achados, PREG_SET_ORDER);

    return array_column($achados, 2, 1);
}

it('⚠️ a paleta do tema claro passa em contraste', function () {
    $t = tokensDoClaro();

    $pares = [
        'texto sobre o fundo' => [$t['text'], $t['ink']],
        'texto sobre a superfície' => [$t['text'], $t['ink-2']],
        'apoio sobre o fundo' => [$t['muted'], $t['ink']],
        'apoio sobre a superfície' => [$t['muted'], $t['ink-2']],
        'rosa sobre o fundo' => [$t['acento'], $t['ink']],
        'rosa sobre a superfície' => [$t['acento'], $t['ink-2']],
        'branco sobre o rosa' => ['#ffffff', $t['acento']],
        'títulos sobre o fundo' => [$t['cream'], $t['ink']],
    ];

    foreach ($pares as $nome => [$frente, $fundo]) {
        expect(contraste($frente, $fundo))
            ->toBeGreaterThanOrEqual(4.5, "$nome ({$frente} sobre {$fundo})");
    }
});

it('⚠️ o adesivo de seção não escurece junto com o rosa', function () {
    $css = (string) file_get_contents(resource_path('css/site.css'));

    expect($css)->toContain('background: var(--acento-vivo);');

    preg_match('/--acento-vivo:\s*(#[0-9a-fA-F]{6})/', $css, $vivo);

    expect(contraste('#100921', $vivo[1]))->toBeGreaterThanOrEqual(4.5);
});

it('a superfície amarela devolve o valor escuro aos tokens', function () {
    $css = (string) file_get_contents(resource_path('css/site.css'));

    expect($css)->toMatch("/:root\[data-tema='claro'\] \.contratar,\s*\n:root\[data-tema='claro'\] #modal-contratar \.modal__caixa \{[^}]*--ink: #06040c;/");
});

it('⚠️ o botão do tema nasce escondido — sem JavaScript ele não existe', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="tema"', false)
        ->assertSee('hidden aria-pressed="false"', false)

        ->assertSee('aria-label="Usar o tema claro"', false);
});

it('⚠️ o tema é aplicado ANTES da folha, senão a página pisca', function () {
    $html = $this->get('/')->assertOk()->getContent();

    $script = strpos($html, "localStorage.getItem('banda.tema')");

    $folha = strpos($html, '<link rel="stylesheet"');

    expect($script)->not->toBeFalse()
        ->and($folha)->not->toBeFalse()
        ->and($script)->toBeLessThan($folha);
});

it('o escuro continua sendo o padrão de quem chega', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('<html lang="pt-BR" class="dark">')
        ->and($html)->not->toContain('data-tema="claro"');

    $regras = (string) preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents(resource_path('css/site.css')));

    expect($regras)->not->toContain('prefers-color-scheme');
});

it('o tema claro cabe em quatro lugares — para poder ser esquecido', function () {
    expect((string) file_get_contents(resource_path('js/site.js')))->toContain('banda.tema');
    expect((string) file_get_contents(resource_path('views/site/layout.blade.php')))->toContain('class="tema" id="tema"');
});
