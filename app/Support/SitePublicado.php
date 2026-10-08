<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

final class SitePublicado
{
    public const CHAVE = 'site.publicado';

    public static function aberto(): bool
    {
        return ConfiguracaoDoSite::valor(self::CHAVE) === '1';
    }

    public static function bloqueia(Request $request): bool
    {
        return ! self::aberto() && $request->user() === null;
    }
}
