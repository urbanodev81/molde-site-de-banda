<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Models\Contratacao;
use App\Models\PoliticaRetencao;
use App\Support\VinculosDeMaterial;
use Illuminate\Console\Command;

class ExpurgarPorRetencao extends Command
{
    protected $signature = 'lgpd:expurgo {--dry-run : Só conta o que seria apagado}';

    protected $description = 'Apaga o que passou do prazo de retenção escrito em politicas_retencao.';

    public function handle(): int
    {
        $simulacao = (bool) $this->option('dry-run');

        foreach (PoliticaRetencao::query()->get() as $politica) {
            if ($politica->nunca_expurgar) {
                $this->line("· {$politica->recurso}: protegido (nunca_expurgar).");

                continue;
            }

            $limite = now()->subMonths($politica->meses);

            $consulta = match ($politica->recurso) {
                'auditoria' => Auditoria::query()->where('ocorreu_em', '<', $limite),

                'contratacao' => Contratacao::withTrashed()->where('created_at', '<', $limite),

                'contratacao_interacao' => null,

                default => null,
            };

            if ($consulta === null) {
                continue;
            }

            $quantos = (clone $consulta)->count();

            if ($simulacao) {
                $this->line("· {$politica->recurso}: {$quantos} linha(s) além de {$politica->meses} meses.");

                continue;
            }

            if ($politica->recurso === 'contratacao') {
                $materiais = VinculosDeMaterial::expurgarDosPedidos((clone $consulta)->pluck('id')->all());
                $materiais && $this->info("· material de pedido: {$materiais} arquivo(s) apagado(s).");
            }

            $politica->recurso === 'contratacao'
                ? $consulta->forceDelete()
                : $consulta->delete();

            $this->info("· {$politica->recurso}: {$quantos} linha(s) apagada(s).");
        }

        return self::SUCCESS;
    }
}
