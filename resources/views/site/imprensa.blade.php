@extends('site.layout', [
    'tituloDaPagina' => 'Imprensa e materiais · '.($config['banda.nome'] ?? 'A melhor banda'),
    'descricaoDaPagina' => 'Formação, fotos em alta e materiais para divulgar um show da '.($config['banda.nome'] ?? 'A melhor banda').'.',
])

@section('conteudo')
    <section class="secao">
        <div class="wrap">
            <div class="revela prosa">
                <p class="olho">Para quem vai divulgar</p>
                <h1>Imprensa</h1>

                @if ($config['banda.descricao'] ?? null)
                    <p>{{ $config['banda.descricao'] }}</p>
                @endif

                @if ($integrantes->isNotEmpty())
                    <h2>Formação</h2>
                    <ul>
                        @foreach ($integrantes as $pessoa)
                            <li><strong>{{ $pessoa->comoAparece() }}</strong>@if ($pessoa->instrumento) — {{ $pessoa->instrumento }}@endif</li>
                        @endforeach
                    </ul>
                @else
                    <p class="nota">A formação entra aqui assim que a autorização de uso de nome for registrada.</p>
                @endif
            </div>
        </div>
    </section>

    @if ($publicacoes->isNotEmpty())
        <section class="secao" id="na-midia">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">O que saiu sobre a banda</p>
                        <h2>Na mídia</h2>
                    </div>
                </div>

                <ul class="redes redes--midia revela">
                    @foreach ($publicacoes as $publicacao)
                        <li>
                            <a class="cartao" href="{{ $publicacao->link }}" target="_blank" rel="noopener noreferrer">
                                @if ($publicacao->imagem_path)
                                    <img class="cartao__img" src="{{ \App\Support\Arquivos::url($publicacao->imagem_path) }}" alt="" loading="lazy" decoding="async">
                                @endif
                                <span class="cartao__k">{{ $publicacao->tipo->rotulo() }}@if ($publicacao->origem()) · {{ $publicacao->origem() }}@endif</span>
                                <span class="cartao__v">{{ $publicacao->titulo }}</span>
                                @if ($publicacao->resumo)<span class="cartao__obs">{{ $publicacao->resumo }}</span>@endif
                                <span class="cartao__obs cartao__fora">Abre no site de quem publicou<span class="so-leitor"> (nova aba)</span></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <section class="secao">
        <div class="wrap">
            <div class="secao__cabeca revela">
                <div>
                    <p class="olho">Baixe e use</p>
                    <h2>Materiais</h2>
                    <p>Uso livre para divulgar o show, com o crédito de quem fotografou.</p>
                </div>
            </div>

            @if ($materiais->isNotEmpty())
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
            @else
                <p class="nota">Os materiais estão sendo preparados. Peça pelo WhatsApp e a banda envia.</p>
            @endif
        </div>
    </section>

    @if ($fotos->isNotEmpty())
        <section class="secao">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div><p class="olho">Em alta</p><h2>Fotos</h2></div>
                </div>

                <div class="galeria revela">
                    @foreach ($fotos as $foto)
                        <figure>
                            <img src="{{ \App\Support\Arquivos::url($foto->arquivo_path) }}"
                                 alt="{{ $foto->textoAlternativo() }}" loading="lazy" decoding="async">
                            @if ($foto->credito)<figcaption>Foto: {{ $foto->credito }}</figcaption>@endif
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
