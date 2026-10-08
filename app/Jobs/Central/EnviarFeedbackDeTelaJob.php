<?php

declare(strict_types=1);

namespace App\Jobs\Central;

use App\Services\Central\CentralPresenceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class EnviarFeedbackDeTelaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(public array $dados) {}

    public function handle(CentralPresenceClient $cliente): void
    {
        $cliente->enviarFeedbackDeTela($this->dados);
    }
}
