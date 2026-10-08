<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Support\Auditoria\Auditable;
use App\Support\TemMateriais;
use App\Support\TemUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Show extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;
    use TemMateriais;
    use TemUuid;

    protected $guarded = ['id'];

    protected $attributes = ['publicado' => true, 'destaque' => false];

    protected function casts(): array
    {
        return [
            'comeca_em' => 'datetime',
            'termina_em' => 'datetime',
            'status' => StatusShow::class,
            'tipo' => TipoShow::class,
            'cache' => 'decimal:2',
            'destaque' => 'boolean',
            'publicado' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Show $show): void {
            if ($show->isDirty('local_id')) {
                $show->unsetRelation('local');
            }

            $descreve = filled($show->slug)
                && str_starts_with($show->slug, 'show-'.Str::slug($show->nome()).'-')
                && str_contains($show->slug, $show->slugDoMes());

            if (! $descreve) {
                $show->slug = $show->slugDisponivel();
            }
        });
    }

    public function slugBase(): string
    {
        return Str::limit('show-'.Str::slug($this->nome()), 120, '').'-'.$this->slugDoMes();
    }

    private function slugDoMes(): string
    {
        return Str::slug($this->comeca_em?->translatedFormat('F-Y') ?? 'sem-data');
    }

    public function slugDisponivel(): string
    {
        $comDia = 'show-'.Str::slug($this->nome()).'-'.($this->comeca_em?->format('d') ?? '00').'-'.$this->slugDoMes();
        $candidatos = [$this->slugBase(), $comDia];

        foreach (range(2, 50) as $n) {
            $candidatos[] = $comDia.'-'.$n;
        }

        foreach ($candidatos as $candidato) {
            $ocupado = static::withTrashed()
                ->where('slug', $candidato)
                ->when($this->exists, fn (Builder $q) => $q->whereKeyNot($this->getKey()))
                ->exists();

            if (! $ocupado) {
                return $candidato;
            }
        }

        return $comDia.'-'.Str::lower(Str::random(6));
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(Foto::class);
    }

    public function participacoes(): BelongsToMany
    {
        return $this->belongsToMany(ParticipacaoEspecial::class, 'participacao_especial_show');
    }

    public function depoimentos(): HasMany
    {
        return $this->hasMany(Depoimento::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function setlist(): BelongsToMany
    {
        return $this->belongsToMany(Musica::class, 'musica_show')
            ->withPivot(['ordem', 'bloco'])
            ->withTimestamps()
            ->orderBy('musica_show.ordem');
    }

    public function scopeFuturos(Builder $query): Builder
    {
        return $query->where('comeca_em', '>=', now())->orderBy('comeca_em');
    }

    public function scopePassados(Builder $query): Builder
    {
        return $query->where('comeca_em', '<', now())->orderByDesc('comeca_em');
    }

    public function scopePublicaveis(Builder $query): Builder
    {
        $statusVisiveis = array_values(array_map(
            fn (StatusShow $s) => $s->value,
            array_filter(StatusShow::cases(), fn (StatusShow $s) => $s->vaiParaOSite()),
        ));

        $tiposVisiveis = array_values(array_map(
            fn (TipoShow $t) => $t->value,
            array_filter(TipoShow::cases(), fn (TipoShow $t) => $t->vaiParaOSite()),
        ));

        return $query->where('publicado', true)
            ->whereIn('status', $statusVisiveis)
            ->whereIn('tipo', $tiposVisiveis);
    }

    public function nome(): string
    {
        return $this->local?->nome ?? $this->titulo ?? 'Show';
    }

    public function endereco(): ?string
    {
        if ($this->endereco_livre) {
            return $this->endereco_livre;
        }

        $doLocal = $this->local?->enderecoCompleto();

        return $doLocal === '' ? null : $doLocal;
    }

    public function logradouro(): ?string
    {
        return $this->endereco_livre ?: ($this->local?->logradouro() ?: null);
    }

    public function praca(): ?string
    {
        return $this->endereco_livre ? null : ($this->local?->praca() ?: null);
    }

    public function tipoDeEspaco(): ?string
    {
        return $this->local?->tipoPublico();
    }

    public function linkDoMapa(): ?string
    {
        return $this->mapa_url ?: $this->local?->linkDoMapa();
    }

    public function jaAconteceu(): bool
    {
        return $this->comeca_em->isPast();
    }

    public function motivosParaNaoAparecer(): array
    {
        $motivos = [];

        if (! $this->publicado) {
            $motivos[] = 'está marcado como não publicado';
        }

        if (! $this->status->vaiParaOSite()) {
            $motivos[] = 'o status é '.mb_strtolower($this->status->rotulo());
        }

        if (! $this->tipo->vaiParaOSite()) {
            $motivos[] = 'é um evento particular — endereço de festa privada não vai para a internet';
        }

        return $motivos;
    }
}
