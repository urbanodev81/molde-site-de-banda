<?php

use App\Support\Captcha\AltchaVerifier;
use App\Support\Captcha\CaptchaVerifier;
use App\Support\Captcha\NullVerifier;

it('emite o desafio quando o driver é o Altcha', function () {
    app()->instance(CaptchaVerifier::class, new AltchaVerifier('chave-de-teste'));

    $this->get('/captcha/desafio')
        ->assertOk()
        ->assertJsonStructure(['algorithm', 'challenge', 'maxnumber', 'salt', 'signature']);
});

it('nunca deixa o desafio ser guardado em cache', function () {
    app()->instance(CaptchaVerifier::class, new AltchaVerifier('chave-de-teste'));

    expect($this->get('/captcha/desafio')->headers->get('Cache-Control'))->toContain('no-store');
});

it('dá 404 quando o driver não emite desafio', function () {
    app()->instance(CaptchaVerifier::class, new NullVerifier);

    $this->get('/captcha/desafio')->assertNotFound();
});

it('o formulário do site aponta para a rota que existe', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('<altcha-widget')
        ->and($html)->toContain('challenge="/captcha/desafio"')
        ->and($html)->toContain('name="captcha_token"')
        ->and($html)->not->toContain('challengeurl');
});
