<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\TemUuid;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Foto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TemUuid;

    protected $guarded = ['id'];

    protected $attributes = ['publicada' => true, 'destaque' => false];

    public const LIMITE_NA_AGENDA = 8;

    public const LIMITE_NA_HOME = 8;

    public const POR_PAGINA_NA_GALERIA = 24;

    public const MINIMO_DA_GALERIA = 3;

    protected function casts(): array
    {
        return [
            'publicada' => 'boolean',
            'destaque' => 'boolean',
            'registrada_em' => 'date',
        ];
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function integrantes(): BelongsToMany
    {
        return $this->belongsToMany(Integrante::class);
    }

    public function tipoGaleria(): BelongsTo
    {
        return $this->belongsTo(TipoGaleria::class);
    }

    public function scopePublicadas(Builder $query): Builder
    {
        return $query->where('publicada', true)->orderBy('ordem')->orderByDesc('created_at');
    }

    public function scopeDoSite(Builder $query): Builder
    {
        return $query->where('publicada', true)
            ->where(fn (Builder $q) => $q
                ->whereHas('show', fn (Builder $s) => $s->publicaveis()->passados())
                ->orWhere(fn (Builder $solta) => $solta
                    ->whereNull('show_id')
                    ->whereHas('tipoGaleria', fn (Builder $t) => $t->where('publicado', true))));
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

    public function scopeMaisRecentes(Builder $query): Builder
    {
        return $query->reorder()->orderByDesc(self::expressaoDaData());
    }

    private static function expressaoDaData(): Expression
    {
        return DB::raw('COALESCE(fotos.registrada_em, (SELECT s.comeca_em::date FROM shows s WHERE s.id = fotos.show_id), fotos.created_at::date)');
    }

    public function scopeDasNoites(Builder $query): Builder
    {
        return $query->publicadas()
            ->whereHas('show', fn (Builder $q) => $q->publicaveis()->passados())
            ->reorder()
            ->orderByDesc(Show::select('comeca_em')->whereColumn('shows.id', 'fotos.show_id'))
            ->orderBy('ordem');
    }

    public static function daGaleriaDasNoites(int $limite = self::LIMITE_NA_AGENDA): Collection
    {
        return static::query()->dasNoites()->with('show.local')->limit($limite)->get();
    }

    public static function daVitrine(int $limite = self::LIMITE_NA_HOME): Collection
    {
        return static::query()->doSite()
            ->with(['show.local', 'tipoGaleria'])
            ->reorder()
            ->orderByDesc('destaque')
            ->orderByDesc(self::expressaoDaData())
            ->limit($limite)
            ->get();
    }

    public static function haGaleriaDasNoites(): bool
    {
        return static::query()->dasNoites()->reorder()->count() >= self::MINIMO_DA_GALERIA;
    }

    public static function haNoitesAlemDe(Show $show): bool
    {
        return static::query()->dasNoites()->reorder()
            ->where('show_id', '!=', $show->getKey())
            ->exists();
    }

    public function quando(): ?Carbon
    {
        return $this->registrada_em ?? $this->show?->comeca_em ?? $this->created_at;
    }

    public function contexto(): ?string
    {
        if ($this->show) {
            return $this->show->nome();
        }

        return $this->tipoGaleria?->nome;
    }

    public function textoAlternativo(): string
    {
        if ($this->legenda) {
            return $this->legenda;
        }

        if ($this->relationLoaded('show') && $this->show) {
            return 'A melhor banda no '.$this->show->nome();
        }

        if ($this->relationLoaded('tipoGaleria') && $this->tipoGaleria) {
            return 'A melhor banda — '.$this->tipoGaleria->nome;
        }

        return 'A melhor banda ao vivo';
    }
}
