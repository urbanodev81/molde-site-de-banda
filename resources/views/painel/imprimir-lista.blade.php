<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ ucfirst($nome[1]) }} · {{ config('app.name') }}</title>

    <style>
        * { box-sizing: border-box; }
        body { margin: 24px; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; color: #111; background: #fff; }
        header { display: flex; justify-content: space-between; align-items: baseline; gap: 16px; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 16px; }
        h1 { font-size: 18px; margin: 0; }
        header p { margin: 0; font-size: 12px; color: #444; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { text-align: left; vertical-align: top; padding: 6px 8px; border-bottom: 1px solid #bbb; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid #111; }
        tr { break-inside: avoid; }
        td small { display: block; color: #444; word-break: break-all; }
        .vazio { font-size: 14px; }
        button { min-height: 44px; padding: 0 16px; font: inherit; cursor: pointer; }
        @media print { .so-tela { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <header>
        <h1>{{ config('app.name') }} · {{ $nome[1] }}</h1>
        <p>{{ $itens->count() }} {{ $itens->count() === 1 ? $nome[0] : $nome[1] }} · {{ now()->format('d/m/Y H:i') }}</p>
    </header>

    @if ($itens->isEmpty())
        <p class="vazio">Nada selecionado. Volte à lista do painel, marque as linhas e escolha "Imprimir".</p>
    @else
        <p class="so-tela"><button type="button" onclick="window.print()">Imprimir</button></p>
        <table>
            <thead><tr><th scope="col">#</th><th scope="col">{{ ucfirst($nome[0]) }}</th><th scope="col">Detalhe</th><th scope="col">Situação</th></tr></thead>
            <tbody>
                @foreach ($itens as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item['titulo'] }}@if ($item['link'])<small>{{ $item['link'] }}</small>@endif</td>
                        <td>{{ $item['detalhe'] }}</td>
                        <td>{{ $item['situacao'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
