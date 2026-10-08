<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEspaco extends Tipo
{
    use Auditable;

    public const GRUPO = 'espaco';

    public function locais(): HasMany
    {
        return $this->hasMany(Local::class, 'tipo_id');
    }
}
