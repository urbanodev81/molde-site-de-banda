<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use App\Support\TemMateriais;
use App\Support\TemUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Local extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    use TemMateriais;
    use TemUuid;

    protected $table = 'locais';

    protected $guarded = ['id'];

    protected $attributes = ['ativa' => true];

    protected $with = ['tipo'];

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $local): void {
            $local->slug ??= self::slugUnico($local->nome);
        });
    }

    public static function slugUnico(string $nome, ?int $ignorarId = null): string
    {
        $base = Str::slug($nome) ?: 'local';
        $slug = $base;
        $n = 2;

        while (self::withTrashed()
            ->where('slug', $slug)
            ->when($ignorarId, fn (Builder $q) => $q->whereKeyNot($ignorarId))
            ->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoEspaco::class, 'tipo_id');
    }

    public function tipoPublico(): ?string
    {
        return $this->tipo?->publicado ? $this->tipo->nome : null;
    }

    public function shows(): HasMany
    {
        return $this->hasMany(Show::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativa', true);
    }

    public function enderecoCompleto(): string
    {
        return collect([$this->endereco, $this->bairro, $this->cidade, $this->uf])
            ->filter()
            ->implode(' · ');
    }

    public function logradouro(): string
    {
        return collect([$this->endereco, $this->bairro])->filter()->implode(' · ');
    }

    public function praca(): string
    {
        return collect([$this->cidade, $this->uf])->filter()->implode(' · ');
    }

    public function linkDoMapa(): ?string
    {
        if ($this->mapa_url) {
            return $this->mapa_url;
        }

        $endereco = $this->enderecoCompleto();

        return $endereco === ''
            ? null
            : 'https://www.google.com/maps/search/?api=1&query='.urlencode($this->nome.' '.$endereco);
    }
}
