@php
    $passado = $show->jaAconteceu();

    $temImagens = ($show->fotos_publicadas_count ?? 0) > 0 || ($show->videos_publicados_count ?? 0) > 0;
    $temFicha = filled($show->observacoes_publicas) || $temImagens;
@endphp

<li class="revela">
    <article class="show @if ($passado) show--passado @endif">
        <p class="show__data">
            <b><time datetime="{{ $show->comeca_em->toIso8601String() }}">{{ $show->comeca_em->format('d/m') }}</time></b>
            <span>{{ $show->comeca_em->translatedFormat('l') }}</span>

            @if ($passado)
                <em class="show__etiqueta">já aconteceu</em>
            @endif
        </p>

        <div class="show__onde">

            <h3><a href="{{ route('site.show', $show) }}">{{ $show->nome() }}</a></h3>

            @if ($show->logradouro() || $show->tipoDeEspaco())
                <p class="show__rua">{{ implode(' · ', array_filter([$show->tipoDeEspaco(), $show->logradouro()])) }}</p>
            @endif
            <p class="show__praca">
                @if ($show->praca()){{ $show->praca() }} · @endif{{ $show->comeca_em->format('H\hi') }}
                @if ($show->entrada) · {{ $show->entrada }} @endif
            </p>
            @if ($show->observacoes_publicas)
                <p>{{ $show->observacoes_publicas }}</p>
            @endif
        </div>

        <div class="show__acoes">
            @if ($show->local?->site_url || $show->local?->instagram)
                <a class="chip" href="{{ $show->local->site_url ?: $show->local->instagram }}" target="_blank" rel="noopener">Página do local</a>
            @elseif ($show->local)

                <span class="chip chip--vazio">Página do local <em class="marcador">a definir</em></span>
            @endif

            @if ($show->linkDoMapa())
                <a class="chip" href="{{ $show->linkDoMapa() }}" target="_blank" rel="noopener">Como chegar</a>
            @endif

            @if ($temImagens)
                <a class="chip chip--forte" href="{{ route('site.show', $show) }}">Ver como foi</a>
            @elseif ($temFicha)
                <a class="chip" href="{{ route('site.show', $show) }}">Detalhes do show</a>
            @endif

            @unless ($passado)
                <a class="chip" href="#contratar">Quero essa data</a>
            @endunless
        </div>
    </article>
</li>
