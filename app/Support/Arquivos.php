<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class Arquivos
{
    public static function guardar(?UploadedFile $arquivo, string $pasta, ?string $anterior = null): ?string
    {
        if ($arquivo === null) {
            return $anterior;
        }

        $novo = $arquivo->store($pasta, 'public');

        if ($anterior && $anterior !== $novo && ! str_starts_with($anterior, 'sementes/')) {
            Storage::disk('public')->delete($anterior);
        }

        return $novo === false ? $anterior : $novo;
    }

    public static function guardarPrivado(?UploadedFile $arquivo, string $pasta, ?string $anterior = null): ?string
    {
        if ($arquivo === null) {
            return $anterior;
        }

        $novo = $arquivo->store($pasta, 'local');

        if ($anterior && $anterior !== $novo) {
            Storage::disk('local')->delete($anterior);
        }

        return $novo === false ? $anterior : $novo;
    }

    public static function apagar(?string $caminho): void
    {
        if ($caminho && ! str_starts_with($caminho, 'sementes/')) {
            Storage::disk('public')->delete($caminho);
        }
    }

    public static function url(?string $caminho): ?string
    {
        if (! $caminho) {
            return null;
        }

        return str_starts_with($caminho, 'sementes/')
            ? asset($caminho)
            : Storage::disk('public')->url($caminho);
    }
}
