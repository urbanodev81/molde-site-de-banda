<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusShow: string
{
    case Rascunho = 'rascunho';
    case Confirmado = 'confirmado';
    case Cancelado = 'cancelado';
    case Realizado = 'realizado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Rascunho => 'Rascunho',
            self::Confirmado => 'Confirmado',
            self::Cancelado => 'Cancelado',
            self::Realizado => 'Realizado',
        };
    }

    public function token(): string
    {
        return match ($this) {
            self::Rascunho => 'fg-subtle',
            self::Confirmado => 'success',
            self::Cancelado => 'danger',
            self::Realizado => 'info',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::Rascunho => 'pencil-line',
            self::Confirmado => 'circle-check',
            self::Cancelado => 'circle-x',
            self::Realizado => 'flag',
        };
    }

    public function vaiParaOSite(): bool
    {
        return $this === self::Confirmado || $this === self::Realizado;
    }

    public static function opcoes(): array
    {
        return array_map(
            fn (self $c) => ['valor' => $c->value, 'rotulo' => $c->rotulo()],
            self::cases(),
        );
    }
}
