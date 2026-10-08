<x-mail::message>
# Fotos da A melhor banda

**{{ $remetente->name }}** separou {{ count($fotos) === 1 ? 'esta foto' : 'estas '.count($fotos).' fotos' }} para você.

@if ($recado)
> {{ $recado }}
@endif

@foreach ($fotos as $foto)
- [{{ $foto['titulo'] }}]({{ $foto['url'] }})@if ($foto['contexto']) · {{ $foto['contexto'] }}@endif @if ($foto['credito']) · Foto: {{ $foto['credito'] }}@endif

@endforeach

Para responder, é só responder este e-mail: ele chega a quem enviou.
</x-mail::message>
