<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrigemContratacao;
use App\Enums\StatusContratacao;
use App\Enums\TipoEvento;
use App\Models\Contratacao;
use App\Models\ContratacaoInteracao;
use App\Models\Show;
use App\Models\VisitaDiaria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistoricoDeExemplo extends Seeder
{
    private const PEDIDOS = [
        [
            'nome' => 'Rodrigo Menezes',
            'email' => 'rodrigo@barlagoa.exemplo.br',
            'telefone' => '11900000000',
            'tipo_evento' => TipoEvento::Bar,
            'origem' => OrigemContratacao::Site,
            'status' => StatusContratacao::Fechado,
            'cidade' => 'São Paulo',
            'local' => 'Bar da Lagoa — Vila Madalena',
            'mensagem' => 'Vi vocês no Bar do Centro e quero fechar uma noite aqui. Tenho sexta livre no mês que vem.',
            'valor_proposto' => 1800.00,
            'dias_atras' => 47,
            'com_consentimento' => true,
            'interacoes' => [
                ['whatsapp', 'Respondi confirmando disponibilidade e mandei o release.', 46],
                ['ligacao', 'Alinhamos horário (22h) e estrutura de som — o local tem PA próprio.', 44],
                ['mudanca_status', 'Proposta enviada: R$ 1.800 com 3 horas de show.', 43],
                ['email', 'Contrato assinado. Fechado.', 40],
            ],
        ],
        [
            'nome' => 'Carla Bastos',
            'email' => 'carla.bastos@exemplo.com',
            'telefone' => '11900000000',
            'tipo_evento' => TipoEvento::Aniversario,
            'origem' => OrigemContratacao::Instagram,
            'status' => StatusContratacao::Fechado,
            'cidade' => 'Santo André',
            'local' => 'Espaço Jardim',
            'mensagem' => '40 anos do meu marido, ele é fã de Paramore e Garbage. Vocês tocam isso?',
            'valor_proposto' => 2400.00,
            'dias_atras' => 31,
            'com_consentimento' => false,
            'interacoes' => [
                ['whatsapp', 'Chegou pelo direct. Mandei o repertório — tem as duas bandas.', 30],
                ['mudanca_status', 'Proposta enviada: R$ 2.400, evento fechado com 2 horas.', 28],
                ['nota', 'Confirmou. Pediu para incluir "Misery Business" no encerramento.', 25],
            ],
        ],
        [
            'nome' => 'Fernanda Klein',
            'email' => 'fernanda.klein@exemplo.com.br',
            'telefone' => '11900000000',
            'tipo_evento' => TipoEvento::Empresa,
            'origem' => OrigemContratacao::Site,
            'status' => StatusContratacao::PropostaEnviada,
            'cidade' => 'São Paulo',
            'local' => 'Confraternização — a definir',
            'mensagem' => 'Somos uma empresa de tecnologia, cerca de 200 pessoas. Precisamos de nota fiscal.',
            'valor_proposto' => 4500.00,
            'dias_atras' => 12,
            'com_consentimento' => true,
            'interacoes' => [
                ['email', 'Pediu proposta formal com CNPJ e dados para nota.', 11],
                ['reuniao', 'Call de 20 min com o RH: querem 1h30 de show, entre 20h e 22h.', 8],
                ['mudanca_status', 'Proposta enviada: R$ 4.500 incluindo sonorização.', 6],
            ],
        ],
        [
            'nome' => 'Marcos Oliveira',
            'email' => 'marcos@exemplo.net',
            'telefone' => '11900000000',
            'tipo_evento' => TipoEvento::Casamento,
            'origem' => OrigemContratacao::Indicacao,
            'status' => StatusContratacao::Perdido,
            'cidade' => 'Cotia',
            'local' => 'Sítio Recanto',
            'mensagem' => 'Indicação da Carla. Casamento no campo, queremos banda na festa depois da cerimônia.',
            'valor_proposto' => 3200.00,
            'motivo_perda' => 'Fechou com DJ por preço',
            'dias_atras' => 20,
            'com_consentimento' => false,
            'interacoes' => [
                ['whatsapp', 'Expliquei a estrutura mínima que a banda precisa no sítio.', 19],
                ['mudanca_status', 'Proposta enviada: R$ 3.200 com deslocamento.', 17],
                ['ligacao', 'Achou acima do orçamento. Vai fechar com DJ.', 14],
            ],
        ],
        [
            'nome' => 'Juliana Arantes',
            'email' => 'ju.arantes@exemplo.com',
            'telefone' => '11900000000',
            'tipo_evento' => TipoEvento::Bar,
            'origem' => OrigemContratacao::Whatsapp,
            'status' => StatusContratacao::EmContato,
            'cidade' => 'São Paulo',
            'local' => 'Botequim do Beco — Pinheiros',
            'mensagem' => 'Programamos rock às quintas. Vocês topam uma data de teste?',
            'valor_proposto' => null,
            'dias_atras' => 5,
            'com_consentimento' => false,
            'interacoes' => [
                ['whatsapp', 'Perguntei qual quinta e qual o cachê praticado pelo local.', 4],
            ],
        ],
        [
            'nome' => 'Diretório Acadêmico — Engenharia',
            'email' => 'da.eng@exemplo.edu.br',
            'telefone' => '11900000000',
            'tipo_evento' => TipoEvento::Formatura,
            'origem' => OrigemContratacao::Site,
            'status' => StatusContratacao::Novo,
            'cidade' => 'São Bernardo do Campo',
            'local' => 'Buffet Vila Real',
            'mensagem' => 'Formatura em dezembro. Queremos saber valores e se vocês têm equipe de som.',
            'valor_proposto' => null,
            'dias_atras' => 2,
            'com_consentimento' => true,
            'interacoes' => [],
        ],
        [
            'nome' => 'Paulo Renato',
            'email' => 'paulo.renato@exemplo.com',
            'telefone' => '11900000000',
            'tipo_evento' => TipoEvento::Outro,
            'origem' => OrigemContratacao::Instagram,
            'status' => StatusContratacao::Novo,
            'cidade' => 'Guarulhos',
            'local' => 'Festival de rua do bairro',
            'mensagem' => 'Estamos montando um festival local. Vocês tocariam num palco aberto?',
            'valor_proposto' => null,
            'dias_atras' => 1,
            'com_consentimento' => false,
            'interacoes' => [],
        ],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('HistoricoDeExemplo não roda em produção — e é de propósito. Nada foi criado.');

            return;
        }

        $this->pedidos();
        $this->visitas();
    }

    private function pedidos(): void
    {
        foreach (self::PEDIDOS as $dados) {
            $criadoEm = now()->subDays($dados['dias_atras'])->setTime(random_int(9, 21), random_int(0, 59));

            $pedido = Contratacao::updateOrCreate(
                ['email' => $dados['email']],
                [
                    'nome' => $dados['nome'],
                    'telefone' => $dados['telefone'],
                    'tipo_evento' => $dados['tipo_evento'],
                    'origem' => $dados['origem'],
                    'status' => $dados['status'],
                    'cidade' => $dados['cidade'],
                    'local' => $dados['local'],

                    'mensagem' => '[exemplo] '.$dados['mensagem'],
                    'valor_proposto' => $dados['valor_proposto'],
                    'motivo_perda' => $dados['motivo_perda'] ?? null,
                    'data_pretendida' => now()->addDays(random_int(20, 120))->toDateString(),

                    'consentimento_em' => $dados['com_consentimento'] ? $criadoEm : null,
                    'consentimento_ip' => $dados['com_consentimento'] ? '203.0.113.10' : null,

                    'created_at' => $criadoEm,
                    'updated_at' => $criadoEm,
                ],
            );

            ContratacaoInteracao::query()->where('contratacao_id', $pedido->id)->delete();

            foreach ($dados['interacoes'] as [$tipo, $descricao, $diasAtras]) {
                $quando = now()->subDays($diasAtras)->setTime(random_int(9, 20), random_int(0, 59));

                ContratacaoInteracao::create([
                    'contratacao_id' => $pedido->id,
                    'user_id' => null,
                    'user_nome' => 'Produção',
                    'tipo' => $tipo,
                    'descricao' => $descricao,
                    'ocorrido_em' => $quando,
                    'created_at' => $quando,
                    'updated_at' => $quando,
                ]);
            }
        }

        $this->command?->info('Pedidos de exemplo: '.count(self::PEDIDOS).' (mensagem marcada com [exemplo]).');
    }

    private function visitas(): void
    {
        $diasDeShow = Show::query()
            ->whereNotNull('comeca_em')
            ->pluck('comeca_em')
            ->map(fn ($data) => $data->toDateString())
            ->all();

        $paginas = [
            ['/', 'site.home', 100],
            ['/agenda', 'site.agenda', 55],
            ['/a-banda', 'site.banda', 38],
            ['/repertorio', 'site.repertorio', 22],
            ['/galeria', 'site.galeria', 18],
            ['/imprensa', 'site.imprensa', 6],
            ['/privacidade', 'site.privacidade', 2],
        ];

        $shows = Show::query()->publicaveis()->whereNotNull('slug')->get(['slug', 'comeca_em']);

        DB::transaction(function () use ($paginas, $shows, $diasDeShow) {
            for ($i = 89; $i >= 0; $i--) {
                $dia = now()->subDays($i)->startOfDay();
                $chave = $dia->toDateString();

                $peso = $dia->isWeekend() ? 1.4 : 1.0;

                foreach ($diasDeShow as $show) {
                    $distancia = $dia->diffInDays($show, false);

                    if ($distancia >= 0 && $distancia <= 3) {
                        $peso += 2.2;
                    } elseif ($distancia < 0 && $distancia >= -2) {
                        $peso += 1.1;
                    }
                }

                foreach ($paginas as [$caminho, $rota, $base]) {
                    $visitas = (int) round(($base / 30) * $peso * (random_int(60, 145) / 100));

                    if ($visitas <= 0) {
                        continue;
                    }

                    $this->gravar($chave, $caminho, $rota, $visitas);
                }

                foreach ($shows as $show) {
                    $distancia = $dia->diffInDays($show->comeca_em, false);

                    if ($distancia > 20 || $distancia < -10) {
                        continue;
                    }

                    $visitas = (int) round(max(0, 6 - abs($distancia) / 4) * (random_int(50, 160) / 100));

                    if ($visitas > 0) {
                        $this->gravar($chave, '/agenda/'.$show->slug, 'site.show', $visitas);
                    }
                }
            }
        });

        $total = (int) VisitaDiaria::query()->sum('visitas');

        $this->command?->info("Visitas de exemplo: {$total} em 90 dias.");
        $this->command?->warn('⚠️ Números FICTÍCIOS. Antes de o site receber tráfego real, limpe com HistoricoDeExemplo::limparVisitas().');
    }

    private function gravar(string $dia, string $caminho, string $rota, int $visitas): void
    {
        DB::statement(
            'insert into visitas_diarias (data, caminho, rota, visitas, created_at, updated_at)
             values (?, ?, ?, ?, now(), now())
             on conflict (data, caminho) do update set visitas = excluded.visitas, updated_at = now()',
            [$dia, $caminho, $rota, $visitas],
        );
    }

    public static function limparVisitas(): int
    {
        return VisitaDiaria::query()->delete();
    }
}
