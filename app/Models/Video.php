<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoVideo;
use App\Support\Auditoria\Auditable;
use App\Support\TemUuid;
use App\Support\VideoExterno;
use App\Support\Youtube;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;
    use TemUuid;

    public const LIMITE_MB = 20;

    public const LIMITE_SEGUNDOS = 90;

    public const LIMITE_NA_HOME = 8;

    protected $guarded = ['id'];

    protected $attributes = ['publicado' => true, 'demonstracao' => false];

    protected function casts(): array
    {
        return [
            'tipo' => TipoVideo::class,
            'gravado_em' => 'date',
            'duracao_segundos' => 'integer',
            'tamanho_bytes' => 'integer',
            'demonstracao' => 'boolean',
            'publicado' => 'boolean',
        ];
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function tipoGaleria(): BelongsTo
    {
        return $this->belongsTo(TipoGaleria::class);
    }

    public function integrantes(): BelongsToMany
    {
        return $this->belongsToMany(Integrante::class);
    }

    public function scopePublicados(Builder $query): Builder
    {
        return $query->where('publicado', true)->orderBy('ordem')->orderByDesc('gravado_em');
    }

    public function scopeDoSite(Builder $query): Builder
    {
        return $query->publicados()
            ->where(fn (Builder $q) => $q
                ->whereNull('show_id')
                ->orWhereHas('show', fn (Builder $s) => $s->publicaveis()->passados()));
    }

    public function scopeDoTipo(Builder $query, TipoGaleria $tipo): Builder
    {
        if (! $tipo->ehDeShows()) {
            return $query->where('tipo_galeria_id', $tipo->getKey());
        }

        return $query->where(fn (Builder $q) => $q
            ->where('tipo_galeria_id', $tipo->getKey())
            ->orWhere(fn (Builder $heranca) => $heranca
                ->whereNull('tipo_galeria_id')
                ->whereNotNull('show_id')));
    }

    public function ondeFoi(): ?string
    {
        if ($this->local) {
            return collect([$this->local->nome, $this->local->cidade])->filter()->implode(' · ');
        }

        return $this->local_nome;
    }

    public function reproduzivel(): bool
    {
        return match ($this->tipo) {
            TipoVideo::Youtube => filled($this->youtube_id),
            TipoVideo::Arquivo => filled($this->arquivo_mp4_path) || filled($this->arquivo_webm_path),
            TipoVideo::Link => $this->externo() !== null,
        };
    }

    public function externo(): ?array
    {
        return $this->tipo === TipoVideo::Link ? VideoExterno::reconhecer($this->link_url) : null;
    }

    public function linkDeOrigem(): ?string
    {
        return match ($this->tipo) {
            TipoVideo::Youtube => Youtube::url($this->youtube_id),
            TipoVideo::Link => $this->externo()['url'] ?? null,
            TipoVideo::Arquivo => null,
        };
    }
}
