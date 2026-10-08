@extends('site.layout', [
    'tituloDaPagina' => 'A banda · '.($config['banda.nome'] ?? 'A melhor banda'),
    'descricaoDaPagina' => 'Quem são as integrantes da '.($config['banda.nome'] ?? 'A melhor banda').': função na banda, história, redes, fotos e vídeos de cada uma.',
])

@section('conteudo')
    <section class="secao">
        <div class="wrap">
            <div class="secao__cabeca revela">
                <div>
                    <p class="olho">A banda</p>
                    <h1>As meninas</h1>
                    <p>{{ $config['banda.descricao'] ?? 'Rock de São Paulo.' }}</p>
                </div>
                <a class="btn" href="{{ route('site.home') }}#contratar">Quero contratar</a>
            </div>

            @if ($integrantes->count() > 1 || $participacoes->isNotEmpty())
                <nav class="abas revela" aria-label="Integrantes">
                    @foreach ($integrantes as $pessoa)
                        <a class="abas__item" href="#integrante-{{ $loop->iteration }}">{{ $pessoa->nomePublico() ?? 'Integrante '.$loop->iteration }}</a>
                    @endforeach
                    @if ($participacoes->isNotEmpty())
                        <a class="abas__item" href="#participacoes">Participações especiais</a>
                    @endif
                </nav>
            @endif
        </div>
    </section>

    @forelse ($integrantes as $pessoa)
        @include('site._perfil', [
            'pessoa' => $pessoa,
            'ancora' => 'integrante-'.$loop->iteration,
            'nome' => $pessoa->nomePublico(),
            'funcao' => $pessoa->instrumento,
            'retrato' => \App\Support\Arquivos::url($pessoa->foto_path ?: $pessoa->recorte_path),
            'recorte' => ! $pessoa->foto_path && $pessoa->recorte_path,
            'alt' => $pessoa->textoAlternativoNoPalco(),
            'grupo' => 'integrante-'.$loop->iteration,
            'invertido' => $loop->even,
        ])
    @empty
        <section class="secao">
            <div class="wrap">
                <p class="nota nota--vazio revela">
                    A apresentação das integrantes está chegando.
                    <a href="{{ route('site.galeria') }}">Enquanto isso, veja a galeria.</a>
                </p>
            </div>
        </section>
    @endforelse

    @if ($participacoes->isNotEmpty())
        <section class="secao perfil" id="participacoes" aria-labelledby="tit-participacoes">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">Quem já subiu ao palco com a gente</p>
                        <h2 id="tit-participacoes">Participações especiais</h2>
                    </div>
                </div>
                <div class="convidados revela">
                    @foreach ($participacoes as $pessoa)
                        @include('site._participacao-cartao', ['pessoa' => $pessoa, 'comNoites' => true])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('site._foto-modal')
    @include('site._video-modal')
@endsection
