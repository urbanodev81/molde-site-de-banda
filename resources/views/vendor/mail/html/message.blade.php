@props(['brand' => null])
@php($brandData = is_array($brand) ? $brand : \App\Support\Mail\MailBrand::resolve(is_string($brand) ? $brand : null))
<x-mail::layout :brand="$brandData">

<x-slot:header>
<x-mail::header :url="$brandData['url']" :brand="$brandData">
{{ $brandData['name'] }}
</x-mail::header>
</x-slot:header>

{!! $slot !!}

@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

<x-slot:footer>
<x-mail::footer :brand="$brandData" />
</x-slot:footer>
</x-mail::layout>
