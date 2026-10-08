    <section class="palco-sec" id="topo" aria-label="{{ $config['banda.nome'] ?? 'A melhor banda' }}">
        <h1 class="so-leitor">{{ $config['seo.titulo'] ?? 'A melhor banda — banda de rock em São Paulo' }}</h1>

        <div class="sangria" aria-hidden="true"
             style="--desfoque-estreito:url('{{ asset('sementes/palco-desfoque.jpg') }}');
                    --desfoque-largo:url('{{ asset('sementes/palco-largo-desfoque.jpg') }}')"></div>

        <div class="palco-area" id="palco-area">
            <div class="palco">

                <picture>
                    <source media="(min-width: 900px)" type="image/webp"
                            srcset="{{ asset('sementes/palco-largo-900.webp') }} 900w, {{ asset('sementes/palco-largo.webp') }} 1024w"
                            sizes="min(1360px, 96vw)">
                    <source srcset="{{ asset('sementes/palco-900.webp') }} 900w, {{ asset('sementes/palco.webp') }} 1400w"
                            sizes="96vw" type="image/webp">
                    <img class="palco__arte" src="{{ asset('sementes/palco.webp') }}" width="1400" height="1400"
                         fetchpriority="high" decoding="async"
                         alt="Arte da banda {{ $config['banda.nome'] ?? 'A melhor banda' }}: lettering em rosa sobre um palco com microfone antigo, cajón, pratos de bateria, violão rosa e pisca-pisca">
                </picture>

                <span class="palco__varredura" id="varredura" aria-hidden="true"></span>

                <span class="palco__breu" aria-hidden="true"></span>

                @if ($integrantes->isNotEmpty())

                    <div class="elenco" id="banda">
                        @foreach ($integrantes as $i => $pessoa)
                            @php $recorte = \App\Support\Arquivos::url($pessoa->recorte_path ?: $pessoa->foto_path); @endphp
                            @if ($recorte)
                                @include('site._integrante-no-palco', ['pessoa' => $pessoa, 'i' => $i, 'recorte' => $recorte])
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <img class="adesivo" src="{{ asset('sementes/estrela.webp') }}" alt="" aria-hidden="true"
                 style="left:-2%;top:12%" width="92" height="92" loading="lazy">
            <img class="adesivo adesivo--2" src="{{ asset('sementes/raio.webp') }}" alt="" aria-hidden="true"
                 style="right:-1%;bottom:16%" width="92" height="92" loading="lazy">

            @include('site._cortina-do-palco')
        </div>

        @if ($integrantes->isNotEmpty())
            @include('site._creditos')
        @endif
    </section>
