<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.captcha.hmac_key' => 'chave-de-teste-nao-usar-em-producao']);
});

$telas = [
    'site: home' => '/',
    'painel: login' => '/login',
    'painel: recuperar senha' => '/forgot-password',
    'offline (PWA)' => '/offline',
];

foreach ($telas as $rotulo => $caminho) {
    test("{$rotulo}: sem erro de JS e sem imagem quebrada", function () use ($caminho) {
        visit($caminho)
            ->assertNoJavaScriptErrors()
            ->assertScript(
                'Array.from(document.images).filter((i) => i.getAttribute("src") && i.complete && i.naturalWidth === 0).map((i) => i.currentSrc || i.src).join(" ")',
                ''
            );
    });

    test("{$rotulo}: cabe em 360px sem rolagem horizontal", function () use ($caminho) {
        visit($caminho)
            ->on()->mobile()
            ->assertScript(
                'document.documentElement.scrollWidth <= document.documentElement.clientWidth',
                true
            );
    });

    test("{$rotulo}: renderiza nos dois temas", function () use ($caminho) {
        visit($caminho)->inLightMode()->assertNoJavaScriptErrors();
        visit($caminho)->inDarkMode()->assertNoJavaScriptErrors();
    });
}
