<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PoliticaRetencao;
use Illuminate\Database\Seeder;

class PoliticasDeRetencao extends Seeder
{
    public function run(): void
    {
        $politicas = [
            [
                'recurso' => 'auditoria',
                'meses' => 24,
                'justificativa' => 'Rastreabilidade de quem alterou a agenda e o conteúdo do site.',
                'nunca_expurgar' => false,
            ],
            [
                'recurso' => 'contratacao',
                'meses' => 24,
                'justificativa' => 'Dado pessoal de quem pediu orçamento. Passado o prazo, não há mais finalidade.',
                'nunca_expurgar' => false,
            ],
            [
                'recurso' => 'contratacao_interacao',
                'meses' => 24,
                'justificativa' => 'Acompanha o pedido a que pertence.',
                'nunca_expurgar' => false,
            ],
        ];

        foreach ($politicas as $politica) {
            PoliticaRetencao::updateOrCreate(['recurso' => $politica['recurso']], $politica);
        }
    }
}
