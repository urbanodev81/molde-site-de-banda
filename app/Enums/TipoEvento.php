<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoEvento: string
{
    case Bar = 'bar';
    case Aniversario = 'aniversario';
    case Casamento = 'casamento';
    case Empresa = 'empresa';
    case Formatura = 'formatura';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::Bar => 'Bar / casa de show',
            self::Aniversario => 'Aniversário',
            self::Casamento => 'Casamento',
            self::Empresa => 'Evento de empresa',
            self::Formatura => 'Formatura',
            self::Outro => 'Outro',
        };
    }

    public static function opcoes(): array
    {
        return array_map(
            fn (self $c) => ['valor' => $c->value, 'rotulo' => $c->rotulo()],
            self::cases(),
        );
    }
}
