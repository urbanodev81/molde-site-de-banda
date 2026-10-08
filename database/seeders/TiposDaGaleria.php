<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TipoGaleria;
use Illuminate\Database\Seeder;

class TiposDaGaleria extends Seeder
{
    private const TIPOS = [
        [TipoGaleria::SLUG_DOS_SHOWS, 'Shows', 'As noites em que a banda subiu no palco.'],
        ['ensaio', 'Ensaio', 'Onde o repertório é montado, antes de virar show.'],
        ['gravacao', 'Gravação', 'Estúdio, clipe e o que sai de lá.'],
        ['participacao', 'Participação', 'Canja, festival, palco de outra banda.'],
        ['bastidores', 'Bastidores', 'A passagem de som, a van, o camarim.'],
    ];

    public function run(): void
    {
        foreach (self::TIPOS as $ordem => [$slug, $nome, $descricao]) {
            TipoGaleria::firstOrCreate(
                ['slug' => $slug],
                ['nome' => $nome, 'descricao' => $descricao, 'ordem' => $ordem, 'publicado' => true],
            );
        }
    }
}
