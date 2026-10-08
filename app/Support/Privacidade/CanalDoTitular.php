<?php

declare(strict_types=1);

namespace App\Support\Privacidade;

class CanalDoTitular
{
    public const MARCADOR = '{{canal}}';

    public static function endereco(): string
    {
        return (string) config('privacidade.canal');
    }

    public static function aplicar(?string $texto): string
    {
        return str_replace(self::MARCADOR, self::endereco(), (string) $texto);
    }
}
