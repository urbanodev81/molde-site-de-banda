<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use App\Support\TemMateriais;
use App\Support\TemUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Integrante extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;
    use TemMateriais;
    use TemUuid;

    protected $guarded = ['id'];

    protected $attributes = ['ativa' => true];

    protected function casts(): array
    {
        return [
            'ativa' => 'boolean',
            'autorizacao_imagem_em' => 'date',
        ];
    }

    public function fotos(): BelongsToMany
    {
        return $this->belongsToMany(Foto::class);
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class);
    }

    public function scopeNoPalco(Builder $query): Builder
    {
        return $query->where('ativa', true)->orderBy('ordem');
    }

    public function scopePublicaveis(Builder $query): Builder
    {
        return $query->where('ativa', true)
            ->whereNotNull('autorizacao_imagem_em')
            ->orderBy('ordem');
    }

    public function autorizada(): bool
    {
        return $this->autorizacao_imagem_em !== null;
    }

    public function comoAparece(): string
    {
        return $this->nome_artistico ?: $this->nome;
    }

    public function nomePublico(): ?string
    {
        return $this->autorizada() ? $this->comoAparece() : null;
    }

    public function textoAlternativoNoPalco(): string
    {
        $papel = $this->instrumento ? ", {$this->instrumento}" : '';

        return $this->autorizada()
            ? "{$this->comoAparece()}, da A melhor banda{$papel}"
            : "Integrante da A melhor banda{$papel}";
    }

    public function motivosParaNaoAparecer(): array
    {
        $motivos = [];

        if (! $this->ativa) {
            $motivos[] = 'está marcada como inativa';
        }

        if (! $this->autorizada()) {
            $motivos[] = 'sem autorização registrada, o NOME dela não aparece no site — o palco '
                .'mostra o recorte com "a definir" no lugar do nome';
        }

        return $motivos;
    }
}
