<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Depoimento extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $attributes = ['autorizado' => false, 'publicado' => false];

    protected function casts(): array
    {
        return [
            'autorizado' => 'boolean',
            'publicado' => 'boolean',
            'ocorrido_em' => 'date',
        ];
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function scopePublicaveis(Builder $query): Builder
    {
        return $query->where('autorizado', true)->where('publicado', true)->orderBy('ordem');
    }
}
