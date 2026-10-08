<?php

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Contracts\Http\Kernel;

it('serve o cookie CSRF com o nome próprio deste sistema', function () {
    $resposta = $this->get('/');

    $nomes = collect($resposta->headers->getCookies())->map->getName();

    expect($nomes)->toContain('XSRF-TOKEN-BANDA')
        ->and($nomes)->not->toContain('XSRF-TOKEN');
});

it('mantém o middleware da casa dentro do grupo web', function () {
    $kernel = app(Kernel::class);

    $grupos = (new ReflectionClass($kernel))->getProperty('middlewareGroups');
    $grupos->setAccessible(true);

    expect($grupos->getValue($kernel)['web'])
        ->toContain(VerifyCsrfToken::class);
});
