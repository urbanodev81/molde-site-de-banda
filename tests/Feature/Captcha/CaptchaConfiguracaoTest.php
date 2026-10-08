<?php

use App\Support\Captcha\AltchaVerifier;
use App\Support\Captcha\CaptchaVerifier;
use App\Support\Captcha\IndisponivelVerifier;
use App\Support\Captcha\NullVerifier;

function verificadorEm(string $ambiente, array $config = []): CaptchaVerifier
{
    putenv('CAPTCHA_DRIVER');
    unset($_ENV['CAPTCHA_DRIVER'], $_SERVER['CAPTCHA_DRIVER']);

    config(array_merge(['services.captcha' => [
        'driver' => 'altcha',
        'hmac_key' => 'chave',
        'cost' => 50_000,
    ]], $config));

    app()->detectEnvironment(fn () => $ambiente);
    app()->forgetInstance(CaptchaVerifier::class);

    return app(CaptchaVerifier::class);
}

beforeEach(function () {
    $this->driverOriginal = $_SERVER['CAPTCHA_DRIVER'] ?? $_ENV['CAPTCHA_DRIVER'] ?? getenv('CAPTCHA_DRIVER') ?: null;
});

afterEach(function () {
    if ($this->driverOriginal !== null) {
        putenv('CAPTCHA_DRIVER='.$this->driverOriginal);
        $_ENV['CAPTCHA_DRIVER'] = $this->driverOriginal;
        $_SERVER['CAPTCHA_DRIVER'] = $this->driverOriginal;
    }

    app()->detectEnvironment(fn () => 'testing');
    app()->forgetInstance(CaptchaVerifier::class);
});

it('em produção ESTOURA quando a chave HMAC está vazia', function () {
    verificadorEm('production', ['services.captcha.hmac_key' => '']);
})->throws(RuntimeException::class, 'Captcha desligado em produção');

it('em produção ESTOURA com CAPTCHA_DRIVER=null', function () {
    verificadorEm('production', ['services.captcha.driver' => 'null']);
})->throws(RuntimeException::class, 'Captcha desligado em produção');

it('em produção ESTOURA com driver desconhecido', function () {
    verificadorEm('production', ['services.captcha.driver' => 'altchaa']);
})->throws(RuntimeException::class, 'Captcha desligado em produção');

it('em produção configurado direito entrega o Altcha', function () {
    expect(verificadorEm('production'))->toBeInstanceOf(AltchaVerifier::class);
});

it('fora de produção não estoura, mas RECUSA — não aprova', function () {
    expect(verificadorEm('staging', ['services.captcha.hmac_key' => '']))
        ->toBeInstanceOf(IndisponivelVerifier::class);
});

it('em local sem configuração aprova — e é de propósito', function () {
    expect(verificadorEm('local', ['services.captcha.hmac_key' => '']))
        ->toBeInstanceOf(NullVerifier::class);
});

it('em testing nunca verifica, mesmo configurado', function () {
    expect(app(CaptchaVerifier::class))->toBeInstanceOf(NullVerifier::class);
});
