<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoliticaRetencao extends Model
{
    protected $table = 'politicas_retencao';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'meses' => 'integer',
            'nunca_expurgar' => 'boolean',
        ];
    }
}
