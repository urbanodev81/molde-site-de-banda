<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Configuracao;
use Illuminate\Support\Facades\Cache;

final class ConfiguracaoDoSite
{
    private const CHAVE_CACHE = 'configuracoes.site';

    public const CATALOGO = [

        SitePublicado::CHAVE => [
            'grupo' => 'publicacao', 'tipo' => 'booleano', 'rotulo' => 'Site aberto ao público',
            'ajuda' => 'Desmarcado, quem não está logado vê só a página "Em breve", com o WhatsApp e o '
                .'formulário de contato. Quem está logado no painel continua vendo o site inteiro.',
            'padrao_config' => 'site.publicado_por_padrao',
        ],

        'banda.nome' => [
            'grupo' => 'banda', 'tipo' => 'texto', 'rotulo' => 'Nome da banda',
            'padrao' => 'A melhor banda',
        ],
        'banda.subtitulo' => [
            'grupo' => 'banda', 'tipo' => 'texto', 'rotulo' => 'Assinatura',
            'ajuda' => 'A linha embaixo do nome. Aparece no topo e no compartilhamento.',
            'padrao' => 'Banda de rock · São Paulo',
        ],
        'banda.descricao' => [
            'grupo' => 'banda', 'tipo' => 'texto_longo', 'rotulo' => 'Quem é a banda',
            'ajuda' => 'Dois ou três parágrafos. É o texto que motor de busca e IA citam.',
        ],
        'banda.cidade_base' => [
            'grupo' => 'banda', 'tipo' => 'texto', 'rotulo' => 'Cidade base', 'padrao' => 'São Paulo',
        ],

        'banda.faixa' => [
            'grupo' => 'banda', 'tipo' => 'texto', 'rotulo' => 'Tarja rolante',
            'ajuda' => 'As palavras que passam deslizando nas faixas do site, separadas por vírgula. '
                .'Vazio: o site usa os estilos cadastrados no repertório.',
        ],

        'contato.whatsapp' => [
            'grupo' => 'contato', 'tipo' => 'telefone', 'rotulo' => 'WhatsApp',
            'ajuda' => 'Só números, com DDI e DDD: 5511900000000. É o CTA principal do site.',
            'padrao' => '5511900000000',
        ],
        'contato.mensagem_whatsapp' => [
            'grupo' => 'contato', 'tipo' => 'texto_longo', 'rotulo' => 'Mensagem pronta do WhatsApp',
            'ajuda' => 'O texto que já vem escrito quando alguém clica. O contratante só aperta enviar.',
            'padrao' => 'Oi! Vi o site de vocês e queria saber sobre disponibilidade para um evento.',
        ],
        'contato.telefone' => [
            'grupo' => 'contato', 'tipo' => 'telefone', 'rotulo' => 'Telefone',
        ],
        'contato.email' => [
            'grupo' => 'contato', 'tipo' => 'email', 'rotulo' => 'E-mail de contratação',
            'ajuda' => 'Empresa grande costuma exigir e-mail para nota e contrato.',
        ],

        'redes.instagram' => [
            'grupo' => 'redes', 'tipo' => 'url', 'rotulo' => 'Instagram',
            'padrao' => 'https://www.instagram.com/amelhorbandaoficial/',
        ],
        'redes.facebook' => [
            'grupo' => 'redes', 'tipo' => 'url', 'rotulo' => 'Facebook',
            'ajuda' => 'Onde o público de casa de show ainda está — é lá que evento e página de bar circulam.',
        ],
        'redes.youtube' => ['grupo' => 'redes', 'tipo' => 'url', 'rotulo' => 'YouTube'],
        'redes.spotify' => ['grupo' => 'redes', 'tipo' => 'url', 'rotulo' => 'Spotify'],
        'redes.tiktok' => ['grupo' => 'redes', 'tipo' => 'url', 'rotulo' => 'TikTok'],

        'contratacao.formacao' => [
            'grupo' => 'contratacao', 'tipo' => 'texto', 'rotulo' => 'Formação',
            'ajuda' => 'Ex.: "Trio — voz, violão e cajón". É a primeira pergunta de quem contrata.',
        ],
        'contratacao.duracao' => [
            'grupo' => 'contratacao', 'tipo' => 'texto', 'rotulo' => 'Duração do show',
            'ajuda' => 'Ex.: "Dois sets de 50 minutos, com intervalo".',
        ],
        'contratacao.estrutura' => [
            'grupo' => 'contratacao', 'tipo' => 'texto_longo', 'rotulo' => 'Estrutura que a banda leva',
            'ajuda' => 'O que vocês levam e o que o local precisa ter. Evita metade das conversas.',
        ],
        'contratacao.raio_atendimento' => [
            'grupo' => 'contratacao', 'tipo' => 'texto', 'rotulo' => 'Onde vocês tocam',
            'padrao' => 'São Paulo e Grande São Paulo',
        ],

        'seo.titulo' => [
            'grupo' => 'seo', 'tipo' => 'texto', 'rotulo' => 'Título da página',
            'ajuda' => 'Até ~60 caracteres. A busca real é "banda cover feminina São Paulo".',
            'padrao' => 'A melhor banda — banda de rock em São Paulo',
        ],
        'seo.descricao' => [
            'grupo' => 'seo', 'tipo' => 'texto_longo', 'rotulo' => 'Descrição',
            'ajuda' => 'Até ~155 caracteres. É o que aparece embaixo do título no Google.',
        ],
        'seo.dominio' => [
            'grupo' => 'seo', 'tipo' => 'url', 'rotulo' => 'Domínio oficial',
            'ajuda' => 'Sem barra no fim. Alimenta canonical, sitemap e imagem de compartilhamento.',
        ],
        'seo.og_imagem' => [
            'grupo' => 'seo', 'tipo' => 'texto', 'rotulo' => 'Imagem de compartilhamento',
            'ajuda' => '1200×630. É o que aparece quando o link é colado no WhatsApp.',
        ],
        'seo.latitude' => ['grupo' => 'seo', 'tipo' => 'texto', 'rotulo' => 'Latitude', 'padrao' => '-23.5505'],
        'seo.longitude' => ['grupo' => 'seo', 'tipo' => 'texto', 'rotulo' => 'Longitude', 'padrao' => '-46.6333'],
    ];

    public static function todas(): array
    {
        $gravadas = Cache::rememberForever(
            self::CHAVE_CACHE,
            fn () => Configuracao::query()->pluck('valor', 'chave')->all(),
        );

        $saida = [];

        foreach (self::CATALOGO as $chave => $meta) {
            $valor = $gravadas[$chave] ?? null;

            if (($valor === null || $valor === '') && isset($meta['padrao_config'])) {
                $valor = config($meta['padrao_config']) ? '1' : '0';
            }

            $saida[$chave] = ($valor === null || $valor === '') ? ($meta['padrao'] ?? null) : $valor;
        }

        return $saida;
    }

    public static function valor(string $chave, ?string $padrao = null): ?string
    {
        if (! array_key_exists($chave, self::CATALOGO)) {
            return $padrao;
        }

        return self::todas()[$chave] ?? $padrao;
    }

    public static function gravar(array $valores): void
    {
        foreach ($valores as $chave => $valor) {
            if (! array_key_exists($chave, self::CATALOGO)) {
                continue;
            }

            if (self::CATALOGO[$chave]['tipo'] === 'booleano') {
                $valor = filter_var($valor, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }

            Configuracao::updateOrCreate(
                ['chave' => $chave],
                [
                    'valor' => ($valor === null || $valor === '') ? null : (string) $valor,
                    'grupo' => self::CATALOGO[$chave]['grupo'],
                ],
            );
        }

        self::esquecer();
    }

    public static function esquecer(): void
    {
        Cache::forget(self::CHAVE_CACHE);
    }

    public static function regras(): array
    {
        $regras = [];

        foreach (self::CATALOGO as $chave => $meta) {
            $regras['valores.'.str_replace('.', '\\.', $chave)] = match ($meta['tipo']) {
                'email' => ['nullable', 'email', 'max:255'],
                'url' => ['nullable', 'url', 'max:500'],
                'telefone' => ['nullable', 'string', 'max:20'],
                'numero' => ['nullable', 'numeric'],
                'texto_longo' => ['nullable', 'string', 'max:5000'],
                'booleano' => ['nullable', 'boolean'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        return $regras;
    }

    public static function paraTela(): array
    {
        $valores = self::todas();
        $grupos = [];

        foreach (self::CATALOGO as $chave => $meta) {
            $grupos[$meta['grupo']][] = [
                'chave' => $chave,
                'tipo' => $meta['tipo'],
                'rotulo' => $meta['rotulo'],
                'ajuda' => $meta['ajuda'] ?? null,
                'valor' => $valores[$chave],
            ];
        }

        return $grupos;
    }

    public static function rotulosDosGrupos(): array
    {
        return [
            'publicacao' => 'Publicação',
            'banda' => 'A banda',
            'contato' => 'Contato',
            'redes' => 'Redes',
            'contratacao' => 'Contratação',
            'seo' => 'Busca e compartilhamento',
        ];
    }
}
