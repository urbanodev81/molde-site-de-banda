@php $versao = $musica->versao(); @endphp
@if ($versao)
    <button class="chip chip--play" type="button"
            data-video
            data-titulo="{{ $versao['titulo'] }}"
            data-onde="{{ $versao['onde'] }}"
            data-youtube="{{ $versao['youtube'] }}"
            data-mp4="{{ $versao['mp4'] }}"
            data-webm="{{ $versao['webm'] }}"
            data-embed="{{ $versao['embed'] }}"
            data-origem="{{ $versao['origem'] }}"
            data-provedor="{{ $versao['provedor'] }}"
            data-capa="{{ $versao['capa'] }}"
            data-demonstracao="0"
            aria-haspopup="dialog"
            aria-label="Ver a versão da banda de {{ $musica->titulo }}">Ver a versão</button>
@endif
