<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VisitaDiaria extends Model
{
    protected $table = 'visitas_diarias';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'visitas' => 'integer',
        ];
    }

    public static function somarUma(Carbon $dia, string $caminho, ?string $rota): void
    {
        DB::statement(
            'insert into visitas_diarias (data, caminho, rota, visitas, created_at, updated_at)
             values (?, ?, ?, 1, now(), now())
             on conflict (data, caminho)
             do update set visitas = visitas_diarias.visitas + 1, updated_at = now()',
            [$dia->toDateString(), $caminho, $rota],
        );
    }
}
