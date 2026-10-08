<section class="secao">
    <div class="wrap">
        <div class="secao__cabeca revela">
            <div>
                <p class="olho">{{ $show->jaAconteceu() ? 'Como foi' : 'Do último encontro' }}</p>
                <h2>{{ $show->jaAconteceu() ? 'A noite em imagens' : 'Para você ter ideia' }}</h2>

                @if ($show->jaAconteceu())
                    <p>

                        Só desta noite — {{ $show->comeca_em->translatedFormat('d \d\e F \d\e Y') }},
                        no {{ $show->nome() }}.
                    </p>
                @endif
            </div>
        </div>

        @if ($show->videos->isNotEmpty())
            <div class="midia-bloco revela" aria-labelledby="tit-noite-videos" role="group">
                <div class="midia-bloco__cabeca">
                    <h3 id="tit-noite-videos">Vídeos</h3>
                    <span>{{ $show->videos->count() }} {{ $show->videos->count() === 1 ? 'vídeo' : 'vídeos' }} · toque para assistir</span>
                </div>
                <div class="videos">
                    @foreach ($show->videos as $video)
                        @include('site._video-cartao', ['video' => $video])
                    @endforeach
                </div>
            </div>
        @endif

        @if ($show->fotos->isNotEmpty())
            <div class="midia-bloco revela" aria-labelledby="tit-noite-fotos" role="group">
                <div class="midia-bloco__cabeca">
                    <h3 id="tit-noite-fotos">Fotos</h3>
                    <span>{{ $show->fotos->count() }} {{ $show->fotos->count() === 1 ? 'foto' : 'fotos' }} · toque para ver grande</span>
                </div>
                <div class="galeria">
                    @foreach ($show->fotos as $foto)
                        @include('site._foto-cartao', ['foto' => $foto, 'grupo' => 'noite', 'quando' => false])
                    @endforeach
                </div>
            </div>
        @endif

        @if (($levaParaAsNoites ?? false) && $show->fotos->isNotEmpty())
            @php $abaDosShows = \App\Models\TipoGaleria::dosShows(); @endphp
            <p class="nota nota--ponte">
                <a href="{{ $abaDosShows?->publicado ? route('site.galeria.tipo', $abaDosShows) : route('site.galeria') }}">
                    Ver as fotos de todas as noites
                </a>
            </p>
        @endif
    </div>
</section>
