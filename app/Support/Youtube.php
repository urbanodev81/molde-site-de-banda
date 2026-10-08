<?php

declare(strict_types=1);

namespace App\Support;

final class Youtube
{
    public static function id(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        if (preg_match('#(?:v=|youtu\.be/|/embed/|/shorts/|/live/)([A-Za-z0-9_-]{6,20})#', $valor, $achado) === 1) {
            return $achado[1];
        }

        return $valor;
    }

    public static function url(?string $id): ?string
    {
        return filled($id) ? 'https://www.youtube.com/watch?v='.$id : null;
    }

    public static function capa(?string $id): ?string
    {
        return filled($id) ? 'https://i.ytimg.com/vi/'.$id.'/hqdefault.jpg' : null;
    }

    public static function embed(?string $id): ?string
    {
        return filled($id) ? 'https://www.youtube-nocookie.com/embed/'.$id.'?rel=0' : null;
    }
}
