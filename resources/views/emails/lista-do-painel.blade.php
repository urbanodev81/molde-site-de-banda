<x-mail::message>
# {{ ucfirst($nome[1]) }} da {{ config('app.name') }}

**{{ $remetente->name }}** separou {{ count($itens) === 1 ? ($nome[2] === 'a' ? 'esta ' : 'este ').$nome[0] : ($nome[2] === 'a' ? 'estas ' : 'estes ').count($itens).' '.$nome[1] }} para você.

@if ($recado)
> {{ $recado }}
@endif

@foreach ($itens as $item)
- @if ($item['link'])[{{ $item['titulo'] }}]({{ $item['link'] }})@else{{ $item['titulo'] }}@endif @if ($item['detalhe']) · {{ $item['detalhe'] }}@endif

@endforeach

Para responder, é só responder este e-mail: ele chega a quem enviou.
</x-mail::message>
