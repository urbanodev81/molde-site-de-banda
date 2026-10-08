<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AlvoDeMaterial;
use App\Models\Material;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait TemMateriais
{
    public function materiais(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'material_vinculos', 'alvo_id', 'material_id')
            ->withPivotValue('alvo_tipo', AlvoDeMaterial::de($this)->value)
            ->withTimestamps();
    }
}
