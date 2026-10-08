@php
    $rotulo = $nome ?? 'Integrante';
    $redes = \App\Support\RedesDaPessoa::de($pessoa, $rotulo);
    $idTitulo = $ancora.'-titulo';
@endphp

<section @class(['secao', 'perfil', 'perfil--invertido' => $invertido ?? false]) id="{{ $ancora }}" aria-labelledby="{{ $idTitulo }}">
    <div class="wrap">
        <div class="perfil__grade revela">
            @if ($retrato)
                <figure @class(['perfil__retrato', 'perfil__retrato--recorte' => $recorte ?? false])>
                    <img src="{{ $retrato }}" loading="lazy" decoding="async" alt="{{ $alt }}">
                </figure>
            @endif

            <div class="perfil__texto">
                @if ($funcao)
                    <p class="olho">{{ $funcao }}</p>
                @endif

                <h2 id="{{ $idTitulo }}">
                    @if ($nome)
                        {{ $nome }}
                    @else
                        Nome <em class="marcador">a definir</em>
                    @endif
                </h2>

                @if ($pessoa->bio)
                    <p class="perfil__bio">{{ $pessoa->bio }}</p>
                @endif

                @if ($redes !== [])
                    <ul class="perfil__redes">
                        @foreach ($redes as [$rede, $href, $titulo, $nomeDaRede])
                            <li>
                                <a class="chip" href="{{ $href }}" target="_blank" rel="noopener" aria-label="{{ $titulo }}">
                                    @include('site._icone-rede', ['rede' => $rede, 'tamanho' => 16])
                                    <span aria-hidden="true">{{ $nomeDaRede }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        @if ($pessoa->videos->isNotEmpty())
            <div class="midia-bloco revela" role="group" aria-labelledby="{{ $ancora }}-videos">
                <div class="midia-bloco__cabeca">
                    <h3 id="{{ $ancora }}-videos">Vídeos</h3>
                    <span>{{ $pessoa->videos->count() }} {{ $pessoa->videos->count() === 1 ? 'vídeo' : 'vídeos' }}</span>
                </div>
                <div class="videos">
                    @foreach ($pessoa->videos as $video)
                        @include('site._video-cartao', ['video' => $video])
                    @endforeach
                </div>
            </div>
        @endif

        @if ($pessoa->fotos->isNotEmpty())
            <div class="midia-bloco revela" role="group" aria-labelledby="{{ $ancora }}-fotos">
                <div class="midia-bloco__cabeca">
                    <h3 id="{{ $ancora }}-fotos">Fotos</h3>
                    <span>{{ $pessoa->fotos->count() }} {{ $pessoa->fotos->count() === 1 ? 'foto' : 'fotos' }} · toque para ver grande</span>
                </div>
                <div class="galeria">
                    @foreach ($pessoa->fotos as $foto)
                        @include('site._foto-cartao', ['foto' => $foto, 'grupo' => $grupo, 'quando' => true])
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
