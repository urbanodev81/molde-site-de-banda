<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pergunta extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $attributes = ['publicada' => true];

    protected function casts(): array
    {
        return ['publicada' => 'boolean'];
    }

    public function scopePublicadas(Builder $query): Builder
    {
        return $query->where('publicada', true)->orderBy('ordem');
    }
}
