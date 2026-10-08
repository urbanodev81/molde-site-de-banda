@aware(['brand' => null])
@php
    $brandData = is_array($brand)
        ? $brand
        : \App\Support\Mail\MailBrand::resolve(is_string($brand) ? $brand : null);
@endphp
<table class="panel" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="panel-content" style="background-color: {{ $brandData['primary_soft'] }}; border-left: 4px solid {{ $brandData['primary'] }}; border-radius: 10px; padding: 4px 20px;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="panel-item">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
</td>
</tr>
</table>
