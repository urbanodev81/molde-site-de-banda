<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Models\CentralOutbox;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class OutboxDoCentral
{
    public const BACKUP_RELATADO = 'backup.relatado';

    public const LOGIN_REPORTADO = 'login.reportado';

    private const ENDPOINTS = [
        self::BACKUP_RELATADO => '/api/backups/report',
        self::LOGIN_REPORTADO => '/api/access-logs/login',
    ];

    private const ESPERAS = [1, 5, 15, 60];

    private const VALIDADE_EM_HORAS = 72;

    private const DIAS_GUARDANDO_DESCARTADO = 30;

    public function __construct(private readonly CentralPresenceClient $client) {}

    public function registrar(string $tipo, array $payload, ?Carbon $ocorreuEm = null): ?CentralOutbox
    {
        if (! isset(self::ENDPOINTS[$tipo])) {
            throw new \InvalidArgumentException("Tipo de outbox desconhecido: {$tipo}");
        }

        if (! $this->client->isConfigured()) {
            return null;
        }

        return CentralOutbox::create([
            'tipo' => $tipo,
            'payload' => $payload,
            'ocorreu_em' => $ocorreuEm ?? now(),
            'proxima_tentativa_em' => now(),
        ]);
    }

    public function entregarPendentes(int $limite = 50): array
    {
        $resultado = ['entregues' => 0, 'adiadas' => 0, 'descartadas' => 0];

        if (! $this->client->isConfigured()) {
            return $resultado;
        }

        $pendentes = CentralOutbox::query()
            ->whereNull('descartado_em')
            ->where('proxima_tentativa_em', '<=', now())
            ->orderBy('proxima_tentativa_em')
            ->limit($limite)
            ->get();

        foreach ($pendentes as $linha) {
            if (! $this->reservar($linha)) {
                continue;
            }

            $desfecho = $this->entregar($linha);
            $resultado[$desfecho]++;

            if ($desfecho === 'adiadas') {
                break;
            }
        }

        return $resultado;
    }

    public function limpar(): int
    {
        return CentralOutbox::query()
            ->where('descartado_em', '<', now()->subDays(self::DIAS_GUARDANDO_DESCARTADO))
            ->delete();
    }

    private function reservar(CentralOutbox $linha): bool
    {
        $espera = self::ESPERAS[min($linha->tentativas, count(self::ESPERAS) - 1)];

        return CentralOutbox::query()
            ->whereKey($linha->id)
            ->whereNull('descartado_em')
            ->where('tentativas', $linha->tentativas)
            ->update([
                'tentativas' => $linha->tentativas + 1,
                'proxima_tentativa_em' => now()->addMinutes($espera),
            ]) === 1;
    }

    private function entregar(CentralOutbox $linha): string
    {
        if ($linha->ocorreu_em->lt(now()->subHours(self::VALIDADE_EM_HORAS))) {
            return $this->descartar($linha, 'Venceu sem entrega: '.($linha->ultimo_erro ?? 'sem erro registrado'));
        }

        try {
            $resposta = $this->client->entregarComChave(self::ENDPOINTS[$linha->tipo], $linha->payload, $linha->id);
        } catch (\Throwable $e) {
            return $this->adiar($linha, $e->getMessage());
        }

        if ($resposta->successful()) {
            $linha->delete();

            return 'entregues';
        }

        if ($resposta->unprocessableEntity()) {
            return $this->descartar($linha, 'Central recusou (422): '.mb_substr($resposta->body(), 0, 500));
        }

        return $this->adiar($linha, "HTTP {$resposta->status()}");
    }

    private function adiar(CentralOutbox $linha, string $erro): string
    {
        CentralOutbox::query()->whereKey($linha->id)->update(['ultimo_erro' => mb_substr($erro, 0, 1000)]);

        return 'adiadas';
    }

    private function descartar(CentralOutbox $linha, string $motivo): string
    {
        CentralOutbox::query()->whereKey($linha->id)->update([
            'descartado_em' => now(),
            'ultimo_erro' => mb_substr($motivo, 0, 1000),
        ]);

        Log::warning('Outbox do Central: fato descartado sem entrega.', [
            'id' => $linha->id,
            'tipo' => $linha->tipo,
            'motivo' => $motivo,
        ]);

        return 'descartadas';
    }
}
