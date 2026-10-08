@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])

@aware(['brand' => null])
@php
    $brandData = is_array($brand)
        ? $brand
        : \App\Support\Mail\MailBrand::resolve(is_string($brand) ? $brand : null);

    $background = match ($color) {
        'green', 'success' => '#059669',
        'red', 'error' => '#dc2626',
        default => $brandData['primary'],
    };

    $foreground = match ($color) {
        'green', 'success', 'red', 'error' => '#ffffff',
        default => $brandData['on_primary'],
    };
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td style="background-color: {{ $background }}; border-radius: 10px;">
<a href="{{ $url }}" class="button" target="_blank" rel="noopener" style="background-color: {{ $background }}; color: {{ $foreground }}; border-radius: 10px; display: inline-block; padding: 15px 30px; font-size: 15px; font-weight: 600; text-decoration: none;">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
