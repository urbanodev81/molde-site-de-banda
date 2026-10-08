<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoShow: string
{
    case Publico = 'publico';
    case Particular = 'particular';

    public function rotulo(): string
    {
        return match ($this) {
            self::Publico => 'Aberto ao público',
            self::Particular => 'Evento particular',
        };
    }

    public function vaiParaOSite(): bool
    {
        return $this === self::Publico;
    }

    public static function opcoes(): array
    {
        return array_map(
            fn (self $c) => ['valor' => $c->value, 'rotulo' => $c->rotulo()],
            self::cases(),
        );
    }
}
