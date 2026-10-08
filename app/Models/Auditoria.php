<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Auditoria extends Model
{
    protected $table = 'auditorias';

    protected $guarded = ['id'];

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['ocorreu_em' => 'datetime'];
    }

    public function auditavel(): MorphTo
    {
        return $this->morphTo();
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function nomeDoAutor(): string
    {
        return $this->user_nome ?? $this->autor?->name ?? 'sistema';
    }

    public function tipoLegivel(): string
    {
        return class_basename((string) $this->auditavel_type);
    }
}
