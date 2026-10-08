@props(['brand' => null])
@php($brand = $brand ?: \App\Support\Mail\MailBrand::resolve())
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="footer-cell" align="center">
@if (trim($slot) !== '')
{{ Illuminate\Mail\Markdown::parse($slot) }}
@endif

<p class="footer-brand" style="margin: 0 0 4px;">{{ $brand['name'] }}</p>

@if (! empty($brand['footer_note']))
<p style="margin: 0 0 4px;">{{ $brand['footer_note'] }}</p>
@endif

@if (! empty($brand['support_email']))
<p style="margin: 0 0 4px;">Dúvidas? Fale com <a href="mailto:{{ $brand['support_email'] }}">{{ $brand['support_email'] }}</a></p>
@endif

<table align="center" cellpadding="0" cellspacing="0" role="presentation" style="margin: 16px auto;">
<tr>
<td class="footer-divider" width="60" style="border-top: 1px solid #e2e3ec; font-size: 0; line-height: 0;">&nbsp;</td>
</tr>
</table>

@if (! empty($brand['vendor']))
<p class="footer-legal" style="margin: 0 0 6px;">
@if ($brand['is_tenant'] && ! empty($brand['product']))
Enviado por {{ $brand['name'] }} através do {{ $brand['product']['name'] }}.<br>
{{ $brand['product']['name'] }}
@else
{{ $brand['name'] }}
@endif
é operado por {{ $brand['vendor']['legal_name'] }} ({{ $brand['vendor']['name'] }}) — CNPJ {{ $brand['vendor']['cnpj'] }}.
</p>
<p class="footer-legal" style="margin: 0 0 10px;">
<a href="{{ $brand['vendor']['url'] }}">{{ preg_replace('#^https?://#', '', $brand['vendor']['url']) }}</a>
&nbsp;·&nbsp;
<a href="mailto:{{ $brand['vendor']['email'] }}">{{ $brand['vendor']['email'] }}</a>
</p>
@endif

<p class="footer-legal" style="margin: 0;">© {{ date('Y') }} {{ $brand['name'] }}. {{ __('All rights reserved.') }}</p>
</td>
</tr>
</table>
</td>
</tr>
