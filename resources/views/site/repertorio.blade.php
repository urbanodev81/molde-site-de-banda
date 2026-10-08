@extends('site.layout', [
    'tituloDaPagina' => 'Repertório · '.($config['banda.nome'] ?? 'A melhor banda'),
    'descricaoDaPagina' => 'O que a '.($config['banda.nome'] ?? 'A melhor banda').' toca: rock clássico, pop rock e MPB em versão pesada.',
])

@section('conteudo')
    <section class="secao">
        <div class="wrap">
            <div class="secao__cabeca revela">
                <div>
                    <p class="olho">O que a gente toca</p>
                    <h1>Repertório</h1>
                    <p>Rock clássico, pop rock e MPB em versão pesada — com espaço para pedidos combinados antes do show.</p>
                </div>
                <a class="btn" href="{{ route('site.home') }}#contratar">Quero contratar</a>
            </div>
        </div>
    </section>

    @if ($atual->isNotEmpty())
        @php $jaFoi = $referencia?->jaAconteceu(); @endphp
        <section class="secao secao--destaque">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">
                            @if ($origemDoAtual === 'setlist')
                                {{ $jaFoi ? 'O que tocou no último show' : 'O que vai tocar no próximo' }}
                            @elseif ($origemDoAtual === 'destaques')
                                As que não faltam
                            @else
                                O que está no palco agora
                            @endif
                        </p>
                        <h2>Repertório atual</h2>
                        <p>
                            @if ($origemDoAtual === 'setlist')

                                <a href="{{ route('site.show', $referencia) }}">{{ $referencia->nome() }}</a>,
                                {{ $referencia->comeca_em->translatedFormat('d \d\e F \d\e Y') }} ·
                                {{ $atual->count() }} {{ \Illuminate\Support\Str::plural('música', $atual->count()) }}
                            @elseif ($origemDoAtual === 'destaques')
                                As {{ $atual->count() }} que a banda escolheu como cartão de visita — elas entram em quase toda noite.
                            @else
                                {{ $atual->count() }} {{ \Illuminate\Support\Str::plural('música', $atual->count()) }} do set que a banda está tocando nesta temporada.
                            @endif
                        </p>
                    </div>
                    @if ($origemDoAtual === 'setlist')
                        <a class="btn" href="{{ route('site.show', $referencia) }}">Ver o show</a>
                    @endif
                </div>

                <ol class="setlist setlist--duas revela" style="--linhas: {{ (int) ceil($atual->count() / 2) }}">
                    @foreach ($atual as $musica)
                        <li>
                            <span class="setlist__n" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="setlist__t">
                                <b>{{ $musica->titulo }}</b>
                                @if ($musica->artista)<span>{{ $musica->artista }}</span>@endif
                            </span>
                            @include('site._versao', ['musica' => $musica])
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    <section class="secao">
        <div class="wrap">
            @if ($outras->isNotEmpty())
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">Fora do set de agora</p>
                        <h2>Outras que a gente já tocou</h2>
                        <p>
                            Mais {{ $outras->count() }} {{ \Illuminate\Support\Str::plural('música', $outras->count()) }} no repertório —
                            {{ $total }} no total. Pedido especial? Combine antes do show.
                        </p>
                    </div>
                </div>

                <ul class="repertorio revela">
                    @foreach ($outras as $musica)
                        <li>
                            <span class="repertorio__t">
                                <b>{{ $musica->titulo }}</b>
                                @if ($musica->artista)<span>{{ $musica->artista }}</span>@endif
                            </span>

                            @include('site._versao', ['musica' => $musica])
                        </li>
                    @endforeach
                </ul>
            @elseif ($atual->isEmpty())
                <p class="nota">
                    O repertório está sendo montado.
                    <a href="{{ route('site.home') }}#contratar">Fale com a banda</a> para saber o que ela toca.
                </p>
            @endif
        </div>
    </section>

    @include('site._video-modal')
@endsection
