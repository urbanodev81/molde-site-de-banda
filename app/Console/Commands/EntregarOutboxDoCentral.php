<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Central\OutboxDoCentral;
use Illuminate\Console\Command;

class EntregarOutboxDoCentral extends Command
{
    protected $signature = 'central:entregar-outbox';

    protected $description = 'Entrega ao Central os avisos pendentes na outbox';

    public function handle(OutboxDoCentral $outbox): int
    {
        $r = $outbox->entregarPendentes();
        $limpas = $outbox->limpar();

        $this->line("Outbox do Central: {$r['entregues']} entregue(s), {$r['adiadas']} adiada(s), {$r['descartadas']} descartada(s), {$limpas} antiga(s) apagada(s).");

        return self::SUCCESS;
    }
}
