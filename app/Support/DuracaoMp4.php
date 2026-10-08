<?php

declare(strict_types=1);

namespace App\Support;

final class DuracaoMp4
{
    public static function segundos(string $caminho): ?float
    {
        $arquivo = @fopen($caminho, 'rb');

        if ($arquivo === false) {
            return null;
        }

        try {
            $tamanho = filesize($caminho) ?: 0;
            $moov = self::acharCaixa($arquivo, 0, $tamanho, 'moov');

            if ($moov === null) {
                return null;
            }

            $mvhd = self::acharCaixa($arquivo, $moov[0], $moov[1], 'mvhd');

            if ($mvhd === null) {
                return null;
            }

            fseek($arquivo, $mvhd[0]);
            $cabeca = fread($arquivo, 32);

            if ($cabeca === false || strlen($cabeca) < 32) {
                return null;
            }

            if (ord($cabeca[0]) === 1) {
                $escala = unpack('N', substr($cabeca, 20, 4))[1];
                $duracao = unpack('J', substr($cabeca, 24, 8))[1];
            } else {
                $escala = unpack('N', substr($cabeca, 12, 4))[1];
                $duracao = unpack('N', substr($cabeca, 16, 4))[1];
            }

            return $escala > 0 ? $duracao / $escala : null;
        } finally {
            fclose($arquivo);
        }
    }

    private static function acharCaixa($arquivo, int $inicio, int $fim, string $tipo): ?array
    {
        $posicao = $inicio;

        while ($posicao + 8 <= $fim) {
            fseek($arquivo, $posicao);
            $cabeca = fread($arquivo, 8);

            if ($cabeca === false || strlen($cabeca) < 8) {
                return null;
            }

            $tamanho = unpack('N', substr($cabeca, 0, 4))[1];
            $nome = substr($cabeca, 4, 4);
            $conteudo = $posicao + 8;

            if ($tamanho === 1) {
                $grande = fread($arquivo, 8);

                if ($grande === false || strlen($grande) < 8) {
                    return null;
                }

                $tamanho = unpack('J', $grande)[1];
                $conteudo += 8;
            } elseif ($tamanho === 0) {
                $tamanho = $fim - $posicao;
            }

            if ($tamanho < 8) {
                return null;
            }

            if ($nome === $tipo) {
                return [$conteudo, min($posicao + $tamanho, $fim)];
            }

            $posicao += $tamanho;
        }

        return null;
    }
}
