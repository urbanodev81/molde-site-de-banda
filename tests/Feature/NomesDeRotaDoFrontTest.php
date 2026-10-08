<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

it('não chama do front nenhuma rota que o Laravel não tem', function () {
    $faltando = [];

    foreach (File::allFiles(resource_path('js')) as $arquivo) {
        if (! in_array($arquivo->getExtension(), ['vue', 'js'], true)) {
            continue;
        }

        preg_match_all("/\broute\(\s*'([^']+)'/", $arquivo->getContents(), $achados);

        foreach ($achados[1] as $nome) {
            if (! Route::has($nome)) {
                $faltando[] = "{$nome} ({$arquivo->getRelativePathname()})";
            }
        }
    }

    expect($faltando)->toBe([]);
});
