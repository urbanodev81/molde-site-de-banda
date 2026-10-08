<?php

declare(strict_types=1);

namespace App\Jobs\Central;

use App\Services\Central\CentralPresenceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class PingCentralPresenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function __construct(
        public string $identificador,
        public string $nome,
        public ?string $pagina,
    ) {}

    public function handle(CentralPresenceClient $cliente): void
    {
        $cliente->ping($this->identificador, $this->nome, $this->pagina);
    }
}
