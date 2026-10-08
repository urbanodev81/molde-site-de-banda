<?php

namespace App\Support\Captcha;

class NullVerifier implements CaptchaVerifier
{
    public function verify(string $token, ?string $ip = null): bool
    {
        return true;
    }

    public function frontendConfig(): array
    {
        return ['driver' => 'null'];
    }
}
