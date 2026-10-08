@php
    $rotulo = $pessoa->nomePublico() ?? 'Integrante';
    $papel = $pessoa->instrumento ? " — {$pessoa->instrumento}" : '';
    $painel = 'artista-'.$pessoa->uuid;

    $dela = \App\Support\RedesDaPessoa::de($pessoa, $rotulo);

    $daBanda = $dela !== [] ? [] : array_values(array_filter([
        ($config['redes.instagram'] ?? null) ? ['instagram', $config['redes.instagram'], 'Instagram da banda'] : null,
        ($config['redes.facebook'] ?? null) ? ['facebook', $config['redes.facebook'], 'Facebook da banda'] : null,
        ($config['redes.youtube'] ?? null) ? ['youtube', $config['redes.youtube'], 'YouTube da banda'] : null,
        ($config['redes.spotify'] ?? null) ? ['spotify', $config['redes.spotify'], 'Spotify da banda'] : null,
        ($config['redes.tiktok'] ?? null) ? ['tiktok', $config['redes.tiktok'], 'TikTok da banda'] : null,
    ]));

    $redes = $dela !== [] ? $dela : $daBanda;

    $temCartao = $pessoa->nomePublico() || $pessoa->bio || $dela !== [];
@endphp

<div class="elenco__m"
     style="--esq:{{ $pessoa->palco_esquerda }}%;--larg:{{ $pessoa->palco_largura }}%;--base:{{ $pessoa->palco_base }}%;--i:{{ $i }}">
    @if ($temCartao)

        <button class="elenco__toque" type="button"
                aria-expanded="false" aria-controls="{{ $painel }}">
            <img src="{{ $recorte }}" width="647" height="805" decoding="async"
                 alt="{{ $pessoa->textoAlternativoNoPalco() }}">
            <span class="so-leitor">Ver detalhes de {{ $rotulo }}{{ $papel }}</span>
        </button>

        <div class="artista" id="{{ $painel }}">
            <p class="artista__nome">
                @if ($pessoa->nomePublico())
                    {{ $pessoa->comoAparece() }}
                @else
                    Nome <em class="marcador">a definir</em>
                @endif
            </p>

            @if ($pessoa->instrumento)
                <p class="artista__papel">{{ $pessoa->instrumento }}</p>
            @endif

            @if ($pessoa->bio)
                <p class="artista__bio">{{ $pessoa->bio }}</p>
            @endif

            @if ($redes !== [])

                @if ($dela === [])
                    <p class="artista__dica">Redes da banda</p>
                @endif

                <ul class="artista__redes">
                    @foreach ($redes as [$rede, $href, $titulo])
                        <li>
                            <a href="{{ $href }}" target="_blank" rel="noopener"
                               aria-label="{{ $titulo }}" title="{{ $titulo }}">
                                @include('site._icone-rede', ['rede' => $rede, 'tamanho' => 17])
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @else

        <img src="{{ $recorte }}" width="647" height="805" decoding="async"
             alt="{{ $pessoa->textoAlternativoNoPalco() }}">
    @endif
</div>
