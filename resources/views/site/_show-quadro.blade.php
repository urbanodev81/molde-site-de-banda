@php
    $temImagens = ($show->fotos_publicadas_count ?? 0) > 0 || ($show->videos_publicados_count ?? 0) > 0;
    $temFicha = filled($show->observacoes_publicas) || $temImagens;
    $capa = \App\Support\Arquivos::url($show->cartaz_path);
@endphp

<li class="revela">
    <{{ $temFicha ? 'a' : 'div' }} class="quadro @unless ($temFicha) quadro--sem-ficha @endunless"
        @if ($temFicha)
            href="{{ route('site.show', $show) }}"
        @endif
    >
        <div class="quadro__arte">
            @if ($capa)
                <img src="{{ $capa }}" alt="Cartaz do show no {{ $show->nome() }}"
                     width="520" height="520" loading="lazy" decoding="async">
            @else

                <p class="quadro__data-arte" aria-hidden="true">
                    <b>{{ $show->comeca_em->format('d') }}</b>
                    <span>{{ $show->comeca_em->translatedFormat('M') }}</span>
                </p>
            @endif

            @if ($temImagens)
                <span class="quadro__selo">ver como foi</span>
            @endif

            <span class="quadro__ja">já aconteceu</span>
        </div>

        <div class="quadro__pe">
            <p class="quadro__quando">
                <time datetime="{{ $show->comeca_em->toIso8601String() }}">{{ $show->comeca_em->format('d/m/Y') }}</time>
            </p>
            <h3>{{ $show->nome() }}</h3>
            @if ($show->local?->cidade)
                <p class="quadro__onde">{{ $show->local->cidade }}@if ($show->local->uf) · {{ $show->local->uf }}@endif</p>
            @endif
        </div>
    </{{ $temFicha ? 'a' : 'div' }}>
</li>
