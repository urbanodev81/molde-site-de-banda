<?php

declare(strict_types=1);

it('serve as páginas públicas', function (string $rota) {
    $this->get($rota)->assertOk();
})->with(['/', '/agenda', '/repertorio', '/imprensa', '/privacidade', '/sitemap.xml', '/llms.txt']);

it('entrega o conteúdo no HTML, sem depender de JavaScript', function () {
    $this->get('/')->assertSee('A melhor banda', escape: false)->assertDontSee('data-page=');
});

it('esconde o painel dos rastreadores', function () {
    expect(file_get_contents(public_path('robots.txt')))
        ->toContain('Disallow: /painel/')
        ->toContain('Sitemap:');
});

it('não esconde conteúdo atrás de JavaScript', function () {
    $folha = file_get_contents(resource_path('css/site.css'));

    foreach (['.revela', '.elenco__m'] as $efeito) {
        expect($folha)
            ->toContain("html.js {$efeito}")

            ->and($folha)->not->toMatch('/^'.preg_quote($efeito, '/').'\s*\{[^}]*opacity:\s*0/m');
    }
});

it('a cortina não pode cobrir o site de quem não tem JavaScript', function () {
    $folha = file_get_contents(resource_path('css/site.css'));

    expect($folha)
        ->toContain('html.js .cortina')

        ->toMatch('/^\.cortina\s*\{\s*display:\s*none;\s*\}/m');

    expect($folha)

        ->not->toContain('html.js .cortina { display: none !important; }')

        ->toContain('animation: cortina-apaga')
        ->toContain('animation: cortina-acende')
        ->toContain('@keyframes cortina-apaga')
        ->toContain('@keyframes cortina-acende');

    expect(file_get_contents(resource_path('js/site.js')))
        ->not->toContain('if (cortina && !menosMovimento)')
        ->toContain('if (cortina) {');
});

it('⚠️ a transição de página mostra o rosto E a marca, e o movimento sai sob movimento reduzido', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('cortina__grupo')
        ->and($html)->toContain('cortina__marca')

        ->and($html)->toContain('sementes/lettering-700.webp');

    $folha = file_get_contents(resource_path('css/site.css'));

    expect($folha)
        ->toContain('@keyframes cortina-deriva')

        ->toMatch('/\.cortina\.fechando \.cortina__grupo[^{]*\{[^}]*cortina-deriva/s')

        ->toMatch('/\.cortina\.fechando \.cortina__marca\s*\{[^}]*rosto-entra/');

    expect($folha)->toMatch('/html\.js \.cortina\.fechando \.cortina__grupo\s*\{\s*animation: none !important/');
});

it('marca que há JavaScript antes da folha do site', function () {
    $html = $this->get('/')->getContent();

    $posicaoDoScript = strpos($html, "classList.add('js')");

    preg_match('#<link[^>]+href="[^"]*/build/assets/site-[^"]+\.css"#', $html, $achado, PREG_OFFSET_CAPTURE);
    $posicaoDaFolha = $achado[0][1] ?? false;

    expect($posicaoDoScript)->not->toBeFalse()
        ->and($posicaoDaFolha)->not->toBeFalse()

        ->and($posicaoDoScript)->toBeLessThan($posicaoDaFolha);
});

it('⚠️ "pausar animações" pula a abertura e a cortina em vez de congelá-las, e tem porta no topo e na gaveta', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toMatch('/<button[^>]*id="animacoes-topo"[^>]*hidden[^>]*aria-haspopup="dialog"/s')
        ->and(substr_count($html, 'data-abre-animacoes'))->toBe(2)

        ->and($html)->not->toContain('id="movimento"')

        ->and($html)->toContain("localStorage.getItem('banda-a11y-pausar-animacoes') === '1'");

    expect(file_get_contents(resource_path('js/Composables/useAcessibilidade.js')))
        ->toContain("const PREFIXO = 'banda-a11y-';")
        ->toContain("lerBooleano('pausar-animacoes')")
        ->toContain('export { pausarAnimacoes };');

    expect(file_get_contents(resource_path('js/site.js')))
        ->toContain("!window.location.hash && !parada('cortina')) {")
        ->toContain("evento.defaultPrevented || parada('cortina')");

    $folha = file_get_contents(resource_path('css/site.css'));

    expect($folha)

        ->toMatch('/html\.a11y-pausar-animacoes \.palco__breu,[^{]*\{\s*display: none !important;/s')

        ->toMatch('/html\.a11y-pausar-animacoes \.gaveta__cta\s*\{[^}]*opacity: 1 !important;/s')
        ->toMatch('/html\.abertura-encerrada \.marca-v3\s*\{[^}]*opacity: 1 !important;/s');
});

it('⚠️ o preflight do widget de acessibilidade não pode ganhar das classes dele', function () {
    $folha = file_get_contents(resource_path('css/a11y.css'));

    expect($folha)->toContain(':where(#acessibilidade) button')
        ->not->toMatch('/^#acessibilidade /m')

        ->toContain('--fg-on-accent:');
});

it('⚠️ cada animação do modal "quais parar" desliga alguma coisa de verdade', function () {
    $catalogo = file_get_contents(resource_path('js/Composables/useAnimacoesDoSite.js'));
    preg_match_all("/chave: '([a-z]+)'/", $catalogo, $m);
    $chaves = $m[1];

    expect($chaves)->toEqualCanonicalizing(['abertura', 'cortina', 'faixa', 'enfeites', 'rolar', 'rolagem']);

    $folha = file_get_contents(resource_path('css/site.css'));
    $script = file_get_contents(resource_path('js/site.js'));

    foreach ($chaves as $chave) {
        expect(str_contains($folha, "html.sem-anim-{$chave} ") || str_contains($script, "parada('{$chave}')"))
            ->toBeTrue("A animação '{$chave}' está no modal e não desliga nada.");
    }

    expect($catalogo)->toContain("export const CHAVE = 'banda.animacoes-paradas';")
        ->and(file_get_contents(resource_path('views/site/layout.blade.php')))
        ->toContain("localStorage.getItem('banda.animacoes-paradas')")
        ->toContain("'sem-anim-' + chave");

    expect(file_get_contents(resource_path('js/Components/AcessibilidadeWidget.vue')))
        ->toContain('escolherAnimacoes: { type: Function, default: null }')
        ->toContain('v-if="props.escolherAnimacoes"');
});
