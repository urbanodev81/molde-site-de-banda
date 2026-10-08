<x-mail::message>
# Chegou um pedido de show

**{{ $pedido->nome }}** quer contratar a banda.

- **Evento:** {{ $pedido->tipo_evento->rotulo() }}
- **Data pretendida:** {{ $pedido->data_pretendida?->format('d/m/Y') ?? 'a combinar' }}
@if ($pedido->cidade)
- **Cidade:** {{ $pedido->cidade }}
@endif
@if ($pedido->local)
- **Local:** {{ $pedido->local }}
@endif
@if ($pedido->telefone)
- **WhatsApp:** {{ $pedido->telefone }}
@endif
@if ($pedido->email)
- **E-mail:** {{ $pedido->email }}
@endif

@if ($pedido->mensagem)
> {{ $pedido->mensagem }}
@endif

<x-mail::button :url="route('painel.contratacoes.show', $pedido)">
Abrir o pedido no painel
</x-mail::button>

Pedido de show tem prazo de validade curto: quem pergunta na terça sobre o
sábado contrata outra banda na quinta.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
