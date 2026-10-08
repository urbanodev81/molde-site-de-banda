@php
    $marca = $config['banda.nome'] ?? 'A melhor banda';
    $dominio = rtrim($config['seo.dominio'] ?? config('app.url'), '/');
    $titulo = $tituloDaPagina ?? ($config['seo.titulo'] ?? $marca);
    $descricao = $descricaoDaPagina ?? ($config['seo.descricao'] ?? '');
    $whatsapp = preg_replace('/\D/', '', (string) ($config['contato.whatsapp'] ?? ''));
    $mensagem = rawurlencode((string) ($config['contato.mensagem_whatsapp'] ?? ''));
    $zap = $whatsapp ? "https://wa.me/{$whatsapp}?text={$mensagem}" : null;

    $naHome = $paginaDaHome ?? false;
    $ancora = fn (string $id) => ($naHome ? '' : route('site.home')).'#'.$id;

    $menu = [
        ['rotulo' => 'Início', 'href' => route('site.home'), 'atual' => $naHome],

        ['rotulo' => 'A banda', 'href' => route('site.banda'), 'atual' => request()->routeIs('site.banda')],
        ['rotulo' => 'Agenda', 'href' => $ancora('agenda'), 'atual' => false],
        ['rotulo' => 'Galeria', 'href' => route('site.galeria'), 'atual' => request()->routeIs('site.galeria*')],
        ['rotulo' => 'Repertório', 'href' => route('site.repertorio'), 'atual' => request()->routeIs('site.repertorio')],
        ['rotulo' => 'Dúvidas', 'href' => $ancora('duvidas'), 'atual' => false],
        ['rotulo' => 'Contato', 'href' => $ancora('contato'), 'atual' => false, 'modal' => $naHome ? null : 'modal-contato'],
    ];
@endphp
<!DOCTYPE html>

<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#06040C">

    <title>{{ $titulo }}</title>
    <meta name="description" content="{{ $descricao }}">
    <link rel="canonical" href="{{ $dominio }}{{ request()->getPathInfo() }}">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1">

    <meta property="og:type" content="{{ $ogTipo ?? 'website' }}">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="{{ $marca }}">
    <meta property="og:title" content="{{ $titulo }}">
    <meta property="og:description" content="{{ $descricao }}">
    <meta property="og:url" content="{{ $dominio }}{{ request()->getPathInfo() }}">
    <meta property="og:image" content="{{ $config['seo.og_imagem'] ? asset($config['seo.og_imagem']) : asset('sementes/og.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $marca }} — {{ $config['banda.subtitulo'] ?? 'banda de rock' }}">
    <meta name="twitter:card" content="summary_large_image">

    <meta name="geo.region" content="BR-SP">
    <meta name="geo.placename" content="{{ $config['banda.cidade_base'] ?? 'São Paulo' }}">
    @if (($config['seo.latitude'] ?? null) && ($config['seo.longitude'] ?? null))
        <meta name="geo.position" content="{{ $config['seo.latitude'] }};{{ $config['seo.longitude'] }}">
        <meta name="ICBM" content="{{ $config['seo.latitude'] }}, {{ $config['seo.longitude'] }}">
    @endif

    <link rel="icon" href="{{ asset('sementes/favicon-32.png') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('sementes/apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    <link href="https://fonts.bunny.net/css?family=big-shoulders-display:500,700,800,900|barlow-condensed:500,600,700|barlow:400,500,600&display=swap" rel="stylesheet">

    <script>
        document.documentElement.classList.add('js');

        try {
            if (localStorage.getItem('banda.tema') === 'claro') {
                document.documentElement.dataset.tema = 'claro';
            }

            if (localStorage.getItem('banda-a11y-pausar-animacoes') === '1') {
                document.documentElement.classList.add('a11y-pausar-animacoes');
            }

            var paradas = JSON.parse(localStorage.getItem('banda.animacoes-paradas') || '[]');
            if (Array.isArray(paradas)) {
                paradas.forEach(function (chave) {
                    if (/^[a-z]+$/.test(chave)) {
                        document.documentElement.classList.add('sem-anim-' + chave);
                    }
                });
            }
        } catch (e) {}
    </script>

    @vite(array_merge(['resources/css/site.css', 'resources/css/a11y.css'], $folhasExtras ?? [], ['resources/js/site.js']))

    @stack('dados-estruturados')
</head>
<body>
    <a class="pular" href="#conteudo">Pular para o conteúdo</a>

    @unless (\App\Support\SitePublicado::aberto())
        <p class="aviso-previa" role="status">
            <strong>Prévia.</strong> O site está fechado: quem não está logado vê a página "Em breve".
            @can('configuracoes.gerenciar')
                <a href="{{ route('painel.configuracoes.edit') }}">Abrir o site</a>
            @endcan
        </p>
    @endunless

    @php

        $rostos = \Illuminate\Support\Facades\Cache::remember('cortina.rostos', now()->addMinutes(10), function () {
            return \App\Models\Integrante::query()->noPalco()->get()
                ->map(fn ($i) => \App\Support\Arquivos::url($i->recorte_path ?: $i->foto_path))
                ->filter()->values()->all();
        });

        $rostos = $rostos ?: [asset('sementes/logo-480.webp')];
    @endphp

    <div class="cortina" id="cortina" aria-hidden="true"
         data-chegada="{{ $naHome ? '1' : '0' }}"
         data-rostos="{{ json_encode($rostos, JSON_UNESCAPED_SLASHES) }}">
        <span class="cortina__e"></span>
        <span class="cortina__d"></span>

        <div class="cortina__grupo">
            <img class="cortina__rosto" id="cortina-rosto" src="" alt="" aria-hidden="true" width="180" height="224">
            <img class="cortina__marca" src="{{ asset('sementes/lettering-700.webp') }}" alt="" aria-hidden="true"
                 width="700" height="565" fetchpriority="low" decoding="async">
        </div>
    </div>

    <header class="topo">
        <a class="topo__marca" href="{{ route('site.home') }}">A MELHOR <b>BANDA</b></a>

        @if ($proximoNoTopo ?? null)
            <p class="topo__proximo">
                <a class="topo__proximo__data" href="{{ $ancora('proximo-show') }}">
                    <span>Próximo show</span>
                    <b><time datetime="{{ $proximoNoTopo['iso'] }}">{{ $proximoNoTopo['semana'] }} {{ $proximoNoTopo['dia'] }}</time></b>
                </a>
            </p>
        @endif

        <nav aria-label="Principal">
            <ul class="topo__menu">

                @foreach ($menu as $item)
                    <li>
                        <a class="topo__link" href="{{ $item['href'] }}"
                           @if ($item['atual']) aria-current="page" @endif
                           @if ($item['modal'] ?? null) data-abre-modal="{{ $item['modal'] }}" aria-haspopup="dialog" @endif>{{ $item['rotulo'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <button class="tema" id="tema" type="button" hidden aria-pressed="false"
                aria-label="Usar o tema claro">
            <svg class="tema__lua" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"></path>
            </svg>
            <svg class="tema__sol" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="4"></circle>
                <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path>
            </svg>
        </button>

        <button class="tema animacoes-topo" id="animacoes-topo" type="button" hidden
                aria-haspopup="dialog" aria-label="Parar animações" title="Parar animações"
                data-abre-animacoes>
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <rect x="6" y="5" width="4" height="14" rx="1"></rect>
                <rect x="14" y="5" width="4" height="14" rx="1"></rect>
            </svg>
        </button>

        @if (! empty($versoesDePrevia))
            <nav class="versoes" aria-label="Versões do topo em comparação">
                @foreach ($versoesDePrevia as $versao)
                    <a class="versoes__v" href="{{ $versao['url'] }}"
                       @if ($versao['atual']) aria-current="page" @endif>
                        <span class="so-leitor">{{ $versao['titulo'] }}</span>
                        <span aria-hidden="true">{{ $versao['rotulo'] }}</span>
                    </a>
                @endforeach
            </nav>
        @endif

        @if ($zap)
            <a class="btn btn--cheio topo__cta" href="{{ $zap }}" target="_blank" rel="noopener">Contratar show</a>
        @endif

        <button class="hamburguer" id="hamburguer" type="button" aria-expanded="false" aria-controls="gaveta" aria-label="Abrir menu"><i></i></button>
    </header>

    <div class="veu" id="veu" hidden></div>

    <nav class="gaveta" id="gaveta" aria-label="Menu" aria-hidden="true">
        <p class="gaveta__titulo">Menu</p>
        @foreach ($menu as $item)
            <a class="gaveta__link" href="{{ $item['href'] }}"
               @if ($item['atual']) aria-current="page" @endif
               @if ($item['modal'] ?? null) data-abre-modal="{{ $item['modal'] }}" aria-haspopup="dialog" @endif><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span> {{ $item['rotulo'] }}</a>
        @endforeach

        @if ($zap)
            <div class="gaveta__cta">

                <a class="btn btn--cheio btn--bloco" href="{{ $zap }}" target="_blank" rel="noopener">Contratar show</a>
            </div>
        @endif

        <button class="gaveta__animacoes" type="button" hidden aria-haspopup="dialog" data-abre-animacoes>
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <rect x="6" y="5" width="4" height="14" rx="1"></rect>
                <rect x="14" y="5" width="4" height="14" rx="1"></rect>
            </svg>
            Parar animações
        </button>

        <p class="gaveta__rodape">
            @if ($config['contato.telefone'] ?? null){{ $config['contato.telefone'] }}<br>@endif
            @if ($config['redes.instagram'] ?? null)&#64;{{ trim(parse_url($config['redes.instagram'], PHP_URL_PATH) ?? '', '/') }}@endif
        </p>
    </nav>

    <main id="conteudo">
        @yield('conteudo')
    </main>

    <footer class="rodape">
        <div class="wrap rodape__linha">
            <span>{{ $marca }} · {{ $config['banda.cidade_base'] ?? 'São Paulo' }} · SP</span>
            <span>
                <a href="{{ route('site.privacidade') }}">Privacidade</a> ·
                Site por <a href="https://exemplo.test" target="_blank" rel="noopener">Exemplo</a>
            </span>
        </div>
    </footer>

    @unless ($naHome)
        <dialog class="modal modal--contato" id="modal-contato" aria-labelledby="tit-modal-contato">
            <div class="modal__caixa">
                <button class="modal__x" type="button" data-fecha-modal aria-label="Fechar">&times;</button>
                @include('site._contato', ['idDoTitulo' => 'tit-modal-contato', 'noModal' => true])
            </div>
        </dialog>
    @endunless

    @include('site._social-flutuante')

    <button class="subir" id="subir" type="button" aria-label="Voltar ao topo"></button>

    @stack('fim-do-corpo')
</body>
</html>
