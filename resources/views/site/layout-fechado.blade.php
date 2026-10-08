@php
    $marca = $config['banda.nome'] ?? 'A melhor banda';
    $titulo = $tituloDaPagina ?? "{$marca} — em breve";
    $descricao = $descricaoDaPagina ?? ($config['banda.subtitulo'] ?? '');
@endphp
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#06040C">

    <title>{{ $titulo }}</title>
    <meta name="description" content="{{ $descricao }}">

    <meta name="robots" content="noindex,nofollow">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="{{ $marca }}">
    <meta property="og:title" content="{{ $titulo }}">
    <meta property="og:description" content="{{ $descricao }}">
    <meta property="og:image" content="{{ ($config['seo.og_imagem'] ?? null) ? asset($config['seo.og_imagem']) : asset('sementes/og.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">

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
        } catch (e) {}
    </script>

    @vite(['resources/css/site.css', 'resources/css/a11y.css', 'resources/js/site.js'])
</head>
<body class="fechado">
    <a class="pular" href="#conteudo">Pular para o conteúdo</a>

    <header class="topo">
        <a class="topo__marca" href="{{ route('site.home') }}">A MELHOR <b>BANDA</b></a>
    </header>

    <main id="conteudo">
        @yield('conteudo')
    </main>

    <footer class="rodape">
        <div class="wrap rodape__linha">
            <span>{{ $marca }} · {{ $config['banda.cidade_base'] ?? 'São Paulo' }} · SP</span>
            <span>
                <a href="{{ route('site.privacidade') }}">Privacidade</a> ·

                <a href="{{ route('login') }}">Entrar</a> ·
                Site por <a href="https://exemplo.test" target="_blank" rel="noopener">Exemplo</a>
            </span>
        </div>
    </footer>
</body>
</html>
