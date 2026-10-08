<?php

namespace App\Support\Captcha;

class IndisponivelVerifier implements CaptchaVerifier
{
    public function __construct(private readonly string $motivo) {}

    public function verify(string $token, ?string $ip = null): bool
    {
        return false;
    }

    public function frontendConfig(): array
    {
        return [
            'driver' => 'indisponivel',

            'motivo' => $this->motivo,
        ];
    }
}
