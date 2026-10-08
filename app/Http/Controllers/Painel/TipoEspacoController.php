<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\TipoEspaco;
use App\Support\Lote\ListasEmLote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TipoEspacoController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Painel/TiposGaleria/Index', [
            'tipos' => TipoEspaco::query()
                ->when($request->boolean('arquivados'), fn ($q) => $q->onlyTrashed())
                ->withCount('locais')
                ->orderBy('ordem')->orderBy('nome')->get()
                ->map(fn (TipoEspaco $t) => [
                    'id' => $t->id,
                    'nome' => $t->nome,
                    'slug' => $t->slug,
                    'descricao' => $t->descricao,
                    'ordem' => $t->ordem,
                    'publicado' => $t->publicado,
                    'locais' => $t->locais_count,
                ])->all(),
            ...ListasEmLote::abas('tipos-espaco', $request),
            'podeGerenciar' => $request->user()?->can('locais.gerenciar') ?? false,
            'grupo' => TipoEspaco::GRUPO,
            'outroGrupo' => $request->user()?->can('fotos.ver') ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TipoEspaco::create($request->validate($this->regras()));

        return back()->with('sucesso', 'Tipo de espaço criado.');
    }

    public function update(Request $request, TipoEspaco $tipoEspaco): RedirectResponse
    {
        $tipoEspaco->update($request->validate($this->regras($tipoEspaco)));

        return back()->with('sucesso', 'Tipo de espaço atualizado.');
    }

    public function destroy(TipoEspaco $tipoEspaco): RedirectResponse
    {
        $tipoEspaco->delete();

        return back()->with('sucesso', 'Tipo arquivado. Os locais continuam cadastrados, sem tipo; ele está na aba Arquivados.');
    }

    private function regras(?TipoEspaco $tipo = null): array
    {
        return [
            'nome' => ['required', 'string', 'max:60', Rule::unique('tipos', 'nome')
                ->where('grupo', TipoEspaco::GRUPO)->ignore($tipo?->getKey())->whereNull('deleted_at')],
            'descricao' => ['nullable', 'string', 'max:160'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'publicado' => ['boolean'],
        ];
    }
}
