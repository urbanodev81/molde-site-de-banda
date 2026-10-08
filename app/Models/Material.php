<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoMaterial;
use App\Support\Arquivos;
use App\Support\TemUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TemUuid;

    protected $table = 'materiais';

    protected $guarded = ['id'];

    protected $attributes = ['publico' => false, 'privado' => false];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMaterial::class,
            'publico' => 'boolean',
            'privado' => 'boolean',
        ];
    }

    public function vinculos(): HasMany
    {
        return $this->hasMany(MaterialVinculo::class);
    }

    public function scopePublicos(Builder $query): Builder
    {
        return $query->where('publico', true)->where('privado', false)->orderBy('ordem');
    }

    public function link(): ?string
    {
        return $this->privado
            ? route('painel.materiais.baixar', $this)
            : Arquivos::url($this->arquivo_path);
    }

    public function tamanhoLegivel(): ?string
    {
        if (! $this->tamanho_bytes) {
            return null;
        }

        $unidades = ['B', 'KB', 'MB', 'GB'];
        $valor = (float) $this->tamanho_bytes;
        $i = 0;

        while ($valor >= 1024 && $i < count($unidades) - 1) {
            $valor /= 1024;
            $i++;
        }

        return number_format($valor, $i === 0 ? 0 : 1, ',', '.').' '.$unidades[$i];
    }
}
