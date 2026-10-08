<?php

namespace App\Support\Security;

use App\Support\Alertas\EmailFormatado;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

use function Illuminate\Support\defer;

class AlertaDeSeguranca
{
    public static function avisar(
        string $evento,
        string $titulo,
        string $descricao,
        array $contexto = [],
        ?string $freio = null,
        int $minutos = 15,
    ): void {
        if ($freio !== null && ! Cache::add('alerta-seguranca:'.$evento.':'.$freio, true, now()->addMinutes($minutos))) {
            return;
        }

        defer(function () use ($evento, $titulo, $descricao, $contexto) {
            try {
                $destino = config('security_alerts.alert_email');
                if (! $destino) {
                    return;
                }

                $servico = (string) config('security_alerts.service', config('app.name'));
                EmailFormatado::para($destino)
                    ->assunto("[{$servico}] {$titulo}")
                    ->titulo($titulo)
                    ->paragrafo($descricao)
                    ->campo('Evento', $evento)
                    ->campos($contexto)
                    ->remetente($servico)
                    ->assinatura("Aviso automático — {$servico}")
                    ->enviar();
            } catch (Throwable $e) {
                Log::error('[alerta-seguranca] falha ao avisar: '.$e->getMessage());
            }
        });
    }
}
