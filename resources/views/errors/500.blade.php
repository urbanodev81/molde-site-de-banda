<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Erro interno — A melhor banda</title>
<link rel="icon" href="data:,">
<style>
:root {
    color-scheme: light;
    --bg: #F9FAFB;
    --surface: #FFFFFF;
    --border: #E5E7EB;
    --fg: #111827;
    --fg-muted: #4B5563;
    --accent: #0035EB;
    --fg-on-accent: #FFFFFF;
}

@media (prefers-color-scheme: dark) {
    :root {
        color-scheme: dark;
        --bg: #030712;
        --surface: #111827;
        --border: #1F2937;
        --fg: #F3F4F6;
        --fg-muted: #9CA3AF;
        --accent: #94ADFF;
        --fg-on-accent: #0B2575;
    }
}

*, *::before, *::after { box-sizing: border-box; }

body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    background: var(--bg);
    color: var(--fg);
    font-family: Figtree, "Segoe UI", system-ui, -apple-system, sans-serif;
    font-size: 1rem;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
}

main {
    width: 100%;
    max-width: 32rem;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 1rem;
    padding: 2rem;
}

@media (min-width: 40em) { main { padding: 2.5rem; } }

.codigo {
    margin: 0 0 .75rem;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--fg-muted);
}

h1 {
    margin: 0 0 .75rem;
    font-size: 1.625rem;
    line-height: 1.2;
    font-weight: 700;
    letter-spacing: -.015em;
}

p { margin: 0 0 1rem; color: var(--fg-muted); }
p:last-of-type { margin-bottom: 1.75rem; }

a.botao {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.75rem;
    padding: .625rem 1.25rem;
    border-radius: .625rem;
    background: var(--accent);
    color: var(--fg-on-accent);
    font-size: .9375rem;
    font-weight: 700;
    text-decoration: none;
}
a.botao:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
</style>
</head>
<body>
<main>
    <p class="codigo">A melhor banda · erro 500</p>
    <h1>Erro interno</h1>
    <p>Algo quebrou do nosso lado, e não foi por sua causa. A falha já foi registrada e vamos olhar.</p>
    <p>Tentar de novo em alguns instantes costuma resolver. Se continuar, avise o suporte.</p>
    <a class="botao" href="/">Ir para o início</a>
</main>
</body>
</html>
