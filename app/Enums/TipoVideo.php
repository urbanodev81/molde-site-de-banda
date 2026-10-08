<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoVideo: string
{
    case Arquivo = 'arquivo';
    case Youtube = 'youtube';

    case Link = 'link';

    public function rotulo(): string
    {
        return match ($this) {
            self::Arquivo => 'Arquivo curto (mp4)',
            self::Youtube => 'YouTube',
            self::Link => 'Outro site',
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
