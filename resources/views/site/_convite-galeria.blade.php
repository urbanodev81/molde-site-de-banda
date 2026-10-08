<div class="convite revela" id="noites">
    @if ($capaDaGaleria)
        <a class="convite__imagem" href="{{ route('site.galeria') }}" tabindex="-1" aria-hidden="true">
            <img src="{{ \App\Support\Arquivos::url($capaDaGaleria->arquivo_path) }}"
                 @if ($capaDaGaleria->largura) width="{{ $capaDaGaleria->largura }}" @endif
                 @if ($capaDaGaleria->altura) height="{{ $capaDaGaleria->altura }}" @endif
                 loading="lazy" decoding="async" alt="">
        </a>
    @endif

    <div class="convite__corpo">
        <h3 class="convite__titulo">As noites em imagens</h3>
        <p>
            Cada show vira foto e vídeo. Estão todos na galeria, separados por
            show, ensaio, gravação e bastidor — e o álbum completo sai primeiro
            nas redes da banda.
        </p>

        <div class="convite__acoes">
            <a class="btn btn--cheio" href="{{ route('site.galeria') }}">Ver galeria</a>
            @if ($config['redes.instagram'] ?? null)
                <a class="btn" href="{{ $config['redes.instagram'] }}" target="_blank" rel="noopener">
                    @include('site._icone-rede', ['rede' => 'instagram', 'tamanho' => 18])
                    Ver no Instagram
                </a>
            @endif
        </div>
    </div>
</div>
