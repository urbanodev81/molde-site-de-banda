@props(['url' => null, 'brand' => null])
@php
    $brand = $brand ?: \App\Support\Mail\MailBrand::resolve();
    $url = $url ?: $brand['url'];
@endphp
<tr>
<td class="header">
<table class="brand-bar" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="brand-bar-cell" style="background-color: {{ $brand['primary'] }}; border-radius: 14px 14px 0 0;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
@if ($brand['logo'])

<td width="72" valign="middle" style="padding-right: 14px;">
<a href="{{ $url }}" style="display: inline-block; background-color: #ffffff; border-radius: 12px; padding: 7px 9px; text-decoration: none;">
<img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" class="brand-logo" style="display: block; max-height: 34px; border: none;">
</a>
</td>
@else

<td width="60" valign="middle" style="padding-right: 14px;">
<table cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="brand-mark" width="46" height="46" align="center" valign="middle" style="background-color: {{ $brand['on_primary'] }}; color: {{ $brand['primary'] }}; border-radius: 12px; width: 46px; height: 46px; text-align: center; font-size: 18px; font-weight: 700;">{{ $brand['initials'] }}</td>
</tr>
</table>
</td>
@endif
<td valign="middle">
<a href="{{ $url }}" style="text-decoration: none;">
<p class="brand-name" style="color: {{ $brand['on_primary'] }}; margin: 0; font-size: 19px; font-weight: 700;">{!! trim($slot) !== '' ? $slot : e($brand['name']) !!}</p>
@if (! empty($brand['tagline']))
<p class="brand-tagline" style="color: {{ $brand['on_primary_muted'] }}; margin: 3px 0 0; font-size: 12px; font-weight: 500;">{{ $brand['tagline'] }}</p>
@endif
</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
