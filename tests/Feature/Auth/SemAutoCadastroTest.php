<?php

declare(strict_types=1);

it('não tem rota de auto-cadastro', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();

    expect(collect(app('router')->getRoutes())->contains(
        fn ($rota) => $rota->uri() === 'register',
    ))->toBeFalse();
});
