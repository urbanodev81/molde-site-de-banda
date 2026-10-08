<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoGaleria extends Tipo
{
    use Auditable;
    use HasFactory;

    public const GRUPO = 'galeria';

    public const SLUG_DOS_SHOWS = 'shows';

    public function fotos(): HasMany
    {
        return $this->hasMany(Foto::class, 'tipo_galeria_id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'tipo_galeria_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function ehDeShows(): bool
    {
        return $this->slug === self::SLUG_DOS_SHOWS;
    }

    public static function dosShows(): ?self
    {
        return static::query()->where('slug', self::SLUG_DOS_SHOWS)->first();
    }

    public static function comMaterial(): Collection
    {
        return static::query()->publicados()->get()
            ->filter(fn (self $tipo) => Foto::query()->doSite()->doTipo($tipo)->exists()
                || Video::query()->doSite()->doTipo($tipo)->exists())
            ->values();
    }
}
