@extends('site.layout', [
    'tituloDaPagina' => 'Agenda de shows · '.($config['banda.nome'] ?? 'A melhor banda'),
    'descricaoDaPagina' => 'Onde a '.($config['banda.nome'] ?? 'A melhor banda').' toca: datas, locais e endereços em '.($config['contratacao.raio_atendimento'] ?? 'São Paulo').'.',
])

@push('dados-estruturados')
    @php
        $raiz = rtrim($config['seo.dominio'] ?? config('app.url'), '/');
        $marca = $config['banda.nome'] ?? 'A melhor banda';

        $grafo = [[
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => $raiz],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Agenda', 'item' => $raiz.'/agenda'],
            ],
        ]];

        foreach ($futuros as $evento) {
            $grafo[] = [
                '@type' => 'MusicEvent',
                'name' => $marca.' no '.$evento->nome(),
                'startDate' => $evento->comeca_em->toIso8601String(),
                'eventStatus' => 'https://schema.org/EventScheduled',
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                'location' => [
                    '@type' => 'Place',
                    'name' => $evento->nome(),
                    'address' => $evento->endereco(),
                ],
                'performer' => ['@type' => 'MusicGroup', 'name' => $marca],
            ];
        }

        $jsonLd = json_encode(['@context' => 'https://schema.org', '@graph' => $grafo],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @endphp
    <script type="application/ld+json">{!! $jsonLd !!}</script>
@endpush

@section('conteudo')
    <section class="secao">
        <div class="wrap">
            <div class="secao__cabeca revela">
                <div>
                    <p class="olho">Onde a gente toca</p>
                    <h1>Agenda</h1>
                    <p>Toda data confirmada, e o histórico de onde a banda já passou.</p>
                </div>

                <a class="btn" href="{{ route('site.home') }}#contratar"
                   data-abre-modal="modal-contratar" aria-haspopup="dialog">Quero marcar a minha</a>
            </div>

            @if ($futuros->isNotEmpty())
                <ul class="shows">
                    @foreach ($futuros as $show)
                        @include('site._show', ['show' => $show])
                    @endforeach
                </ul>
            @else
                <p class="nota">
                    Nenhuma data confirmada no momento.
                    <a href="{{ route('site.home') }}#contratar">Marque a sua.</a>
                </p>
            @endif
        </div>
    </section>

    @if ($passados->isNotEmpty())
        <section class="secao">
            <div class="wrap">
                <div class="secao__cabeca revela">
                    <div>
                        <p class="olho">Já aconteceu</p>
                        <h2>Onde a banda tocou</h2>
                    </div>
                </div>

                <ul class="quadros">
                    @foreach ($passados as $show)
                        @include('site._show-quadro', ['show' => $show])
                    @endforeach
                </ul>

                @if ($haGaleria)
                    @include('site._convite-galeria')
                @endif
            </div>
        </section>
    @endif

    <dialog class="modal" id="modal-contratar" aria-labelledby="tit-modal-contratar">
        <div class="modal__caixa">
            <button class="modal__x" type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            <h2 class="modal__titulo" id="tit-modal-contratar">Quero marcar a minha data</h2>
            <p class="modal__linha-fina">Conta o que você está planejando. A gente responde pelo WhatsApp ou pelo e-mail.</p>

            @include('site._form-contratar', ['tituloDoForm' => ''])
        </div>
    </dialog>

    @include('site._foto-modal')

@endsection
