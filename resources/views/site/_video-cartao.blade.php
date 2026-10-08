@php
    $capa = \App\Support\Arquivos::url($video->capa_path) ?: \App\Support\Youtube::capa($video->youtube_id);

    $externo = $video->externo();
    $onde = trim(($video->ondeFoi() ?? '').($video->gravado_em ? ' · '.$video->gravado_em->format('d/m/Y') : ''), ' ·');
@endphp

<button class="video" type="button"
        data-video
        data-titulo="{{ $video->titulo }}"
        data-onde="{{ $onde }}"
        data-youtube="{{ $video->youtube_id }}"
        data-mp4="{{ \App\Support\Arquivos::url($video->arquivo_mp4_path) }}"
        data-webm="{{ \App\Support\Arquivos::url($video->arquivo_webm_path) }}"
        data-embed="{{ $externo['embed'] ?? '' }}"
        data-origem="{{ $externo['url'] ?? '' }}"
        data-provedor="{{ $externo ? \App\Support\VideoExterno::rotulo($externo['provedor']) : '' }}"
        data-capa="{{ $capa }}"
        data-demonstracao="{{ $video->demonstracao ? '1' : '0' }}"
        aria-haspopup="dialog">
    <span class="video__capa">
        @if ($capa)
            <img src="{{ $capa }}" width="400" height="225" loading="lazy" decoding="async" alt="">
        @endif
        @if ($video->demonstracao)
            <span class="video__selo"><em class="marcador">demonstração</em></span>
        @endif
    </span>
    <span class="video__corpo">
        <span class="video__titulo">{{ $video->titulo }}</span>
        <span class="video__onde">{{ $onde }}</span>
    </span>
</button>
