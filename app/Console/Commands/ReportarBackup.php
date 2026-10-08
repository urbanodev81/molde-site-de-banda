<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CentralOutbox;
use App\Services\Central\OutboxDoCentral;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportarBackup extends Command
{
    protected $signature = 'backup:reportar {--status=sucesso : sucesso|falha} {--mensagem= : Mensagem curta em caso de falha}';

    protected $description = 'Relata ao Central o resultado do backup deste sistema';

    public function handle(OutboxDoCentral $outbox): int
    {
        $status = $this->option('status') === 'falha' ? 'falha' : 'sucesso';

        try {
            $registrado = $outbox->registrar(OutboxDoCentral::BACKUP_RELATADO, array_filter([
                'status' => $status,
                'destino' => config('backup.backup.destination.disks')[0] ?? null,
                'tamanho_bytes' => $status === 'sucesso' ? $this->tamanhoDoUltimoBackup() : null,
                'mensagem' => $this->option('mensagem'),
                'ocorreu_em' => now()->toIso8601String(),
            ], fn ($v) => $v !== null && $v !== ''));

            if ($registrado === null) {
                $this->line('Relato não registrado: integração com o Central desligada.');

                return self::SUCCESS;
            }

            $outbox->entregarPendentes();
            $entregue = CentralOutbox::query()->whereKey($registrado->id)->doesntExist();

            $this->line($entregue
                ? "Backup relatado ao Central ({$status})."
                : "Central não recebeu agora; o relato ({$status}) ficou na outbox e será reentregue.");
        } catch (\Throwable $e) {
            Log::warning('Falha ao registrar o relato de backup na outbox.', ['erro' => $e->getMessage()]);
            $this->line('Relato de backup não registrado: '.$e->getMessage());
        }

        return self::SUCCESS;
    }

    private function tamanhoDoUltimoBackup(): ?int
    {
        try {
            $disco = config('backup.backup.destination.disks')[0] ?? 'local';
            $pasta = config('backup.backup.name');

            $arquivos = collect(Storage::disk($disco)->files($pasta))
                ->filter(fn (string $f) => str_ends_with($f, '.zip'));

            if ($arquivos->isEmpty()) {
                return null;
            }

            $recente = $arquivos->sortByDesc(fn (string $f) => Storage::disk($disco)->lastModified($f))->first();

            return Storage::disk($disco)->size($recente);
        } catch (\Throwable) {
            return null;
        }
    }
}
