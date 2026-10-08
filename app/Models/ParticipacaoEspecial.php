<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use App\Support\TemUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParticipacaoEspecial extends Model
{
    use Auditable;
    use SoftDeletes;
    use TemUuid;

    protected $table = 'participacoes_especiais';

    protected $guarded = ['id'];

    protected $attributes = ['publicada' => true];

    protected function casts(): array
    {
        return [
            'publicada' => 'boolean',
            'autorizacao_imagem_em' => 'date',
        ];
    }

    public function shows(): BelongsToMany
    {
        return $this->belongsToMany(Show::class, 'participacao_especial_show');
    }

    public function scopePublicaveis(Builder $query): Builder
    {
        return $query->where('publicada', true)
            ->whereNotNull('autorizacao_imagem_em')
            ->orderBy('ordem')->orderBy('nome');
    }

    public function motivosParaNaoAparecer(): array
    {
        $motivos = [];

        if (! $this->publicada) {
            $motivos[] = 'está marcada como não publicada';
        }

        if ($this->autorizacao_imagem_em === null) {
            $motivos[] = 'sem autorização de imagem registrada, nome e foto de convidado não vão ao site';
        }

        return $motivos;
    }
}
