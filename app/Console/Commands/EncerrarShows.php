<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\StatusShow;
use App\Models\Show;
use Illuminate\Console\Command;

class EncerrarShows extends Command
{
    protected $signature = 'shows:encerrar';

    protected $description = 'Marca como realizado todo show confirmado cuja data já passou.';

    public function handle(): int
    {
        $encerrados = 0;

        Show::query()
            ->where('status', StatusShow::Confirmado->value)
            ->where('comeca_em', '<', now())
            ->cursor()
            ->each(function (Show $show) use (&$encerrados): void {
                $show->update(['status' => StatusShow::Realizado]);
                $encerrados++;
            });

        $this->info("Shows encerrados: {$encerrados}.");

        return self::SUCCESS;
    }
}
