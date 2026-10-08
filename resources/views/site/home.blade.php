@extends('site.layout')

@push('dados-estruturados')
    @php

        $marca = $config['banda.nome'] ?? 'A melhor banda';
        $raiz = rtrim($config['seo.dominio'] ?? config('app.url'), '/');
        $zapCru = preg_replace('/\D/', '', (string) ($config['contato.whatsapp'] ?? ''));

        $grafo = [[
            '@type' => 'MusicGroup',
            '@id' => $raiz.'/#banda',
            'name' => $marca,
            'description' => $config['banda.descricao'] ?: ($config['seo.descricao'] ?? null),
            'genre' => ['Rock', 'Pop rock', 'MPB'],
            'url' => $raiz,
            'image' => asset('sementes/og.jpg'),
            'foundingLocation' => ['@type' => 'Place', 'name' => $config['banda.cidade_base'] ?? 'São Paulo'],
            'areaServed' => $config['contratacao.raio_atendimento'] ?? 'São Paulo',
            'telephone' => $zapCru ? '+'.$zapCru : null,
            'email' => $config['contato.email'] ?? null,
            'sameAs' => array_values(array_filter([
                $config['redes.instagram'] ?? null,
                $config['redes.youtube'] ?? null,
                $config['redes.spotify'] ?? null,
                $config['redes.tiktok'] ?? null,
            ])),

            'member' => $integrantes->filter->autorizada()->map(fn ($i) => [
                '@type' => 'Person',
                'name' => $i->comoAparece(),
                'roleName' => $i->instrumento,
            ])->values()->all(),
        ]];

        foreach (collect([$proximo])->filter()->concat($agenda) as $evento) {
            $grafo[] = [
                '@type' => 'MusicEvent',
                'name' => $marca.' no '.$evento->nome(),
                'startDate' => $evento->comeca_em->toIso8601String(),
                'endDate' => $evento->termina_em?->toIso8601String(),
                'eventStatus' => 'https://schema.org/EventScheduled',
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                'url' => $raiz.'/agenda',
                'image' => $evento->cartaz_path ? \App\Support\Arquivos::url($evento->cartaz_path) : asset('sementes/og.jpg'),
                'location' => [
                    '@type' => 'Place',
                    'name' => $evento->nome(),
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => $evento->local?->endereco ?: $evento->endereco_livre,
                        'addressLocality' => $evento->local?->cidade ?? ($config['banda.cidade_base'] ?? 'São Paulo'),
                        'addressRegion' => $evento->local?->uf ?? 'SP',
                        'addressCountry' => 'BR',
                    ],
                ],
                'performer' => ['@id' => $raiz.'/#banda'],
                'organizer' => ['@id' => $raiz.'/#banda'],
            ];
        }

        foreach ($videos->where('demonstracao', false) as $video) {
            $grafo[] = array_filter([
                '@type' => 'VideoObject',
                'name' => $video->titulo,
                'description' => trim($marca.' ao vivo'.($video->ondeFoi() ? ' — '.$video->ondeFoi() : '')),
                'uploadDate' => $video->gravado_em?->toIso8601String(),
                'thumbnailUrl' => $video->capa_path
                    ? \App\Support\Arquivos::url($video->capa_path)
                    : ($video->youtube_id ? "https://i.ytimg.com/vi/{$video->youtube_id}/hqdefault.jpg" : null),
                'embedUrl' => $video->youtube_id ? "https://www.youtube-nocookie.com/embed/{$video->youtube_id}" : null,
                'contentUrl' => $video->arquivo_mp4_path ? \App\Support\Arquivos::url($video->arquivo_mp4_path) : null,
            ]);
        }

        if ($perguntas->isNotEmpty()) {
            $grafo[] = [
                '@type' => 'FAQPage',
                'mainEntity' => $perguntas->map(fn ($p) => [
                    '@type' => 'Question',
                    'name' => $p->pergunta,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $p->resposta],
                ])->values()->all(),
            ];
        }

        $jsonLd = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $grafo],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    @endphp

    <script type="application/ld+json">{!! $jsonLd !!}</script>
@endpush

@section('conteudo')

    @include($palcoVariante ?? 'site._palco')

    @include('site._faixa', ['itens' => $estilos, 'aceitaConfig' => true])

    @if ($proximo)
        <section class="proximo" id="proximo-show" aria-labelledby="tit-proximo">
            <h2 id="tit-proximo" class="so-leitor">Próximo show</h2>

            <div class="wrap proximo__grade">
                <p class="data">
                    <span class="data__dow">{{ $proximo->comeca_em->translatedFormat('l') }} · Próximo show</span>
                    <span class="data__num"><time datetime="{{ $proximo->comeca_em->toIso8601String() }}">{{ $proximo->comeca_em->format('d/m') }}</time></span>
                    <span class="data__hora">{{ $proximo->comeca_em->format('H\hi') }}</span>
                </p>

                @php $logoLocal = \App\Support\Arquivos::url($proximo->local?->logo_path); @endphp
                @if ($logoLocal)
                    <img class="local-logo" src="{{ $logoLocal }}" width="320" height="297" loading="lazy" decoding="async"
                         alt="Emblema do {{ $proximo->local->nome }}">
                @endif

                <div class="local">
                    <h2>{{ $proximo->nome() }}</h2>
                    @if ($proximo->endereco())
                        <p class="local__end">{{ $proximo->endereco() }}</p>
                    @endif
                </div>

                <div class="proximo__acoes">

                    <ul class="relogio" data-relogio="{{ $proximo->comeca_em->toIso8601String() }}" aria-label="Contagem regressiva para o próximo show">
                        <li><b data-relogio-dias>--</b><span>dias</span></li>
                        <li><b data-relogio-horas>--</b><span>horas</span></li>
                        <li><b data-relogio-min>--</b><span>min</span></li>
                    </ul>

                    <div class="btn-linha">
                        @if ($proximo->linkDoMapa())
                            <a class="btn" href="{{ $proximo->linkDoMapa() }}" target="_blank" rel="noopener">Como chegar</a>
                        @endif

                        <a class="btn" href="{{ route('site.agenda') }}">Agenda completa</a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="secao" id="agenda" aria-labelledby="tit-agenda">
        <div class="wrap">
            <div class="secao__cabeca revela">
                <div>
                    <p class="olho">Onde a gente toca</p>
                    <h2 id="tit-agenda" data-eco="Agenda">Agenda</h2>
                    <p>
                        Rock clássico, pop rock e MPB pesada em bares, aniversários e eventos de empresa
                        @if ($config['contratacao.raio_atendimento'] ?? null) em {{ $config['contratacao.raio_atendimento'] }}@endif.
                        Entrada e consumação são por conta do local — o link leva direto para ela.
                    </p>
                </div>
                @if ($config['redes.instagram'] ?? null)
                    <a class="btn" href="{{ $config['redes.instagram'] }}" target="_blank" rel="noopener">Ver no Instagram</a>
                @endif
            </div>

            @if ($agenda->isNotEmpty())
                <ul class="shows">
                    @foreach ($agenda as $show)
                        @include('site._show', ['show' => $show])
                    @endforeach
                </ul>
                <p class="nota"><a href="{{ route('site.agenda') }}">Ver a agenda completa</a></p>
            @elseif ($passados->isNotEmpty())

                @if ($proximo)
                    <p class="nota">A próxima está logo ali em cima. Antes dela, onde a banda tocou:</p>
                @else
                    <p class="nota">A próxima data está sendo fechada. Enquanto isso, onde a banda tocou:</p>
                @endif
                <ul class="shows">
                    @foreach ($passados->take(3) as $show)
                        @include('site._show', ['show' => $show])
                    @endforeach
                </ul>
                <p class="nota"><a href="{{ route('site.agenda') }}">Agenda completa</a> · <a href="#contratar">quero marcar a minha data</a></p>
            @else
                <p class="nota">A agenda está sendo montada. <a href="#contratar">Marque a sua data.</a></p>
            @endif
        </div>
    </section>

    @if ($videos->isNotEmpty() || $fotos->isNotEmpty())
        <section class="secao" id="galeria" aria-labelledby="tit-galeria">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">No palco</p>
                        <h2 id="tit-galeria" data-eco="Galeria">Galeria</h2>
                        <p>Um pouco de show, ensaio e do que acontece no meio. Toque para assistir ou ver grande.</p>
                    </div>

                    <a class="btn" href="{{ route('site.galeria') }}">Ver galeria</a>
                </div>

                @if ($videos->isNotEmpty())

                    <div class="vitrine vitrine--videos revela" id="videos">
                        <button class="vitrine__seta vitrine__seta--antes" type="button" data-vitrine-antes aria-label="Vídeos anteriores" hidden>&#8249;</button>

                        <ul class="vitrine__faixa" data-vitrine>
                            @foreach ($videos as $video)
                                <li>@include('site._video-cartao', ['video' => $video])</li>
                            @endforeach
                        </ul>

                        <button class="vitrine__seta vitrine__seta--depois" type="button" data-vitrine-depois aria-label="Próximos vídeos" hidden>&#8250;</button>
                    </div>

                    @if ($videos->where('demonstracao', true)->isNotEmpty())
                        <p class="nota">
                            O clipe marcado como demonstração foi feito por nós, só para o player não ficar mudo —
                            não é a banda tocando.
                        </p>
                    @endif
                @endif

                @if ($fotos->isNotEmpty())

                    <div class="vitrine revela">
                        <button class="vitrine__seta vitrine__seta--antes" type="button" data-vitrine-antes aria-label="Fotos anteriores" hidden>&#8249;</button>

                        <ul class="vitrine__faixa" data-vitrine>
                            @foreach ($fotos as $foto)
                                <li>@include('site._foto-cartao', ['foto' => $foto, 'grupo' => 'vitrine', 'quando' => false, 'linkDaNoite' => true])</li>
                            @endforeach
                        </ul>

                        <button class="vitrine__seta vitrine__seta--depois" type="button" data-vitrine-depois aria-label="Próximas fotos" hidden>&#8250;</button>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if ($destaques->isNotEmpty())
        <section class="secao" aria-labelledby="tit-repertorio">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">O que a gente toca</p>
                        <h2 id="tit-repertorio" data-eco="Repertório">Repertório</h2>
                        <p>Uma amostra. A lista inteira fica na página do repertório.</p>
                    </div>
                    <a class="btn" href="{{ route('site.repertorio') }}">Repertório completo</a>
                </div>

                <ul class="repertorio revela">
                    @foreach ($destaques as $musica)
                        <li>
                            <b>{{ $musica->titulo }}</b>
                            @if ($musica->artista)<span>{{ $musica->artista }}</span>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($depoimentos->isNotEmpty())
        <section class="secao" aria-labelledby="tit-depoimentos">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">Quem já contratou</p>
                        <h2 id="tit-depoimentos" data-eco="Depoimentos">Depoimentos</h2>
                    </div>
                </div>

                <ul class="depoimentos revela">
                    @foreach ($depoimentos as $depoimento)
                        <li>
                            <figure class="depoimento">
                                <blockquote>“{{ $depoimento->texto }}”</blockquote>
                                <figcaption>
                                    {{ $depoimento->autor }}@if ($depoimento->papel) <span>· {{ $depoimento->papel }}</span>@endif
                                </figcaption>
                            </figure>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @include('site._faixa', [
        'variante' => 'faixa--invertida',
        'itens' => ['Bar', 'Aniversário', 'Casamento', 'Empresa', 'Formatura'],
    ])

    @include('site._contratar')

    @if ($perguntas->isNotEmpty())
        <section class="secao" id="duvidas" aria-labelledby="tit-duvidas">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">Antes de chamar</p>
                        <h2 id="tit-duvidas" data-eco="Dúvidas frequentes">Dúvidas frequentes</h2>
                        <p>O que quem contrata costuma perguntar antes de fechar a data.</p>
                    </div>
                </div>

                <div class="faq revela">
                    @foreach ($perguntas as $indice => $pergunta)
                        <details @if ($indice === 0) open @endif>
                            <summary>{{ $pergunta->pergunta }}</summary>
                            <p>{{ $pergunta->resposta }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="secao" id="contato" aria-labelledby="tit-contato">
        <div class="wrap">

            @include('site._contato', ['idDoTitulo' => 'tit-contato'])
        </div>
    </section>

    @include('site._foto-modal')
    @include('site._video-modal')
@endsection
