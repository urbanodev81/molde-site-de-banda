@php

    $escrita = ($aceitaConfig ?? false)
        ? collect(explode(',', (string) ($config['banda.faixa'] ?? '')))->map(fn ($i) => trim($i))->filter()
        : collect();

    $itens = $escrita->isNotEmpty() ? $escrita->values() : collect($itens ?? [])->filter()->values();

    if ($itens->isEmpty()) {
        $itens = collect(['Rock clássico', 'Pop rock', 'MPB pesada', $config['banda.cidade_base'] ?? 'São Paulo']);
    }

    $linha = $itens->map(fn ($i) => e($i))->implode(' <b>★</b> ');
@endphp

<div class="faixa {{ $variante ?? '' }}" aria-hidden="true">
    <div class="faixa__trilho">
        <span>{!! $linha !!} <b>★</b> </span>
        <span>{!! $linha !!} <b>★</b> </span>
    </div>
</div>
