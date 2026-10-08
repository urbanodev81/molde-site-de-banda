<?php

namespace App\Support\Alertas;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class AlertaDeErro
{
    public const FREIO_MINUTOS = 5;

    public static function avisar(Throwable $e): void
    {
        try {
            $assinatura = $e::class.'|'.$e->getFile().':'.$e->getLine().'|'.$e->getMessage();
            if (! Cache::add('alerta-erro:'.md5($assinatura), true, now()->addMinutes(self::FREIO_MINUTOS))) {
                return;
            }

            $destino = config('security_alerts.alert_email');
            if (! $destino) {
                return;
            }

            $servico = (string) config('security_alerts.service', config('app.name'));
            $url = app()->runningInConsole() ? 'cli' : request()->fullUrl();
            EmailFormatado::para($destino)
                ->assunto("[{$servico}] Erro 500: ".mb_strimwidth($e->getMessage(), 0, 80, '…'))
                ->titulo('Erro não tratado')
                ->paragrafo("Um erro não tratado aconteceu em {$servico}.")
                ->campos([
                    'URL' => $url,
                    'Erro' => $e::class.': '.$e->getMessage(),
                    'Onde' => $e->getFile().':'.$e->getLine(),
                ])
                ->paragrafo('Repetições do mesmo erro nos próximos '.self::FREIO_MINUTOS.' min não geram outro e-mail.')
                ->remetente($servico)
                ->assinatura("Aviso automático — {$servico}")
                ->enviar();
        } catch (Throwable $falha) {
            Log::error('[alerta-erro] falha ao avisar: '.$falha->getMessage());
        }
    }
}
