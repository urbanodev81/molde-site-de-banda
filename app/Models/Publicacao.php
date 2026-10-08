<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoPublicacao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Publicacao extends Model
{
    use SoftDeletes;

    protected $table = 'publicacoes';

    protected $guarded = ['id'];

    protected $attributes = ['tipo' => 'clipping', 'publicada' => false, 'destaque' => false];

    protected function casts(): array
    {
        return [
            'tipo' => TipoPublicacao::class,
            'saiu_em' => 'date',
            'publicada' => 'boolean',
            'destaque' => 'boolean',
        ];
    }

    public function scopePublicaveis(Builder $query): Builder
    {
        return $query->where('publicada', true)
            ->orderByDesc('destaque')
            ->orderByRaw('saiu_em desc nulls last')
            ->orderByDesc('id');
    }

    public function origem(): string
    {
        return implode(' · ', array_filter([$this->veiculo, $this->saiu_em?->format('d/m/Y')]));
    }
}
