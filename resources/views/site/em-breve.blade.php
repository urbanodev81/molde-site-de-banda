@extends('site.layout-fechado')

@php
    $zapCru = preg_replace('/\D/', '', (string) ($config['contato.whatsapp'] ?? ''));
    $msg = rawurlencode((string) ($config['contato.mensagem_whatsapp'] ?? ''));
    $instagram = $config['redes.instagram'] ?? null;
    $marca = $config['banda.nome'] ?? 'A melhor banda';
@endphp

@section('conteudo')
    <section class="embreve" aria-labelledby="tit-embreve">
        <div class="wrap embreve__miolo">

            <img class="embreve__marca" src="{{ asset('sementes/lettering-700.webp') }}"
                 srcset="{{ asset('sementes/lettering-700.webp') }} 700w, {{ asset('sementes/lettering-1400.webp') }} 1400w"
                 sizes="(max-width: 640px) 78vw, 460px"
                 width="700" height="565" alt="{{ $marca }}" fetchpriority="high">

            <p class="olho">{{ $config['banda.subtitulo'] ?? 'Banda de rock · São Paulo' }}</p>
            <h1 id="tit-embreve">Em breve</h1>
            <p class="embreve__texto">
                O site novo da banda está no forno: agenda, fotos, vídeos e repertório.
                Enquanto ele não abre, a gente atende por aqui.
            </p>

            <div class="btn-linha embreve__botoes">
                @if ($zapCru)
                    <a class="btn btn--cheio" href="https://wa.me/{{ $zapCru }}?text={{ $msg }}" target="_blank" rel="noopener">
                        Chamar no WhatsApp
                    </a>
                @endif
                <a class="btn" href="#contratar">Mandar os detalhes do evento</a>
                @if ($instagram)
                    <a class="btn" href="{{ $instagram }}" target="_blank" rel="noopener">Instagram</a>
                @endif
            </div>
        </div>
    </section>

    <section class="secao contratar" id="contratar" aria-labelledby="tit-contratar">
        <div class="wrap">
            <p class="olho">Shows e eventos</p>
            <h2 id="tit-contratar">Leve as meninas<br>pro seu palco</h2>
            <p>
                Bar, aniversário, casamento, confraternização de empresa. Conte o que você está
                planejando e a gente responde com disponibilidade e proposta.
            </p>

            @include('site._form-contratar', ['tituloDoForm' => ''])
        </div>
    </section>
@endsection
