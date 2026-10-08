<section class="palco-sec palco-sec--kit" id="topo" aria-label="{{ $config['banda.nome'] ?? 'A melhor banda' }}">
    <h1 class="so-leitor">{{ $config['seo.titulo'] ?? 'A melhor banda — banda de rock em São Paulo' }}</h1>

    <div class="sangria" aria-hidden="true"
         style="--desfoque-estreito:url('{{ asset('sementes/palco-desfoque.jpg') }}');
                --desfoque-largo:url('{{ asset('sementes/palco-largo-desfoque.jpg') }}')"></div>

    <div class="palco-area palco-area--kit" id="palco-area">
        <div class="palco palco--kit">
            <picture>
                <source media="(min-width: 900px)" type="image/webp"
                        srcset="{{ asset('sementes/palco-kit-900.webp') }} 900w, {{ asset('sementes/palco-kit.webp') }} 1104w"
                        sizes="100vw">

                <source srcset="{{ asset('sementes/palco-kit-quadrado-600.webp') }} 600w, {{ asset('sementes/palco-kit-quadrado.webp') }} 873w"
                        sizes="100vw" type="image/webp">
                <img class="palco__arte" src="{{ asset('sementes/palco-kit.webp') }}" width="1104" height="555"
                     fetchpriority="high" decoding="async"
                     alt="O palco do bar montado, com microfone, pratos, cajón e violão, e o selo da banda {{ $config['banda.nome'] ?? 'A melhor banda' }} iluminado na parede de tijolos">
            </picture>

            <span class="palco__varredura" id="varredura" aria-hidden="true"></span>

            <span class="palco__breu" aria-hidden="true"></span>

            @if ($integrantes->isNotEmpty())

                <div class="elenco elenco--no-palco" id="banda">
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
