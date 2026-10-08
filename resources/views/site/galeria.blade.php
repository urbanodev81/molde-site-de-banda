@php
    $marca = $config['banda.nome'] ?? 'A melhor banda';
    $titulo = $tipo?->nome ?? 'Galeria';
@endphp

@extends('site.layout', [
    'tituloDaPagina' => $titulo.' · '.$marca,
    'descricaoDaPagina' => $tipo?->descricao
        ?: 'Fotos e vídeos da '.$marca.' — shows, ensaios, gravações e bastidores.',
])

@push('dados-estruturados')
    @php

        $galeria = [
            '@context' => 'https://schema.org',
            '@type' => 'ImageGallery',
            'name' => $titulo.' · '.$marca,
            'url' => url()->current(),
            'about' => ['@type' => 'MusicGroup', 'name' => $marca],
        ];

        if ($fotos->currentPage() === 1 && $fotos->isNotEmpty()) {
            $galeria['image'] = $fotos->take(12)
                ->map(fn ($f) => \App\Support\Arquivos::url($f->arquivo_path))
                ->values()->all();
        }
    @endphp
    <script type="application/ld+json">{!! json_encode($galeria, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('conteudo')
    <section class="secao">
        <div class="wrap">
            <div class="secao__cabeca revela">
                <div>
                    <p class="olho">{{ $tipo ? 'Galeria' : 'O acervo' }}</p>
                    <h1>{{ $titulo }}</h1>
                    <p>
                        @if ($tipo?->descricao)
                            {{ $tipo->descricao }}
                        @elseif ($tipo)
                            Tudo o que a banda registrou em {{ mb_strtolower($tipo->nome) }}.
                        @else
                            Show, ensaio, gravação e bastidor — tudo o que a banda registrou.
                            Toque em qualquer imagem para ver grande.
                        @endif
                    </p>
                </div>
                <a class="btn" href="{{ route('site.home') }}#contratar">Quero contratar</a>
            </div>

            @if ($abas->isNotEmpty())
                <nav class="abas revela" aria-label="Tipos de galeria">
                    <a class="abas__item @if (! $tipo) abas__item--atual @endif"
                       href="{{ route('site.galeria') }}"
                       @if (! $tipo) aria-current="page" @endif>Tudo</a>

                    @foreach ($abas as $aba)
                        <a class="abas__item @if ($tipo?->is($aba)) abas__item--atual @endif"
                           href="{{ route('site.galeria.tipo', $aba) }}"
                           @if ($tipo?->is($aba)) aria-current="page" @endif>{{ $aba->nome }}</a>
                    @endforeach
                </nav>
            @endif
        </div>
    </section>

    @if ($videos->isNotEmpty())
        <section class="secao secao--destaque" id="videos" aria-labelledby="tit-galeria-videos">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">Com som</p>
                        <h2 id="tit-galeria-videos">Vídeos</h2>
                        <p>{{ $videos->count() }} {{ $videos->count() === 1 ? 'registro' : 'registros' }} em vídeo. Toque para assistir aqui mesmo.</p>
                    </div>
                </div>

                <div class="videos revela">
                    @foreach ($videos as $video)
                        @include('site._video-cartao', ['video' => $video])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="secao" id="fotos" aria-labelledby="tit-galeria-fotos">
        <div class="wrap">
            @if ($fotos->isNotEmpty())
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">Sem som</p>
                        <h2 id="tit-galeria-fotos">Fotos</h2>
                        <p>
                            {{ $fotos->total() }} {{ $fotos->total() === 1 ? 'foto' : 'fotos' }}@if ($fotos->hasPages()) · página {{ $fotos->currentPage() }} de {{ $fotos->lastPage() }}@endif.
                        </p>
                    </div>
                </div>

                <div class="galeria revela">
                    @foreach ($fotos as $foto)
                        @include('site._foto-cartao', ['foto' => $foto, 'grupo' => 'galeria', 'quando' => true])
                    @endforeach
                </div>

                @if ($fotos->hasPages())
                    <nav class="paginacao" aria-label="Páginas da galeria">
                        {{ $fotos->onEachSide(1)->links('site.paginacao') }}
                    </nav>
                @endif
            @elseif ($videos->isEmpty())

                <div class="revela nota nota--vazio">
                    <p>Ainda não há nada aqui.</p>
                    <p>
                        <a class="btn" href="{{ route('site.galeria') }}">Ver a galeria inteira</a>
                        <a class="btn" href="{{ route('site.agenda') }}">Ver a agenda</a>
                    </p>
                </div>
            @endif
        </div>
    </section>

    @include('site._foto-modal')
    @include('site._video-modal')
@endsection
