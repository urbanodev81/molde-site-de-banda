<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Integrante;
use App\Models\Musica;
use App\Models\Show;
use App\Models\Video;

final class Pendencias
{
    public static function levantar(): array
    {
        $pendencias = [];

        $futuros = Show::query()->publicaveis()->futuros()->count();

        if ($futuros === 0) {
            $pendencias[] = [
                'chave' => 'agenda-vazia',
                'titulo' => 'Nenhum show futuro na agenda',
                'detalhe' => 'É o maior risco do projeto. Um site de banda sem próxima data comunica que a banda parou — '
                    .'e quem abre o link da bio procura exatamente isso.',
                'gravidade' => 'alta',
                'rota' => 'painel.shows.create',
            ];
        } elseif ($futuros === 1) {
            $pendencias[] = [
                'chave' => 'agenda-curta',
                'titulo' => 'Só uma data futura confirmada',
                'detalhe' => 'Passado esse show, a agenda fica vazia. Vale cadastrar a próxima antes.',
                'gravidade' => 'media',
                'rota' => 'painel.shows.create',
            ];
        }

        $semAutorizacao = Integrante::query()->where('ativa', true)
            ->whereNull('autorizacao_imagem_em')->count();

        if ($semAutorizacao > 0) {
            $pendencias[] = [
                'chave' => 'autorizacao-imagem',
                'titulo' => $semAutorizacao === 1
                    ? 'Uma integrante sem autorização de imagem'
                    : "$semAutorizacao integrantes sem autorização de imagem",
                'detalhe' => 'Nome e rosto no ar exigem o "ok" de cada uma, por escrito, ainda que informal. '
                    .'Sem a autorização registrada elas não aparecem no site — o palco da home fica sem ninguém.',
                'gravidade' => 'alta',
                'rota' => 'painel.integrantes.index',
            ];
        }

        $semInstrumento = Integrante::query()->where('ativa', true)
            ->where(fn ($q) => $q->whereNull('instrumento')->orWhere('instrumento', ''))->count();

        if ($semInstrumento > 0) {
            $pendencias[] = [
                'chave' => 'instrumentos',
                'titulo' => 'Falta o instrumento de quem toca o quê',
                'detalhe' => 'É a segunda coisa que um contratante lê depois do nome, e o que dá a formação da banda.',
                'gravidade' => 'media',
                'rota' => 'painel.integrantes.index',
            ];
        }

        $demonstracoes = Video::query()->where('publicado', true)->where('demonstracao', true)->count();
        $reais = Video::query()->where('publicado', true)->where('demonstracao', false)->count();

        if ($reais === 0) {
            $pendencias[] = [
                'chave' => 'video-real',
                'titulo' => 'Nenhum vídeo da banda de verdade',
                'detalhe' => $demonstracoes > 0
                    ? "As $demonstracoes vagas preenchidas são clipe de demonstração, não a banda tocando. "
                        .'Um contratante decide vendo e ouvindo: três vídeos de celular na horizontal já resolvem.'
                    : 'A aba de vídeos é o que mais converte contratação, e ela está vazia.',
                'gravidade' => 'alta',
                'rota' => 'painel.videos.index',
            ];
        }

        $musicas = Musica::query()->where('publicada', true)->count();

        if ($musicas < 15) {
            $pendencias[] = [
                'chave' => 'repertorio',
                'titulo' => $musicas === 0
                    ? 'Repertório em branco'
                    : "Repertório com $musicas música(s) — a análise pede de 15 a 20",
                'detalhe' => 'É a segunda pergunta de todo contratante, logo depois da data.',
                'gravidade' => 'media',
                'rota' => 'painel.musicas.index',
            ];
        }

        $config = ConfiguracaoDoSite::todas();

        foreach ([
            'contato.email' => ['E-mail de contratação não preenchido',
                'Empresa grande costuma exigir e-mail para nota e contrato. O WhatsApp resolve o resto.', 'baixa'],
            'seo.dominio' => ['Domínio oficial não definido',
                'Sem ele o canonical, o sitemap e a imagem de compartilhamento apontam para lugar nenhum — '
                .'o link colado no WhatsApp aparece como quadrado cinza.', 'media'],
            'contratacao.formacao' => ['Formação da banda não descrita',
                'É a primeira pergunta de quem contrata: quantas pessoas e o que cada uma toca.', 'media'],
            'contratacao.estrutura' => ['Estrutura que a banda leva não descrita',
                'O que vocês levam e o que o local precisa ter. Evita metade das conversas de orçamento.', 'baixa'],
        ] as $chave => [$titulo, $detalhe, $gravidade]) {
            if (blank($config[$chave] ?? null)) {
                $pendencias[] = [
                    'chave' => $chave,
                    'titulo' => $titulo,
                    'detalhe' => $detalhe,
                    'gravidade' => $gravidade,
                    'rota' => 'painel.configuracoes.edit',
                ];
            }
        }

        return $pendencias;
    }
}
