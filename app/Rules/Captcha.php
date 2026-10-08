<?php

namespace App\Rules;

use App\Support\Captcha\CaptchaVerifier;
use App\Support\Captcha\IndisponivelVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Captcha implements ValidationRule
{
    public static function rules(): array
    {
        return app()->environment(['local', 'testing'])
            ? ['nullable', 'string']
            : ['required', 'string', new self];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('A verificação de segurança é obrigatória.');

            return;
        }

        $bypass = (string) config('services.captcha.qa_bypass_token');
        if ($bypass !== '' && ! app()->environment('production') && hash_equals($bypass, $value)) {
            return;
        }

        $verifier = app(CaptchaVerifier::class);

        if ($verifier instanceof IndisponivelVerifier) {
            $fail('A verificação de segurança está indisponível no momento. Avise a equipe.');

            return;
        }

        if (! $verifier->verify($value, request()->ip())) {
            $fail('Falha na verificação de segurança. Recarregue a página e tente novamente.');
        }
    }
}
