<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\TipoVideo;
use App\Models\Video;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

final class EnvioDeVideo
{
    public static function regrasDoArquivo(): array
    {
        return ['nullable', 'file', 'max:'.(Video::LIMITE_MB * 1024), 'mimetypes:video/mp4'];
    }

    public static function mensagens(string $campo): array
    {
        return [
            "{$campo}.max" => 'O vídeo passa de '.Video::LIMITE_MB.' MB. Corte um trecho mais curto — ou suba no YouTube e cole o link.',
            "{$campo}.mimetypes" => 'O vídeo precisa ser mp4 — é o formato que toca em todo celular. O WhatsApp já exporta assim.',
            "{$campo}.uploaded" => 'O vídeo não chegou ao servidor. Se ele passa de '.Video::LIMITE_MB.' MB, é esse o motivo.',
        ];
    }

    public static function conferirDuracao(Validator $validator, ?UploadedFile $arquivo, string $campo): void
    {
        if ($arquivo === null || $validator->errors()->has($campo)) {
            return;
        }

        $segundos = DuracaoMp4::segundos($arquivo->getRealPath());

        if ($segundos === null) {
            $validator->errors()->add($campo, 'Não consegui ler a duração deste vídeo. Exporte de novo em mp4 (o WhatsApp faz isso ao compartilhar).');

            return;
        }

        if ($segundos > Video::LIMITE_SEGUNDOS) {
            $validator->errors()->add($campo, sprintf(
                'O vídeo tem %s. O limite é %d segundos — corte o melhor trecho, ou suba no YouTube e cole o link.',
                self::duracaoLegivel((int) round($segundos)),
                Video::LIMITE_SEGUNDOS,
            ));
        }
    }

    public static function doLink(?string $link): ?array
    {
        $link = trim((string) $link);

        if ($link === '') {
            return null;
        }

        $host = strtolower((string) parse_url($link, PHP_URL_HOST));
        $ehYoutube = preg_match('#(^|\.)(youtube\.com|youtu\.be|youtube-nocookie\.com)$#', $host) === 1

            || preg_match('/^[A-Za-z0-9_-]{11}$/', $link) === 1;

        if ($ehYoutube) {
            $id = Youtube::id($link);

            return $id && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id) === 1
                ? ['tipo' => TipoVideo::Youtube->value, 'youtube_id' => $id, 'link_url' => null]
                : null;
        }

        $externo = VideoExterno::reconhecer($link);

        return $externo
            ? ['tipo' => TipoVideo::Link->value, 'youtube_id' => null, 'link_url' => $externo['url']]
            : null;
    }

    public static function mensagemDeLinkDesconhecido(): string
    {
        return 'Não reconheci este link. Funciona com YouTube, Vimeo, Instagram e TikTok — cole o endereço do vídeo, não o do perfil.';
    }

    public static function guardarArquivo(UploadedFile $arquivo, ?Video $anterior = null): array
    {
        $segundos = DuracaoMp4::segundos($arquivo->getRealPath());

        return [
            'tipo' => TipoVideo::Arquivo->value,
            'arquivo_mp4_path' => Arquivos::guardar($arquivo, 'videos', $anterior?->arquivo_mp4_path),
            'duracao_segundos' => $segundos !== null ? (int) round($segundos) : null,
            'tamanho_bytes' => $arquivo->getSize() ?: null,
            'youtube_id' => null,
            'link_url' => null,
        ];
    }

    public static function criarNaBiblioteca(array $atributos, ?UploadedFile $arquivo, ?string $link, ?UploadedFile $capa = null): Video
    {
        if ($arquivo !== null) {
            return Video::create([
                ...$atributos,
                ...self::guardarArquivo($arquivo),
                'capa_path' => Arquivos::guardar($capa, 'videos'),
            ]);
        }

        $doLink = self::doLink($link) ?? throw new \InvalidArgumentException('Link de vídeo não reconhecido.');

        $existente = Video::query()
            ->where('tipo', $doLink['tipo'])
            ->when($doLink['youtube_id'], fn ($q, $id) => $q->where('youtube_id', $id))
            ->when($doLink['link_url'], fn ($q, $url) => $q->where('link_url', $url))
            ->first();

        return $existente ?? Video::create([...$atributos, ...$doLink]);
    }

    public static function duracaoLegivel(?int $segundos): ?string
    {
        if ($segundos === null) {
            return null;
        }

        return $segundos < 60 ? "{$segundos} s" : sprintf('%d min %02d s', intdiv($segundos, 60), $segundos % 60);
    }
}
