@php
    $zapCru = preg_replace('/\D/', '', (string) ($config['contato.whatsapp'] ?? ''));
    $msg = rawurlencode((string) ($config['contato.mensagem_whatsapp'] ?? ''));
    $telefone = $config['contato.telefone'] ?? null;

    $fatos = array_values(array_filter([
        $config['contratacao.formacao'] ?? null,
        $config['contratacao.duracao'] ?? null,
        $config['contratacao.estrutura'] ?? null,
        $config['contratacao.raio_atendimento'] ?? null,
    ]));

    $cartaz = $showEmDestaque ? \App\Support\Arquivos::url($showEmDestaque->cartaz_path) : null;
@endphp

<section class="secao contratar" id="contratar" aria-labelledby="tit-contratar">
    <div class="wrap contratar__grade">
        <div class="revela">
            <p class="olho">Shows e eventos</p>
            <h2 id="tit-contratar">Leve as meninas<br>pro seu palco</h2>
            <p>
                Bar, aniversário, casamento, confraternização de empresa. A gente monta o repertório
                junto com você e leva a energia do rock pra qualquer tamanho de local.
            </p>

            @if ($fatos !== [])
                <ul class="specs">
                    @foreach ($fatos as $fato)
                        <li>{{ $fato }}</li>
                    @endforeach
                </ul>
            @else
                <p class="nota">Formação, duração e estrutura entram assim que a banda passar — <a href="#contato">é só mandar</a>.</p>
            @endif

            <div class="btn-linha">
                @if ($zapCru)
                    <a class="btn btn--escuro" href="https://wa.me/{{ $zapCru }}?text={{ $msg }}" target="_blank" rel="noopener">
                        WhatsApp{{ $telefone ? ' '.$telefone : '' }}
                    </a>
                @endif
                @if ($telefone)
                    <a class="btn btn--escuro" href="tel:{{ preg_replace('/\D/', '', $telefone) }}">Ligar agora</a>
                @endif
            </div>

            @include('site._form-contratar')
        </div>

        @if ($cartaz)
            <figure class="cartaz revela">
                <img src="{{ $cartaz }}" width="840" height="1189" loading="lazy" decoding="async"
                     alt="Cartaz do show da {{ $config['banda.nome'] ?? 'A melhor banda' }} no {{ $showEmDestaque->nome() }}, {{ $showEmDestaque->comeca_em->translatedFormat('d \d\e F') }}">
                <figcaption class="cartaz__legenda">
                    {{ $showEmDestaque->nome() }} · {{ $showEmDestaque->comeca_em->format('d/m/Y') }}
                </figcaption>
            </figure>
        @endif
    </div>
</section>
