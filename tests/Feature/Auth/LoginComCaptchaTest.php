<?php

declare(strict_types=1);

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;

beforeEach(fn () => $this->seed(PerfisESeguranca::class));

function contaParaCaptcha(): User
{
    $usuario = User::create([
        'name' => 'Conta do captcha',
        'email' => 'captcha@login.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);

    $usuario->syncRoles([Perfis::ADMINISTRADOR]);

    return $usuario;
}

test('em ambiente publicado o login exige captcha', function () {
    app()['env'] = 'staging';
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $usuario = contaParaCaptcha();

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'senha-de-teste-123'])
        ->assertSessionHasErrors('captcha_token');

    $this->assertGuest();

    app()['env'] = 'testing';
});

test('em testing o login não exige captcha (fail-open de dev)', function () {
    $usuario = contaParaCaptcha();

    $this->post(route('login'), ['email' => $usuario->email, 'password' => 'senha-de-teste-123'])
        ->assertSessionDoesntHaveErrors('captcha_token');

    $this->assertAuthenticated();
});
