<?php

declare(strict_types=1);

use App\Models\Integrante;

it('as prévias abrem em desenvolvimento', function () {
    $this->get('/previa/kit')->assertOk()->assertSee('Versão 2', false);
    $this->get('/previa/v3')->assertOk()->assertSee('Versão 3', false);
});

it('⚠️ as TRÊS versões estão à vista em todas as telas, e cada uma acende a sua', function () {
    $telas = [
        '/' => 'Versão 1',
        '/previa/kit' => 'Versão 2',
        '/previa/v3' => 'Versão 3',
    ];

    foreach ($telas as $url => $atual) {
        $html = $this->get($url)->assertOk()->getContent();

        foreach (['site.home' => route('site.home'), 'kit' => route('site.previa', 'kit'), 'v3' => route('site.previa', 'v3')] as $destino) {
            expect($html)->toContain('href="'.$destino.'"');
        }

        expect(substr_count($html, 'aria-current="page"'))->toBeGreaterThanOrEqual(1);

        preg_match_all('/<a class="versoes__v"[^>]*>(.*?)<\/a>/s', $html, $pastilhas);
        expect($pastilhas[0])->toHaveCount(3);

        $acesas = array_values(array_filter($pastilhas[0], fn ($p) => str_contains($p, 'aria-current')));
        expect($acesas)->toHaveCount(1)
            ->and($acesas[0])->toContain($atual);
    }
});

it('⚠️ a barra amarela saiu, e não sobrou nada dela', function () {
    $html = $this->get('/previa/kit')->assertOk()->getContent();

    expect($html)->not->toContain('barra-previa')
        ->and($html)->not->toContain('--barra-previa-h');
});

it('a prévia troca SÓ o topo — o resto da home é o mesmo', function () {
    foreach ([[1, 19, 33, 0], [2, 44, 19, 13], [3, 59, 22, 1]] as [$i, $esq, $larg, $base]) {
        Integrante::create([
            'nome' => "Integrante $i (a definir)",
            'ordem' => $i,
            'ativa' => true,
            'recorte_path' => "sementes/integrante-0$i.png",
            'palco_esquerda' => $esq,
            'palco_largura' => $larg,
            'palco_base' => $base,
        ]);
    }

    $home = $this->get('/')->getContent();
    $previa = $this->get('/previa/kit')->getContent();

    $secoes = function (string $html): array {
        preg_match_all('/<section[^>]+id="([a-z-]+)"/', $html, $achados);
        sort($achados[1]);

        return $achados[1];
    };

    expect($secoes($previa))->toBe($secoes($home))
        ->and($secoes($home))->not->toBeEmpty();

    expect($previa)->toContain('elenco--no-palco')
        ->and($home)->not->toContain('elenco--no-palco');

    foreach ([$home, $previa] as $html) {
        expect($html)->toContain('class="elenco__m"')
            ->and($html)->toMatch('/--esq:\d+%;--larg:\d+%;--base:\d+%;--i:\d+/');
    }

    expect($previa)->not->toContain('elenco__reflexo');

    $v3 = $this->get('/previa/v3')->getContent();

    expect($secoes($v3))->toBe($secoes($home));

    expect($v3)->toContain('elenco--v3')
        ->and($home)->not->toContain('elenco--v3')
        ->and($v3)->not->toContain('elenco--no-palco');

    expect($v3)->toContain('class="elenco__m"')
        ->and($v3)->toMatch('/--esq:\d+%;--larg:\d+%;--base:\d+%;--i:\d+/');

    expect($v3)->not->toContain('elenco__reflexo');
});

it('variante que não existe é 404', function () {
    $this->get('/previa/inventada')->assertNotFound();
});

it('⚠️ a versão 2 saiu, e a rota dela morreu junto', function () {
    $this->get('/previa/gemini')->assertNotFound();
});

it('⚠️ fora de desenvolvimento, a comparação é 404 por padrão', function () {
    app()->detectEnvironment(fn () => 'production');
    config()->set('site.comparacao_de_layout', false);

    $this->get('/previa/kit')->assertNotFound();
    $this->get('/previa/v3')->assertNotFound();
});

it('⚠️ e a home NÃO oferece a segunda versão quando a comparação está desligada', function () {
    app()->detectEnvironment(fn () => 'production');
    config()->set('site.comparacao_de_layout', false);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('versoes__v', false)
        ->assertDontSee(route('site.previa', 'kit'), false);
});

it('a chave explícita liga a comparação fora de desenvolvimento', function () {
    app()->detectEnvironment(fn () => 'production');
    config()->set('site.comparacao_de_layout', true);

    $this->get('/previa/kit')->assertOk()->assertSee('Versão 2', false);
    $this->get('/')->assertOk()->assertSee('Versão 1', false);
});

it('⚠️ o menu da prévia rola NA prévia — não devolve quem compara para a versão 1', function () {
    $html = $this->get('/previa/kit')->assertOk()->getContent();

    foreach (['agenda', 'duvidas', 'contato'] as $secao) {
        expect($html)->toContain('href="#'.$secao.'"')
            ->and($html)->not->toContain('href="'.route('site.home').'#'.$secao.'"');
    }
});

it('e as páginas que NÃO têm as âncoras continuam com o link absoluto', function () {
    $html = $this->get('/repertorio')->assertOk()->getContent();

    expect($html)->toContain('href="'.route('site.home').'#agenda"');
});

it('⚠️ os dois recipientes da marca convivem na v3, num alternador', function () {
    $v3 = $this->get('/previa/v3')->assertOk()->getContent();

    expect($v3)->toContain('id="v3-arco"')
        ->and($v3)->toContain('id="v3-disco"')

        ->and(substr_count($v3, 'name="v3-recipiente"'))->toBe(2);
});

it('a arte da variante é a composta, e existe', function () {
    $this->get('/previa/kit')->assertOk()->assertSee('sementes/palco-kit.webp', false);

    foreach (['palco-kit.webp', 'palco-kit-900.webp', 'palco-kit-desfoque.jpg'] as $arquivo) {
        expect(public_path('sementes/'.$arquivo))->toBeFile();
    }
});

it('⚠️ na 2v o elenco é ancorado pelo CENTRO, e o fundo é o da 1v', function () {
    $folha = file_get_contents(resource_path('css/previa-kit.css'));

    expect($folha)
        ->toContain('--meio: calc(var(--esq) + var(--larg) / 2)')

        ->and($folha)->not->toMatch('/left:\s*calc\(50% \+ \(var\(--esq\) - 50%\)/');

    expect($folha)->not->toMatch('/^\s*\.sangria/m');

    $kit = $this->get('/previa/kit')->assertOk()->getContent();

    expect($kit)->toContain('sementes/palco-largo-desfoque.jpg')
        ->and($kit)->not->toContain('palco-kit-desfoque.jpg')
        ->and($kit)->not->toContain('sangria--kit');
});

it('a variante carrega a folha dela, e a da versão 2 não sobrou em lugar nenhum', function () {
    $kit = $this->get('/previa/kit')->assertOk()->getContent();
    $v3 = $this->get('/previa/v3')->assertOk()->getContent();
    $home = $this->get('/')->assertOk()->getContent();

    expect($kit)->toContain('previa-kit')
        ->and($kit)->not->toContain('previa-v3')
        ->and($kit)->not->toContain('previa-gemini');

    expect($v3)->toContain('previa-v3')
        ->and($v3)->not->toContain('previa-kit');

    expect($home)->not->toContain('previa-kit')
        ->and($home)->not->toContain('previa-v3')
        ->and($home)->not->toContain('previa-gemini');
});

it('as peças da v3 existem, e a marca é servida SEPARADA da cena', function () {
    $v3 = $this->get('/previa/v3')->assertOk()->getContent();

    expect($v3)->toContain('sementes/palco-v3.webp')
        ->and($v3)->toContain('sementes/lettering-1400.webp')

        ->and($v3)->toContain('marca-v3__tipo');

    foreach ([
        'palco-v3.webp', 'palco-v3-900.webp', 'palco-v3-desfoque.jpg',
        'lettering-1400.webp', 'lettering-700.webp',
    ] as $arquivo) {
        expect(public_path('sementes/'.$arquivo))->toBeFile();
    }
});

it('⚠️ a galáxia NÃO é imagem — o recorte que existiu vinha com fantasma', function () {
    $v3 = $this->get('/previa/v3')->assertOk()->getContent();

    expect($v3)->not->toContain('galaxia')
        ->and($v3)->not->toContain('--galaxia');

    expect(public_path('sementes/galaxia-512.webp'))->not->toBeFile();
});

it('⚠️ no celular as três versões servem artes DIFERENTES, e elas existem', function () {
    $fonteDoCelular = function (string $url): string {
        $html = $this->get($url)->assertOk()->getContent();
        $palco = substr($html, strpos($html, 'class="palco-area'));

        expect(preg_match('/<source srcset="([^"]+)"/', $palco, $m))->toBe(1, "sem fonte de celular em {$url}");

        return $m[1];
    };

    $fontes = [
        '/' => $fonteDoCelular('/'),
        '/previa/kit' => $fonteDoCelular('/previa/kit'),
        '/previa/v3' => $fonteDoCelular('/previa/v3'),
    ];

    expect(array_unique($fontes))->toHaveCount(3);

    foreach ($fontes as $url => $srcset) {
        preg_match_all('#/sementes/([\w.-]+\.webp)#', $srcset, $arquivos);
        expect($arquivos[1])->not->toBeEmpty();
        foreach ($arquivos[1] as $arquivo) {
            expect(public_path("sementes/{$arquivo}"))->toBeFile("{$url} aponta para {$arquivo}, que não existe");
        }
    }
});
