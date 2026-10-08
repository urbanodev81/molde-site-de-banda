<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoPublicacao: string
{
    case Clipping = 'clipping';
    case Noticia = 'noticia';
    case Entrevista = 'entrevista';

    public function rotulo(): string
    {
        return match ($this) {
            self::Clipping => 'Clipping',
            self::Noticia => 'Notícia',
            self::Entrevista => 'Entrevista',
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
