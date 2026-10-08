<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoMaterial: string
{
    case FotoAlta = 'foto_alta';
    case Logo = 'logo';
    case Rider = 'rider';
    case MapaPalco = 'mapa_palco';
    case Contrato = 'contrato';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::FotoAlta => 'Foto em alta resolução',
            self::Logo => 'Logo / marca',
            self::Rider => 'Rider técnico',
            self::MapaPalco => 'Mapa de palco',
            self::Contrato => 'Contrato modelo',
            self::Outro => 'Outro',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::FotoAlta => 'image',
            self::Logo => 'shapes',
            self::Rider => 'clipboard-list',
            self::MapaPalco => 'layout-grid',
            self::Contrato => 'file-signature',
            self::Outro => 'file',
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
