<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoInteracao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratacaoInteracao extends Model
{
    use HasFactory;

    protected $table = 'contratacao_interacoes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tipo' => TipoInteracao::class,
            'ocorrido_em' => 'datetime',
        ];
    }

    public function contratacao(): BelongsTo
    {
        return $this->belongsTo(Contratacao::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function nomeDoAutor(): string
    {
        return $this->user_nome ?? $this->autor?->name ?? 'sistema';
    }
}
