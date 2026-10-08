<?php

namespace App\Support\Captcha;

interface CaptchaVerifier
{
    public function verify(string $token, ?string $ip = null): bool;

    public function frontendConfig(): array;
}
