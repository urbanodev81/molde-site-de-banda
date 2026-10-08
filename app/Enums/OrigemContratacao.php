<?php

declare(strict_types=1);

namespace App\Enums;

enum OrigemContratacao: string
{
    case Site = 'site';
    case Whatsapp = 'whatsapp';
    case Instagram = 'instagram';
    case Indicacao = 'indicacao';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::Site => 'Formulário do site',
            self::Whatsapp => 'WhatsApp',
            self::Instagram => 'Instagram',
            self::Indicacao => 'Indicação',
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
