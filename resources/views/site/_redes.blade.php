@php
    $zapCru = preg_replace('/\D/', '', (string) ($config['contato.whatsapp'] ?? ''));
    $msg = rawurlencode((string) ($config['contato.mensagem_whatsapp'] ?? ''));

    $arroba = function (?string $url): ?string {
        if (! $url) {
            return null;
        }

        $caminho = trim((string) parse_url($url, PHP_URL_PATH), '/');

        return $caminho !== '' ? '@'.$caminho : $url;
    };

    $cartoes = array_values(array_filter([
        ($config['redes.instagram'] ?? null) ? ['Instagram', $arroba($config['redes.instagram']), $config['redes.instagram'], 'instagram'] : null,
        ($config['redes.facebook'] ?? null) ? ['Facebook', $arroba($config['redes.facebook']), $config['redes.facebook'], 'facebook'] : null,
        $zapCru ? ['WhatsApp', $config['contato.telefone'] ?: 'Chamar agora', "https://wa.me/{$zapCru}?text={$msg}", 'whatsapp'] : null,
        ($config['contato.telefone'] ?? null) ? ['Telefone', $config['contato.telefone'], 'tel:'.preg_replace('/\D/', '', $config['contato.telefone']), 'telefone'] : null,
        ($config['contato.email'] ?? null) ? ['E-mail', $config['contato.email'], 'mailto:'.$config['contato.email'], 'email'] : null,
        ($config['redes.youtube'] ?? null) ? ['YouTube', 'Ver os vídeos', $config['redes.youtube'], 'youtube'] : null,
        ($config['redes.spotify'] ?? null) ? ['Spotify', 'Ouvir', $config['redes.spotify'], 'spotify'] : null,
        ($config['redes.tiktok'] ?? null) ? ['TikTok', 'Ver', $config['redes.tiktok'], 'tiktok'] : null,
    ]));

    $reservados = array_values(array_filter([
        ($config['redes.facebook'] ?? null) ? null : 'Facebook',
        ($config['redes.youtube'] ?? null) ? null : 'YouTube',
        ($config['redes.spotify'] ?? null) ? null : 'Spotify',
        ($config['contato.email'] ?? null) ? null : 'E-mail',
    ]));
@endphp

<ul @class(['redes', 'revela' => ! ($semRevelar ?? false)])>
    @foreach ($cartoes as [$rotulo, $valor, $href, $icone])
        <li>
            <a class="cartao" href="{{ $href }}"
               @if (str_starts_with($href, 'http')) target="_blank" rel="me noopener" @endif>

                <span class="cartao__ico" aria-hidden="true">
                    @include('site._icone-rede', ['rede' => $icone, 'tamanho' => 22])
                </span>
                <span class="cartao__k">{{ $rotulo }}</span>
                <span class="cartao__v">{{ $valor }}</span>
            </a>
        </li>
    @endforeach

    @if ($reservados !== [])
        <li>
            <span class="cartao">
                <span class="cartao__k">{{ implode(' · ', $reservados) }}</span>
                <span class="cartao__v">Em breve</span>
                <span class="cartao__obs">Espaço reservado — é só mandar que a gente encaixa aqui.</span>
            </span>
        </li>
    @endif
</ul>
