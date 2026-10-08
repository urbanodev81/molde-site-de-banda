<section class="palco-sec palco-sec--v3" id="topo"
         aria-label="{{ $config['banda.nome'] ?? 'A melhor banda' }}">
    <h1 class="so-leitor">{{ $config['seo.titulo'] ?? 'A melhor banda — banda de rock em São Paulo' }}</h1>

    <div class="sangria sangria--v3" aria-hidden="true"
         style="background-image:url('{{ asset('sementes/palco-v3-desfoque.jpg') }}')"></div>

    <div class="palco-area palco-area--v3" id="palco-area">
        <div class="palco palco--v3">

            <div class="marca-v3" aria-hidden="true">
                <span class="marca-v3__prato"></span>
                <img class="marca-v3__tipo" alt=""
                     src="{{ asset('sementes/lettering-1400.webp') }}"
                     srcset="{{ asset('sementes/lettering-700.webp') }} 700w, {{ asset('sementes/lettering-1400.webp') }} 1400w"
                     sizes="(min-width: 1400px) 446px, (min-width: 900px) 32vw, 58vw"
                     width="1400" height="1130" fetchpriority="high" decoding="async">
            </div>

            <div class="cena-v3">

                <picture>
                    <source media="(min-width: 900px)" type="image/webp"
                            srcset="{{ asset('sementes/palco-v3-900.webp') }} 900w, {{ asset('sementes/palco-v3.webp') }} 1292w"
                            sizes="min(1292px, 100vw)">
                    <source srcset="{{ asset('sementes/palco-v3-quadrado-600.webp') }} 600w, {{ asset('sementes/palco-v3-quadrado.webp') }} 873w"
                            sizes="96vw" type="image/webp">
                    <img class="palco__arte" src="{{ asset('sementes/palco-v3.webp') }}"
                         width="1292" height="402" fetchpriority="high" decoding="async"
                         alt="O palco do bar montado: microfone antigo, pratos de bateria, cajón com a silhueta das três e violão rosa sobre o assoalho de madeira, com pisca-pisca aceso">
                </picture>

                <span class="palco__varredura" id="varredura" aria-hidden="true"></span>

                <span class="palco__breu" aria-hidden="true"></span>

                @if ($integrantes->isNotEmpty())

                    <div class="elenco elenco--v3" id="banda">
                        @foreach ($integrantes as $i => $pessoa)
                            @php $recorte = \App\Support\Arquivos::url($pessoa->recorte_path ?: $pessoa->foto_path); @endphp
                            @if ($recorte)
                                @include('site._integrante-no-palco', ['pessoa' => $pessoa, 'i' => $i, 'recorte' => $recorte])
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
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

    <fieldset class="v3-troca">
        <legend class="so-leitor">O que fica atrás do nome da banda</legend>
        <span class="v3-troca__rotulo" aria-hidden="true">Atrás do nome</span>

        <input class="so-leitor" type="radio" name="v3-recipiente" id="v3-arco" checked>
        <label for="v3-arco">Arco de neon</label>

        <input class="so-leitor" type="radio" name="v3-recipiente" id="v3-disco">
        <label for="v3-disco">Disco de galáxia</label>
    </fieldset>
</section>
