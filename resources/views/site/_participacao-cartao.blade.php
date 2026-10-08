@php
    $redes = \App\Support\RedesDaPessoa::de($pessoa, $pessoa->nome);
    $foto = \App\Support\Arquivos::url($pessoa->foto_path);
    $tag = $titulo ?? 'h3';
@endphp

<article class="convidado">
    @if ($foto)
        <img class="convidado__foto" src="{{ $foto }}" loading="lazy" decoding="async"
             alt="{{ $pessoa->nome }}{{ $pessoa->funcao ? ', '.$pessoa->funcao : '' }}">
    @endif

    <div class="convidado__corpo">
        @if ($pessoa->funcao)
            <p class="convidado__funcao">{{ $pessoa->funcao }}</p>
        @endif
        <{{ $tag }} class="convidado__nome">{{ $pessoa->nome }}</{{ $tag }}>

        @if ($pessoa->descricao)
            <p class="convidado__descricao">{{ $pessoa->descricao }}</p>
        @endif

        @if (($comNoites ?? false) && $pessoa->shows->isNotEmpty())
            <p class="convidado__noites">
                <span>Com a banda em</span>
                @foreach ($pessoa->shows as $noite)
                    <a href="{{ route('site.show', $noite) }}">{{ $noite->nome() }} · {{ $noite->comeca_em->format('d/m/Y') }}</a>@if (! $loop->last)<span aria-hidden="true">,</span>@endif
                @endforeach
            </p>
        @endif

        @if ($redes !== [])
            <ul class="perfil__redes">
                @foreach ($redes as [$rede, $href, $rotulo, $nomeDaRede])
                    <li>
                        <a class="chip" href="{{ $href }}" target="_blank" rel="noopener" aria-label="{{ $rotulo }}">
                            @include('site._icone-rede', ['rede' => $rede, 'tamanho' => 16])
                            <span aria-hidden="true">{{ $nomeDaRede }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</article>
