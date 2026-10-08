<?php

use App\Rules\Captcha;
use App\Support\Captcha\CaptchaVerifier;
use Illuminate\Support\Facades\Validator;

function validaCaptcha(string $token): bool
{
    return Validator::make(
        ['captcha_token' => $token],
        ['captcha_token' => ['required', new Captcha]],
    )->passes();
}

function verificadorQueSempreReprova(): void
{
    app()->instance(CaptchaVerifier::class, new class implements CaptchaVerifier
    {
        public function verify(string $token, ?string $ip = null): bool
        {
            return false;
        }

        public function frontendConfig(): array
        {
            return ['driver' => 'falso'];
        }
    });
}

afterEach(function () {
    $this->app['env'] = 'testing';
});

test('token de bypass passa fora de produção quando configurado', function () {
    $this->app['env'] = 'staging';
    config()->set('services.captcha.qa_bypass_token', 'token-smoke-qa');
    verificadorQueSempreReprova();

    expect(validaCaptcha('token-smoke-qa'))->toBeTrue();

    expect(validaCaptcha('outro-token'))->toBeFalse();
});

test('em produção o bypass é ignorado mesmo configurado', function () {
    $this->app['env'] = 'production';
    config()->set('services.captcha.qa_bypass_token', 'token-smoke-qa');
    verificadorQueSempreReprova();

    expect(validaCaptcha('token-smoke-qa'))->toBeFalse();
});

test('sem a env configurada não existe caminho de bypass', function () {
    $this->app['env'] = 'staging';
    config()->set('services.captcha.qa_bypass_token', null);
    verificadorQueSempreReprova();

    expect(validaCaptcha(''))->toBeFalse();
    expect(validaCaptcha('qualquer'))->toBeFalse();
});

test('o bypass não é comparado de forma vulnerável a timing', function () {
    $fonte = file_get_contents(app_path('Rules/Captcha.php'));

    expect($fonte)->toContain('hash_equals(');
});
