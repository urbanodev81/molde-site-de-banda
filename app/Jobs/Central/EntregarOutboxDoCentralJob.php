<?php

declare(strict_types=1);

namespace App\Jobs\Central;

use App\Services\Central\OutboxDoCentral;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class EntregarOutboxDoCentralJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function handle(OutboxDoCentral $outbox): void
    {
        $outbox->entregarPendentes();
    }
}
