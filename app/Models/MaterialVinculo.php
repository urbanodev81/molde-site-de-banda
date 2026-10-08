<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AlvoDeMaterial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialVinculo extends Model
{
    protected $table = 'material_vinculos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['alvo_tipo' => AlvoDeMaterial::class];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
