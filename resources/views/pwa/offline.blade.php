<!DOCTYPE html>

<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sem conexão - A melhor banda</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        :root {
            --azul: #1a4bff;
            --fundo: #f9fafb;
            --superficie: #ffffff;
            --texto: #111827;
            --apagado: #4b5563;
            --borda: #e5e7eb;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --fundo: #030712;
                --superficie: #111827;
                --texto: #f3f4f6;
                --apagado: #9ca3af;
                --borda: #1f2937;
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: var(--fundo);
            color: var(--texto);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.5;
        }

        .cartao {
            width: 100%;
            max-width: 26rem;
            padding: 2rem;
            border: 1px solid var(--borda);
            border-radius: 1rem;
            background: var(--superficie);
            text-align: center;
        }

        .marca {
            width: 3rem;
            height: 3rem;
            margin: 0 auto 1.25rem;
        }

        h1 {
            margin: 0 0 .5rem;
            font-size: 1.125rem;
            font-weight: 600;
        }

        p {
            margin: 0;
            color: var(--apagado);
            font-size: .875rem;
        }

        button {
            margin-top: 1.5rem;
            width: 100%;
            padding: .625rem 1rem;
            border: 0;
            border-radius: .375rem;
            background: var(--azul);
            color: #fff;
            font: inherit;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover { background: #0035eb; }

        button:focus-visible {
            outline: 2px solid var(--azul);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <main class="cartao">
        <svg class="marca" viewBox="0 0 32 32" role="img" aria-label="A melhor banda">
            <rect width="32" height="32" rx="7" fill="#1a4bff"/>
            <path d="M9.2 6h9c2.6 0 4.6 1.5 4.6 4v1.2c0 1.5-.8 2.7-2 3.3 1.4.6 2.4 1.9 2.4 3.6V19c0 2.7-2.1 4.3-4.9 4.3H9.2V6Zm3.4 3v4.4h5.3c1 0 1.7-.6 1.7-1.6v-1.2c0-1-.7-1.6-1.7-1.6h-5.3Zm0 7.4v4.6h5.6c1.1 0 1.8-.7 1.8-1.7v-1.2c0-1-.7-1.7-1.8-1.7h-5.6Z" fill="#fff"/>
        </svg>

        <h1>Sem conexão</h1>
        <p>
            Este aparelho está sem internet no momento. Assim que a conexão
            voltar, é só recarregar.
        </p>

        <button type="button" onclick="location.reload()">Tentar de novo</button>
    </main>
</body>
</html>
