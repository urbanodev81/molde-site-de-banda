<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoInteracao: string
{
    case Nota = 'nota';
    case Ligacao = 'ligacao';
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case Reuniao = 'reuniao';
    case MudancaStatus = 'mudanca_status';

    public function rotulo(): string
    {
        return match ($this) {
            self::Nota => 'Anotação',
            self::Ligacao => 'Ligação',
            self::Whatsapp => 'WhatsApp',
            self::Email => 'E-mail',
            self::Reuniao => 'Conversa presencial',
            self::MudancaStatus => 'Mudança de etapa',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::Nota => 'sticky-note',
            self::Ligacao => 'phone',
            self::Whatsapp => 'message-circle',
            self::Email => 'mail',
            self::Reuniao => 'users',
            self::MudancaStatus => 'git-commit-horizontal',
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
