<?php

declare(strict_types=1);

namespace App\Support;

final class VideoExterno
{
    public const PROVEDORES = [
        'vimeo' => 'Vimeo',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
    ];

    public static function reconhecer(?string $link): ?array
    {
        $link = trim((string) $link);

        if ($link === '' || ! preg_match('#^https?://#i', $link)) {
            return null;
        }

        $host = strtolower((string) parse_url($link, PHP_URL_HOST));
        $caminho = (string) parse_url($link, PHP_URL_PATH);

        if (preg_match('#(^|\.)vimeo\.com$#', $host) && preg_match('#/(?:video/)?(\d{6,12})(?:/|$)#', $caminho, $m)) {
            return self::montar('vimeo', $m[1], 'https://vimeo.com/'.$m[1], 'https://player.vimeo.com/video/'.$m[1].'?dnt=1');
        }

        if (preg_match('#(^|\.)instagram\.com$#', $host) && preg_match('#^/(p|reel|reels|tv)/([A-Za-z0-9_-]{5,40})#', $caminho, $m)) {
            $tipo = $m[1] === 'reels' ? 'reel' : $m[1];

            return self::montar('instagram', $m[2], "https://www.instagram.com/{$tipo}/{$m[2]}/", "https://www.instagram.com/{$tipo}/{$m[2]}/embed");
        }

        if (preg_match('#(^|\.)tiktok\.com$#', $host) && preg_match('#/video/(\d{10,25})#', $caminho, $m)) {
            return self::montar('tiktok', $m[1], $link, 'https://www.tiktok.com/embed/v2/'.$m[1]);
        }

        return null;
    }

    public static function rotulo(?string $provedor): string
    {
        return self::PROVEDORES[$provedor] ?? 'site';
    }

    private static function montar(string $provedor, string $id, string $url, string $embed): array
    {
        return compact('provedor', 'id', 'url', 'embed');
    }
}
