<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\TipoGaleria;
use App\Support\Lote\ListasEmLote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TipoGaleriaController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Painel/TiposGaleria/Index', [
            'tipos' => TipoGaleria::query()
                ->when($request->boolean('arquivados'), fn ($q) => $q->onlyTrashed())
                ->withCount(['fotos', 'videos'])
                ->orderBy('ordem')->orderBy('nome')->get()
                ->map(fn (TipoGaleria $t) => [
                    'id' => $t->id,
                    'nome' => $t->nome,
                    'slug' => $t->slug,
                    'descricao' => $t->descricao,
                    'ordem' => $t->ordem,
                    'publicado' => $t->publicado,
                    'fotos' => $t->fotos_count,
                    'videos' => $t->videos_count,

                    'ehDeShows' => $t->ehDeShows(),
                ])->all(),
            ...ListasEmLote::abas('tipos-galeria', $request),
            'podeGerenciar' => $request->user()?->can('fotos.gerenciar') ?? false,
            'grupo' => TipoGaleria::GRUPO,

            'outroGrupo' => $request->user()?->can('locais.ver') ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TipoGaleria::create($request->validate($this->regras()));

        return back()->with('sucesso', 'Tipo criado.');
    }

    public function update(Request $request, TipoGaleria $tipoGaleria): RedirectResponse
    {
        $dados = $request->validate($this->regras($tipoGaleria));

        $tipoGaleria->update($dados);

        return back()->with('sucesso', 'Tipo atualizado.');
    }

    public function destroy(TipoGaleria $tipoGaleria): RedirectResponse
    {
        if ($tipoGaleria->ehDeShows()) {
            return back()->with('erro', 'O tipo "Shows" não pode ser arquivado: é ele que junta as fotos e vídeos de todas as noites. Para tirá-lo do site, desmarque "Publicado".');
        }

        $tipoGaleria->delete();

        return back()->with('sucesso', 'Tipo arquivado. As fotos continuam no painel, sem tipo; ele está na aba Arquivados.');
    }

    private function regras(?TipoGaleria $tipo = null): array
    {
        return [
            'nome' => ['required', 'string', 'max:60', Rule::unique('tipos', 'nome')
                ->where('grupo', TipoGaleria::GRUPO)->ignore($tipo?->getKey())->whereNull('deleted_at')],
            'descricao' => ['nullable', 'string', 'max:160'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'publicado' => ['boolean'],
        ];
    }
}
