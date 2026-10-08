<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrigemContratacao;
use App\Enums\StatusContratacao;
use App\Enums\TipoEvento;
use App\Support\Auditoria\Auditable;
use App\Support\TemMateriais;
use App\Support\TemUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contratacao extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;
    use TemMateriais;
    use TemUuid;

    protected $table = 'contratacoes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tipo_evento' => TipoEvento::class,
            'origem' => OrigemContratacao::class,
            'status' => StatusContratacao::class,
            'data_pretendida' => 'date',
            'valor_proposto' => 'decimal:2',
            'consentimento_em' => 'datetime',
        ];
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function interacoes(): HasMany
    {
        return $this->hasMany(ContratacaoInteracao::class)->orderByDesc('ocorrido_em');
    }

    public function scopeAbertas(Builder $query): Builder
    {
        $encerrados = array_values(array_map(
            fn (StatusContratacao $s) => $s->value,
            array_filter(StatusContratacao::cases(), fn (StatusContratacao $s) => $s->encerrado()),
        ));

        return $query->whereNotIn('status', $encerrados);
    }

    public function scopeSemResposta(Builder $query): Builder
    {
        return $query->where('status', StatusContratacao::Novo->value);
    }

    public function diasEsperando(): int
    {
        return (int) $this->created_at->startOfDay()->diffInDays(now()->startOfDay());
    }

    public function temConsentimentoRegistrado(): bool
    {
        return $this->consentimento_em !== null;
    }

    public function registrarInteracao(string $descricao, string $tipo, ?User $autor = null): ContratacaoInteracao
    {
        return $this->interacoes()->create([
            'user_id' => $autor?->getKey(),
            'user_nome' => $autor?->name,
            'tipo' => $tipo,
            'descricao' => $descricao,
            'ocorrido_em' => now(),
        ]);
    }
}
