<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait TemUuid
{
    public static function bootTemUuid(): void
    {
        static::creating(function (Model $m): void {
            if (empty($m->uuid)) {
                $m->uuid = (string) Str::uuid7();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        abort_if(($field ?? $this->getRouteKeyName()) === 'uuid' && ! Str::isUuid((string) $value), 404);

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }
}
