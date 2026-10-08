@php $dominio = rtrim($config['seo.dominio'] ?? config('app.url'), '/'); @endphp
# {{ $config['banda.nome'] ?? 'A melhor banda' }}

> {{ $config['seo.descricao'] ?? $config['banda.subtitulo'] ?? '' }}

Resumo em texto puro para motores generativos citarem sem depender de renderizar
a página. Gerado do banco a cada requisição — o que está aqui é o que está no ar.

## A banda

@if ($config['banda.descricao'] ?? null)
{{ $config['banda.descricao'] }}
@endif

- Cidade base: {{ $config['banda.cidade_base'] ?? 'São Paulo' }}
- Onde toca: {{ $config['contratacao.raio_atendimento'] ?? 'São Paulo e região' }}
@if ($config['contratacao.formacao'] ?? null)
- Formação: {{ $config['contratacao.formacao'] }}
@endif
@if ($config['contratacao.duracao'] ?? null)
- Duração do show: {{ $config['contratacao.duracao'] }}
@endif

## Próximos shows

@forelse ($futuros as $show)
- {{ $show->comeca_em->format('d/m/Y \à\s H\hi') }} — {{ $show->nome() }}@if ($show->endereco()), {{ $show->endereco() }}@endif
@empty
- Nenhuma data confirmada no momento.
@endforelse

## Repertório

@forelse ($musicas as $musica)
- {{ $musica->linha() }}
@empty
- A lista está sendo montada.
@endforelse

## Dúvidas frequentes

@foreach ($perguntas as $pergunta)
### {{ $pergunta->pergunta }}
{{ $pergunta->resposta }}

@endforeach
@if ($publicacoes->isNotEmpty())
## Na mídia

@foreach ($publicacoes as $publicacao)
- {{ $publicacao->titulo }}@if ($publicacao->origem()) ({{ $publicacao->origem() }})@endif: {{ $publicacao->link }}
@endforeach

@endif
## Contato

@if ($config['contato.whatsapp'] ?? null)
- WhatsApp: https://wa.me/{{ preg_replace('/\D/', '', $config['contato.whatsapp']) }}
@endif
@if ($config['contato.email'] ?? null)
- E-mail: {{ $config['contato.email'] }}
@endif
@if ($config['redes.instagram'] ?? null)
- Instagram: {{ $config['redes.instagram'] }}
@endif
- Site: {{ $dominio }}
- Agenda: {{ $dominio }}/agenda
