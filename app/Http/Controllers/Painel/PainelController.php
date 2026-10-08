<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Enums\StatusContratacao;
use App\Http\Controllers\Controller;
use App\Models\Contratacao;
use App\Models\Show;
use App\Models\Video;
use App\Support\Arquivos;
use App\Support\Pendencias;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PainelController extends Controller
{
    public function index(Request $request): Response
    {
        $usuario = $request->user();

        $proximo = Show::query()
            ->with('local')
            ->publicaveis()
            ->futuros()
            ->first();

        $podeVerContratacoes = (bool) $usuario?->can('contratacoes.ver');

        return Inertia::render('Painel/Inicio', [
            'proximoShow' => $proximo === null ? null : [
                'uuid' => $proximo->uuid,
                'nome' => $proximo->nome(),
                'quando' => $proximo->comeca_em->toIso8601String(),
                'quandoLegivel' => $proximo->comeca_em->translatedFormat('D, d \d\e F \à\s H\hi'),
                'endereco' => $proximo->endereco(),
                'cartaz' => Arquivos::url($proximo->cartaz_path),
            ],

            'kpis' => [
                'showsFuturos' => Show::query()->publicaveis()->futuros()->count(),
                'showsNoAno' => Show::query()->publicaveis()
                    ->whereBetween('comeca_em', [now()->startOfYear(), now()])->count(),

                'vagasDeVideo' => max(0, Video::LIMITE_NA_HOME - Video::query()->where('publicado', true)->count()),

                'pedidosEsperando' => $podeVerContratacoes
                    ? Contratacao::query()->semResposta()->count()
                    : null,
            ],

            'pedidosAbertos' => $podeVerContratacoes
                ? Contratacao::query()->abertas()->orderBy('created_at')->limit(5)->get()
                    ->map(fn (Contratacao $c) => [
                        'uuid' => $c->uuid,
                        'nome' => $c->nome,
                        'tipoEvento' => $c->tipo_evento->rotulo(),
                        'dataPretendida' => $c->data_pretendida?->format('d/m/Y'),
                        'status' => $c->status->value,
                        'statusRotulo' => $c->status->rotulo(),
                        'diasEsperando' => $c->diasEsperando(),
                    ])->all()
                : [],

            'pendencias' => Pendencias::levantar(),

            'statusPossiveis' => StatusContratacao::opcoes(),
        ]);
    }
}
