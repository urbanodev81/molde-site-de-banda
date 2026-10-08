<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

final class RedesDaPessoa
{
    private const REDES = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'site_url' => 'Site',
    ];

    public static function de(Model $pessoa, string $rotulo): array
    {
        $redes = [];

        foreach (self::REDES as $campo => $nome) {
            $url = $pessoa->getAttributes()[$campo] ?? null;

            if (filled($url)) {
                $redes[] = [$campo === 'site_url' ? 'site' : $campo, (string) $url, "{$nome} de {$rotulo}", $nome];
            }
        }

        return $redes;
    }
}
