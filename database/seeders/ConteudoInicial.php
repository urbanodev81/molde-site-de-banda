<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Models\Foto;
use App\Models\Integrante;
use App\Models\Local;
use App\Models\Musica;
use App\Models\Pergunta;
use App\Models\Show;
use App\Support\ConfiguracaoDoSite;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ConteudoInicial extends Seeder
{
    public function run(): void
    {
        $casaDaEsquina = Local::updateOrCreate(
            ['slug' => 'casa-da-esquina'],
            [
                'nome' => 'Casa da Esquina',
                'cidade' => 'Cidade Exemplo',
                'uf' => 'SP',
                'endereco' => 'Rua das Flores, 100',
                'ativa' => true,
                'logo_path' => 'sementes/local-casa-da-esquina.webp',
                'observacoes' => 'Local de exemplo.',
            ],
        );

        Show::updateOrCreate(
            ['comeca_em' => Carbon::parse('2026-03-14 20:00:00', 'America/Sao_Paulo')],
            [
                'local_id' => $casaDaEsquina->id,
                'status' => StatusShow::Realizado,
                'tipo' => TipoShow::Publico,
                'publicado' => true,
                'destaque' => true,
                'entrada' => 'Consultar o local',
                'cartaz_path' => 'sementes/cartaz.webp',
                'observacoes_internas' => 'Show de exemplo.',
            ],
        );

        $barDoCentro = Local::updateOrCreate(
            ['slug' => 'bar-do-centro'],
            [
                'nome' => 'Bar do Centro',
                'cidade' => 'Cidade Exemplo',
                'uf' => 'SP',
                'ativa' => true,
                'logo_path' => 'sementes/local-bar-do-centro.webp',
                'observacoes' => 'Local de exemplo, sem endereço de propósito: mostra como a agenda fica quando falta dado.',
            ],
        );

        $noiteNoBar = Show::updateOrCreate(
            ['comeca_em' => Carbon::parse('2026-04-18 21:00:00', 'America/Sao_Paulo')],
            [
                'local_id' => $barDoCentro->id,
                'status' => StatusShow::Realizado,
                'tipo' => TipoShow::Publico,
                'publicado' => true,
                'entrada' => 'Consultar o local',
                'observacoes_internas' => 'Show de exemplo.',
            ],
        );

        Foto::updateOrCreate(
            [
                'show_id' => $noiteNoBar->id,
                'arquivo_path' => 'sementes/noite-bar-do-centro-2026-09-12.webp',
            ],
            [
                'publicada' => true,
                'largura' => 1448,
                'altura' => 1086,
                'legenda' => 'Foto de exemplo de uma noite de show',
            ],
        );

        $exemplo = fn (string $quem) => "Texto de exemplo — escreva aqui duas ou três linhas sobre a {$quem}: "
            .'como ela entrou na banda, o que mais gosta de tocar, o que ela traz para o show. '
            .'Trocável em Integrantes, no painel.';

        $noPalco = [
            1 => ['nome' => 'Ana', 'papel' => 'Voz', 'esquerda' => 19, 'largura' => 33, 'base' => 0],
            2 => ['nome' => 'Bia', 'papel' => 'Guitarra e voz', 'esquerda' => 44, 'largura' => 19, 'base' => 13],
            3 => ['nome' => 'Carol', 'papel' => 'Bateria', 'esquerda' => 59, 'largura' => 22, 'base' => 1],
        ];

        foreach ($noPalco as $i => $lugar) {
            Integrante::updateOrCreate(
                ['ordem' => $i],
                [
                    'nome' => $lugar['nome'],
                    'instrumento' => $lugar['papel'],
                    'bio' => $exemplo($lugar['nome']),
                    'ativa' => true,
                    'autorizacao_imagem_em' => '2026-01-10',
                    'recorte_path' => "sementes/integrante-0$i.png",
                    'palco_esquerda' => $lugar['esquerda'],
                    'palco_largura' => $lugar['largura'],
                    'palco_base' => $lugar['base'],
                ],
            );
        }

        $perguntas = [
            ['Vocês tocam em qual região?', 'Texto de exemplo: diga aqui as cidades que a banda atende.'],
            ['Quanto tempo dura o show?', 'Texto de exemplo: quantos sets, com ou sem intervalo.'],
            ['A banda leva som?', 'Texto de exemplo: o que a banda leva e o que o local precisa ter.'],
            ['Como faço para contratar?', 'Texto de exemplo: o caminho mais rápido é o WhatsApp; para contrato e nota, o formulário.'],
            ['Vocês tocam em festa particular?', 'Texto de exemplo. Evento particular entra na agenda interna e não aparece no site.'],
        ];

        foreach ($perguntas as $i => [$pergunta, $resposta]) {
            Pergunta::updateOrCreate(
                ['pergunta' => $pergunta],
                ['resposta' => $resposta, 'ordem' => $i, 'publicada' => true],
            );
        }

        Musica::updateOrCreate(
            ['titulo' => 'Repertório a preencher'],
            [
                'artista' => null,
                'publicada' => false,
                'ordem' => 0,
                'observacoes' => 'Linha de exemplo. Apague quando cadastrar o repertório.',
            ],
        );

        ConfiguracaoDoSite::gravar([
            'banda.nome' => 'A melhor banda',
            'banda.subtitulo' => 'Banda de rock · Cidade Exemplo',
            'banda.cidade_base' => 'Cidade Exemplo',
            'contato.whatsapp' => '5511900000000',
            'contato.mensagem_whatsapp' => 'Oi! Vi o site da banda e queria saber sobre disponibilidade para um evento.',
            'redes.instagram' => 'https://www.instagram.com/amelhorbanda/',
            'seo.titulo' => 'A melhor banda — banda de rock em Cidade Exemplo',
            'seo.descricao' => 'Banda de rock para bar, aniversário, casamento e evento de empresa. Veja a agenda, os vídeos e fale no WhatsApp.',
            'contratacao.raio_atendimento' => 'Cidade Exemplo e região',
        ]);
    }
}
