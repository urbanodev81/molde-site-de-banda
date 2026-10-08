@extends('site.layout', [
    'tituloDaPagina' => $show->nome().' · '.$show->comeca_em->format('d/m/Y').' · '.($config['banda.nome'] ?? 'A melhor banda'),
    'descricaoDaPagina' => ($config['banda.nome'] ?? 'A melhor banda').' '.($show->jaAconteceu() ? 'tocou' : 'toca').' no '.$show->nome().' em '.$show->comeca_em->translatedFormat('d \d\e F \d\e Y').'.'.($show->endereco() ? ' '.$show->endereco().'.' : ''),
])

@push('dados-estruturados')
    @php
        $raiz = rtrim($config['seo.dominio'] ?? config('app.url'), '/');
        $marca = $config['banda.nome'] ?? 'A melhor banda';

        $evento = [
            '@type' => 'MusicEvent',
            'name' => $marca.' no '.$show->nome(),
            'startDate' => $show->comeca_em->toIso8601String(),
            'endDate' => $show->termina_em?->toIso8601String(),

            'eventStatus' => $show->jaAconteceu()
                ? 'https://schema.org/EventScheduled'
                : 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'url' => route('site.show', $show),
            'image' => $show->cartaz_path
                ? \App\Support\Arquivos::url($show->cartaz_path)
                : asset('sementes/og.jpg'),
            'location' => [
                '@type' => 'Place',
                'name' => $show->nome(),
                'address' => $show->endereco(),
            ],
            'performer' => ['@type' => 'MusicGroup', 'name' => $marca, '@id' => $raiz.'/#banda'],
            'description' => $show->observacoes_publicas ?: null,
        ];

        $grafo = [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => $raiz],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Agenda', 'item' => $raiz.'/agenda'],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $show->nome(), 'item' => route('site.show', $show)],
                ],
            ],
            array_filter($evento, fn ($v) => $v !== null),
        ];

        $jsonLd = json_encode(['@context' => 'https://schema.org', '@graph' => $grafo],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @endphp
    <script type="application/ld+json">{!! $jsonLd !!}</script>
@endpush

@section('conteudo')
    @php
        $passado = $show->jaAconteceu();
        $temMaterial = $show->fotos->isNotEmpty() || $show->videos->isNotEmpty();
    @endphp

    <section class="secao show-topo">
        <div class="wrap">
            <p class="olho">
                <a href="{{ route('site.agenda') }}">Agenda</a>
                <span aria-hidden="true">·</span>
                {{ $passado ? 'Já aconteceu' : 'Próximo' }}
            </p>

            <div class="show-topo__grade">
                <div class="revela">
                    <p class="show-topo__quando">
                        <b><time datetime="{{ $show->comeca_em->toIso8601String() }}">{{ $show->comeca_em->format('d/m/Y') }}</time></b>
                        <span>{{ $show->comeca_em->translatedFormat('l') }} · {{ $show->comeca_em->format('H\hi') }}</span>
                    </p>

                    <h1>{{ $show->nome() }}</h1>

                    @if ($show->endereco() || $show->tipoDeEspaco())
                        <p class="show-topo__onde">{{ implode(' · ', array_filter([$show->tipoDeEspaco(), $show->endereco()])) }}</p>
                    @endif

                    @if ($show->entrada)
                        <p class="show-topo__entrada">{{ $show->entrada }}</p>
                    @endif

                    @if ($show->observacoes_publicas)
                        <p class="show-topo__texto">{{ $show->observacoes_publicas }}</p>
                    @endif

                    <div class="show__acoes">
                        @if ($show->linkDoMapa())
                            <a class="chip" href="{{ $show->linkDoMapa() }}" target="_blank" rel="noopener">Como chegar</a>
                        @endif
                        @if ($show->local?->site_url || $show->local?->instagram)
                            <a class="chip" href="{{ $show->local->site_url ?: $show->local->instagram }}" target="_blank" rel="noopener">Página do local</a>
                        @endif
                        <a class="chip" href="{{ route('site.home') }}#contratar">
                            {{ $passado ? 'Quero um show assim' : 'Quero essa data' }}
                        </a>
                    </div>
                </div>

                @if ($show->cartaz_path)
                    <figure class="show-topo__cartaz revela">
                        <img src="{{ \App\Support\Arquivos::url($show->cartaz_path) }}"
                             loading="lazy" decoding="async"
                             alt="Cartaz do show no {{ $show->nome() }} em {{ $show->comeca_em->format('d/m/Y') }}">
                    </figure>
                @endif
            </div>
        </div>
    </section>

    @if ($show->participacoes->isNotEmpty())
        <section class="secao" aria-labelledby="tit-participacoes">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">{{ $passado ? 'Subiu ao palco com a gente' : 'Com a gente no palco' }}</p>
                        <h2 id="tit-participacoes">{{ $show->participacoes->count() === 1 ? 'Participação especial' : 'Participações especiais' }}</h2>
                    </div>
                </div>
                <div class="convidados revela">
                    @foreach ($show->participacoes as $pessoa)
                        @include('site._participacao-cartao', ['pessoa' => $pessoa, 'comNoites' => false])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($passado && $temMaterial)
        @include('site._show-como-foi', ['show' => $show])
    @endif

    @if ($show->setlist->isNotEmpty())
        <section class="secao">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">{{ $passado ? 'O que rolou' : 'O que vai rolar' }}</p>
                        <h2>Setlist da noite</h2>
                        <p>
                            {{ $show->setlist->count() }} {{ \Illuminate\Support\Str::plural('música', $show->setlist->count()) }}.
                            O repertório inteiro está na <a href="{{ route('site.repertorio') }}">página de repertório</a>.
                        </p>
                    </div>
                </div>

                <ol class="setlist revela">
                    @foreach ($show->setlist as $musica)
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

    @if (! $passado && $temMaterial)
        @include('site._show-como-foi', ['show' => $show])
    @endif

    @if ($show->depoimentos->isNotEmpty())
        <section class="secao">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">Quem contratou</p>
                        <h2>O que disseram desta noite</h2>
                    </div>
                </div>
                <div class="depoimentos revela">
                    @foreach ($show->depoimentos as $depoimento)
                        <blockquote class="depoimento">
                            <p>{{ $depoimento->texto }}</p>
                            <footer>{{ $depoimento->autor }}@if ($depoimento->papel) <span>{{ $depoimento->papel }}</span>@endif</footer>
                        </blockquote>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($materiais->isNotEmpty())
        <section class="secao" aria-labelledby="tit-materiais">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">Baixe e use</p>
                        <h2 id="tit-materiais">Materiais desta noite</h2>
                    </div>
                </div>

                <ul class="redes revela">
                    @foreach ($materiais as $material)
                        <li>
                            <a class="cartao" href="{{ \App\Support\Arquivos::url($material->arquivo_path) }}" download>
                                <span class="cartao__k">{{ $material->tipo->rotulo() }}@if ($material->tamanhoLegivel()) · {{ $material->tamanhoLegivel() }}@endif</span>
                                <span class="cartao__v">{{ $material->titulo }}</span>
                                @if ($material->descricao)<span class="cartao__obs">{{ $material->descricao }}</span>@endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($vizinhos['anterior'] || $vizinhos['proximo'])
        <section class="secao">
            <div class="wrap">
                <nav class="vizinhos revela" aria-label="Outros shows">
                    @if ($vizinhos['anterior'])
                        <a class="vizinho vizinho--ant" href="{{ route('site.show', $vizinhos['anterior']) }}">
                            <span>Show anterior</span>
                            <b>{{ $vizinhos['anterior']->nome() }}</b>
                            <em>{{ $vizinhos['anterior']->comeca_em->format('d/m/Y') }}</em>
                        </a>
                    @endif
                    @if ($vizinhos['proximo'])
                        <a class="vizinho vizinho--prox" href="{{ route('site.show', $vizinhos['proximo']) }}">
                            <span>Show seguinte</span>
                            <b>{{ $vizinhos['proximo']->nome() }}</b>
                            <em>{{ $vizinhos['proximo']->comeca_em->format('d/m/Y') }}</em>
                        </a>
                    @endif
                </nav>
            </div>
        </section>
    @endif

    @include('site._foto-modal')
    @include('site._video-modal')
@endsection
