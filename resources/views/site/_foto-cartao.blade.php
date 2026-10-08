@php
    $url = \App\Support\Arquivos::url($foto->arquivo_path);
    $contexto = $foto->contexto();
    $data = $foto->quando();
@endphp

<button class="foto" type="button"
        data-foto="{{ $url }}"
        data-grupo="{{ $grupo ?? 'galeria' }}"
        data-legenda="{{ $foto->legenda }}"
        data-descricao="{{ $foto->descricao }}"
        data-credito="{{ $foto->credito }}"
        data-contexto="{{ $contexto }}"
        data-quando="{{ $data?->format('d/m/Y') }}"
        data-alt="{{ $foto->textoAlternativo() }}"
        data-link="{{ ($linkDaNoite ?? false) && $foto->show ? route('site.show', $foto->show) : '' }}"
        aria-haspopup="dialog"
        aria-label="Ampliar: {{ $foto->textoAlternativo() }}">

    <span class="foto__quadro">
        <img src="{{ $url }}"
             @if ($foto->largura) width="{{ $foto->largura }}" @endif
             @if ($foto->altura) height="{{ $foto->altura }}" @endif
             loading="lazy" decoding="async"
             alt="{{ $foto->textoAlternativo() }}">

        @if ($foto->legenda)

            <span class="foto__titulo" aria-hidden="true">{{ $foto->legenda }}</span>
        @endif
    </span>

    @if (($quando ?? false) && ($contexto || $data))
        <span class="foto__etiqueta">
            @if ($contexto)<b>{{ $contexto }}</b>@endif
            @if ($data)<time datetime="{{ $data->toDateString() }}">{{ $data->format('d/m/Y') }}</time>@endif
        </span>
    @endif
</button>
