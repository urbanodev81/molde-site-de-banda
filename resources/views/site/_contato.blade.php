@php $noModal = $noModal ?? false; @endphp

<div @class(['contato', 'revela' => ! $noModal])>
    <p class="olho">Fala com a gente</p>
    @if ($noModal)
        <h2 class="modal__titulo" id="{{ $idDoTitulo }}">Redes &amp; contato</h2>
    @else
        <h2 id="{{ $idDoTitulo }}" data-eco="Redes &amp; contato">Redes &amp; contato</h2>
    @endif
</div>

@include('site._redes', ['semRevelar' => $noModal])
