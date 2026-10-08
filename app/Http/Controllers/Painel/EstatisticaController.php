<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Show;
use App\Models\VisitaDiaria;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class EstatisticaController extends Controller
{
    private const JANELAS = [7, 30, 90];

    public function __invoke(Request $request): Response
    {
        $dias = (int) $request->integer('dias', 30);

        if (! in_array($dias, self::JANELAS, true)) {
            $dias = 30;
        }

        $inicio = now()->subDays($dias - 1)->startOfDay();
        $fim = now()->endOfDay();

        $noPeriodo = VisitaDiaria::query()->whereBetween('data', [$inicio, $fim]);

        $totalPeriodo = (int) (clone $noPeriodo)->sum('visitas');

        $anterior = (int) VisitaDiaria::query()
            ->whereBetween('data', [
                (clone $inicio)->subDays($dias),
                (clone $inicio)->subDay()->endOfDay(),
            ])
            ->sum('visitas');

        return Inertia::render('Painel/Estatisticas/Index', [
            'dias' => $dias,
            'janelas' => self::JANELAS,

            'kpis' => [
                'totalPeriodo' => $totalPeriodo,
                'mediaPorDia' => $dias > 0 ? (int) round($totalPeriodo / $dias) : 0,

                'variacao' => $anterior > 0
                    ? (int) round((($totalPeriodo - $anterior) / $anterior) * 100)
                    : null,

                'totalDesdeOInicio' => (int) VisitaDiaria::query()->sum('visitas'),
                'desde' => optional(VisitaDiaria::query()->min('data'))
                    ? Carbon::parse(VisitaDiaria::query()->min('data'))->format('d/m/Y')
                    : null,
            ],

            'serie' => $this->serieDiaria($inicio, $fim),
            'paginas' => $this->ranking($inicio, $fim),
            'shows' => $this->rankingDeShows($inicio, $fim),
        ]);
    }

    private function serieDiaria(Carbon $inicio, Carbon $fim): array
    {
        $porDia = VisitaDiaria::query()
            ->whereBetween('data', [$inicio, $fim])
            ->selectRaw('data, sum(visitas) as total')
            ->groupBy('data')
            ->pluck('total', 'data');

        $serie = [];

        for ($dia = $inicio->copy(); $dia->lte($fim); $dia->addDay()) {
            $chave = $dia->toDateString();

            $serie[] = [
                'dia' => $chave,
                'rotulo' => $dia->translatedFormat('d/m'),
                'visitas' => (int) ($porDia[$chave] ?? 0),
            ];
        }

        return $serie;
    }

    private function ranking(Carbon $inicio, Carbon $fim): array
    {
        $linhas = VisitaDiaria::query()
            ->whereBetween('data', [$inicio, $fim])
            ->selectRaw('caminho, rota, sum(visitas) as total')
            ->groupBy('caminho', 'rota')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $titulos = $this->titulosDeShow($linhas->pluck('caminho')->all());

        return $linhas->map(fn ($linha) => [
            'caminho' => (string) $linha->caminho,
            'titulo' => $this->titulo((string) $linha->caminho, (string) $linha->rota, $titulos),
            'visitas' => (int) $linha->total,
        ])->all();
    }

    private function rankingDeShows(Carbon $inicio, Carbon $fim): array
    {
        $linhas = VisitaDiaria::query()
            ->whereBetween('data', [$inicio, $fim])
            ->whereIn('rota', ['site.show', 'site.show.uuid'])
            ->selectRaw('caminho, sum(visitas) as total')
            ->groupBy('caminho')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $titulos = $this->titulosDeShow($linhas->pluck('caminho')->all());

        return $linhas->map(fn ($linha) => [
            'caminho' => (string) $linha->caminho,
            'titulo' => $titulos[$linha->caminho] ?? (string) $linha->caminho,
            'visitas' => (int) $linha->total,
        ])->all();
    }

    private const NOMES = [
        '/' => 'Home',
        '/a-banda' => 'A banda',
        '/agenda' => 'Agenda',
        '/repertorio' => 'Repertório',
        '/galeria' => 'Galeria',
        '/imprensa' => 'Imprensa',
        '/privacidade' => 'Privacidade',
        '/offline' => 'Offline',
    ];

    private function titulo(string $caminho, string $rota, array $titulosDeShow): string
    {
        if (isset($titulosDeShow[$caminho])) {
            return $titulosDeShow[$caminho];
        }

        if (isset(self::NOMES[$caminho])) {
            return self::NOMES[$caminho];
        }

        if (str_starts_with($caminho, '/galeria/')) {
            return 'Galeria · '.ucfirst(str_replace('-', ' ', basename($caminho)));
        }

        return $caminho;
    }

    private function titulosDeShow(array $caminhos): array
    {
        $slugs = [];

        foreach ($caminhos as $caminho) {
            if (str_starts_with($caminho, '/agenda/')) {
                $slugs[basename($caminho)] = $caminho;
            }
        }

        if ($slugs === []) {
            return [];
        }

        return Show::query()
            ->whereIn('slug', array_keys($slugs))
            ->get(['slug', 'titulo', 'comeca_em'])
            ->mapWithKeys(fn (Show $show) => [
                $slugs[$show->slug] => $show->nome(),
            ])
            ->all();
    }
}
