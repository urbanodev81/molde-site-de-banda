<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Enums\TipoMaterial;
use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Support\Arquivos;
use App\Support\Lote\ListasEmLote;
use App\Support\VinculosDeMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    public function index(Request $request): Response
    {
        $usuario = $request->user();

        $materiais = VinculosDeMaterial::soOsQuePodeVer(Material::query(), $usuario)
            ->when($request->boolean('arquivados'), fn ($q) => $q->onlyTrashed())->orderBy('ordem')->orderBy('titulo')->get();
        $vinculos = VinculosDeMaterial::daLista($materiais, $usuario);

        return Inertia::render('Painel/Materiais/Index', [
            'materiais' => $materiais
                ->map(fn (Material $m) => [
                    'uuid' => $m->uuid,
                    'titulo' => $m->titulo,
                    'descricao' => $m->descricao,
                    'tipo' => $m->tipo->value,
                    'tipoRotulo' => $m->tipo->rotulo(),
                    'tipoIcone' => $m->tipo->icone(),
                    'url' => $m->link(),
                    'privado' => $m->privado,
                    'vinculos' => $vinculos[$m->id] ?? [],
                    'nomeOriginal' => $m->arquivo_nome_original,
                    'tamanho' => $m->tamanhoLegivel(),
                    'publico' => $m->publico,
                    'ordem' => $m->ordem,
                ])->all(),
            'tipos' => TipoMaterial::opcoes(),

            'alvos' => VinculosDeMaterial::opcoes($usuario),
            ...ListasEmLote::abas('materiais', $request),
            'podeGerenciar' => $request->user()?->can('materiais.gerenciar') ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'arquivo' => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,webp,pdf,zip,svg,ai,eps'],
            ...$this->regrasComuns(),
        ]);

        $arquivo = $request->file('arquivo');

        Material::create([
            ...collect($dados)->except('arquivo')->all(),
            'arquivo_path' => Arquivos::guardar($arquivo, 'materiais'),
            'arquivo_nome_original' => $arquivo->getClientOriginalName(),
            'tamanho_bytes' => $arquivo->getSize(),
        ]);

        return back()->with('sucesso', 'Material adicionado.');
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $dados = $request->validate($this->regrasComuns());

        if ($material->privado) {
            $dados['publico'] = false;
        }

        $material->update($dados);

        return back()->with('sucesso', 'Material atualizado.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        $material->delete();

        return back()->with('sucesso', 'Material arquivado. Ele está na aba Arquivados.');
    }

    private function regrasComuns(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'tipo' => ['required', new Enum(TipoMaterial::class)],
            'publico' => ['boolean'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
