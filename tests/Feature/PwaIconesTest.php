<?php

declare(strict_types=1);

test('todo ícone do manifesto existe e tem o tamanho que declara', function () {
    $manifesto = $this->get(route('pwa.manifest'))->assertOk()->json();

    $icones = collect($manifesto['icons']);

    expect($icones->pluck('sizes'))->toContain('192x192', '512x512')
        ->and($icones->pluck('purpose'))->toContain('maskable');

    foreach ($manifesto['icons'] as $icone) {
        $arquivo = public_path(ltrim($icone['src'], '/'));

        expect($arquivo)->toBeReadableFile();

        [$largura, $altura] = getimagesize($arquivo);
        expect("{$largura}x{$altura}")->toBe($icone['sizes']);
    }
});

test('tudo que o shell do service worker guarda existe', function () {
    $sw = file_get_contents(resource_path('pwa/sw.js'));

    preg_match('/const SHELL = \[(.*?)\];/s', $sw, $bloco);
    expect($bloco)->not->toBeEmpty('não achei a lista SHELL no sw.js');

    preg_match_all("/'(\/[^']+\.[a-z]+)'/", $bloco[1], $arquivos);
    expect($arquivos[1])->not->toBeEmpty();

    foreach ($arquivos[1] as $caminho) {
        expect(public_path(ltrim($caminho, '/')))->toBeReadableFile();
    }
});

test('os ícones que o painel declara no <head> existem', function () {
    foreach (['favicon.ico', 'favicon.svg', 'apple-touch-icon.png'] as $arquivo) {
        expect(public_path($arquivo))->toBeReadableFile();
    }
});
