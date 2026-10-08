@php
    $zapCru = preg_replace('/\D/', '', (string) ($config['contato.whatsapp'] ?? ''));
    $msg = rawurlencode((string) ($config['contato.mensagem_whatsapp'] ?? ''));

    $redes = array_values(array_filter([
        $zapCru ? [
            'rede' => 'whatsapp',
            'rotulo' => 'Chamar no WhatsApp',
            'href' => "https://wa.me/{$zapCru}".($msg ? "?text={$msg}" : ''),
        ] : null,
        ($config['redes.instagram'] ?? null) ? [
            'rede' => 'instagram',
            'rotulo' => 'Instagram da banda',
            'href' => $config['redes.instagram'],
        ] : null,
        ($config['redes.facebook'] ?? null) ? [
            'rede' => 'facebook',
            'rotulo' => 'Facebook da banda',
            'href' => $config['redes.facebook'],
        ] : null,
        ($config['redes.youtube'] ?? null) ? [
            'rede' => 'youtube',
            'rotulo' => 'YouTube da banda',
            'href' => $config['redes.youtube'],
        ] : null,
        ($config['redes.spotify'] ?? null) ? [
            'rede' => 'spotify',
            'rotulo' => 'Spotify da banda',
            'href' => $config['redes.spotify'],
        ] : null,
        ($config['redes.tiktok'] ?? null) ? [
            'rede' => 'tiktok',
            'rotulo' => 'TikTok da banda',
            'href' => $config['redes.tiktok'],
        ] : null,
    ]));
@endphp

@if ($redes)
    <nav class="social-flut" id="social-flut" aria-label="Redes sociais da banda">
        @foreach ($redes as $rede)
            <a href="{{ $rede['href'] }}" target="_blank" rel="noopener" aria-label="{{ $rede['rotulo'] }}" title="{{ $rede['rotulo'] }}">
                @include('site._icone-rede', ['rede' => $rede['rede']])
            </a>
        @endforeach
    </nav>
@endif
