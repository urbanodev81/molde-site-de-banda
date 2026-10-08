<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

abstract class Tipo extends Model
{
    use SoftDeletes;

    public const GRUPO = '';

    protected $table = 'tipos';

    protected $guarded = ['id'];

    protected $attributes = ['publicado' => true];

    protected function casts(): array
    {
        return ['publicado' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('grupo', fn (Builder $q) => $q->where($q->qualifyColumn('grupo'), static::GRUPO));

        static::creating(function (self $tipo): void {
            $tipo->grupo = static::GRUPO;
            $tipo->slug ??= static::slugUnico($tipo->nome);
        });
    }

    public function scopePublicados(Builder $query): Builder
    {
        return $query->where('publicado', true)->orderBy('ordem')->orderBy('nome');
    }

    public static function slugUnico(string $nome, ?int $ignorarId = null): string
    {
        $base = Str::slug($nome) ?: 'tipo';
        $slug = $base;
        $n = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignorarId, fn (Builder $q) => $q->whereKeyNot($ignorarId))
            ->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
