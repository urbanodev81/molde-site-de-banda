<?php

namespace App\Providers;

use App\Support\Captcha\AltchaVerifier;
use App\Support\Captcha\CaptchaVerifier;
use App\Support\Captcha\IndisponivelVerifier;
use App\Support\Captcha\NullVerifier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class CaptchaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CaptchaVerifier::class, function () {
            $driver = (string) config('services.captcha.driver', 'altcha');

            if ($this->app->environment('testing')) {
                return new NullVerifier;
            }

            if ($this->app->environment('local') && env('CAPTCHA_DRIVER') === null) {
                return new NullVerifier;
            }

            return match ($driver) {
                'altcha' => $this->altcha(),

                'null' => $this->desligado('CAPTCHA_DRIVER=null'),
                default => $this->desligado("CAPTCHA_DRIVER desconhecido: '{$driver}'"),
            };
        });
    }

    private function altcha(): CaptchaVerifier
    {
        $chave = (string) config('services.captcha.hmac_key');

        if ($chave === '') {
            return $this->desligado('CAPTCHA_HMAC_KEY vazia (desafio sairia sem assinatura)');
        }

        return new AltchaVerifier($chave, (int) config('services.captcha.cost', 50_000));
    }

    private function desligado(string $motivo): CaptchaVerifier
    {
        if ($this->app->environment('production')) {
            throw new RuntimeException(
                "Captcha desligado em produção — {$motivo}. "
                .'Configure CAPTCHA_HMAC_KEY (ou CAPTCHA_DRIVER) antes de subir.'
            );
        }

        Log::error("Captcha indisponível neste ambiente — {$motivo}. Os formulários públicos vão RECUSAR envios até isso ser corrigido.");

        return new IndisponivelVerifier($motivo);
    }
}
