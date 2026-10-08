<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CentralOutbox extends Model
{
    use HasUuids;

    protected $table = 'central_outbox';

    protected $fillable = ['tipo', 'payload', 'ocorreu_em', 'proxima_tentativa_em'];

    protected $attributes = ['tentativas' => 0];

    protected $casts = [
        'payload' => 'array',
        'ocorreu_em' => 'datetime',
        'proxima_tentativa_em' => 'datetime',
        'descartado_em' => 'datetime',
        'tentativas' => 'integer',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }
}
