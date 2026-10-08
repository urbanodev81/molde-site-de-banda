<?php

namespace App\Support\Captcha;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\ChallengeOptions;
use AltchaOrg\Altcha\Hasher\Algorithm;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AltchaVerifier implements CaptchaVerifier
{
    private const EXPIRA_EM_SEGUNDOS = 600;

    public function __construct(
        private readonly string $hmacKey,
        private readonly int $custo = 50_000,
    ) {}

    public function criarDesafio(): array
    {
        $desafio = $this->altcha()->createChallenge(new ChallengeOptions(
            algorithm: Algorithm::SHA256,
            maxNumber: $this->custo,
            expires: new \DateTimeImmutable('+'.self::EXPIRA_EM_SEGUNDOS.' seconds'),
        ));

        return [
            'algorithm' => $desafio->algorithm,
            'challenge' => $desafio->challenge,
            'maxnumber' => $desafio->maxNumber,
            'salt' => $desafio->salt,
            'signature' => $desafio->signature,
        ];
    }

    public function verify(string $token, ?string $ip = null): bool
    {
        if ($token === '') {
            return false;
        }

        try {
            $valido = $this->altcha()->verifySolution($token, checkExpires: true);
        } catch (\Throwable $e) {
            return false;
        }

        if (! $valido) {
            return false;
        }

        return $this->queimar($token);
    }

    public function frontendConfig(): array
    {
        return [
            'driver' => 'altcha',

            'challenge_url' => '/captcha/desafio',
        ];
    }

    private function queimar(string $token): bool
    {
        $payload = json_decode((string) base64_decode($token, true), true);
        $desafio = $payload['challenge'] ?? null;

        if (! is_string($desafio) || $desafio === '') {
            return false;
        }

        $chave = 'captcha:usado:'.hash('sha256', $desafio);

        if (Cache::add($chave, true, self::EXPIRA_EM_SEGUNDOS)) {
            return true;
        }

        Log::warning('Captcha: desafio válido reapresentado (replay recusado).', [
            'chave' => $chave,
        ]);

        return false;
    }

    private function altcha(): Altcha
    {
        return new Altcha($this->hmacKey);
    }
}
