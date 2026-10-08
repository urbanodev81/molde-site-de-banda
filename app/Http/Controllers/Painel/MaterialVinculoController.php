<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Enums\AlvoDeMaterial;
use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialVinculo;
use App\Support\VinculosDeMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MaterialVinculoController extends Controller
{
    public function store(Request $request, Material $material): RedirectResponse
    {
        $dados = $request->validate(['alvo' => ['required', 'string', 'regex:/^[a-z]+:\d+$/']]);
        [$tipo, $id] = explode(':', $dados['alvo']);

        $alvo = AlvoDeMaterial::tryFrom($tipo);
        abort_if($alvo === null, 422);
        abort_unless($request->user()->can($alvo->permissao()), 403);
        $this->exigirAcessoAoMaterial($request, $material);

        VinculosDeMaterial::ligar($material, $alvo->modelo()::query()->findOrFail((int) $id));

        return back()->with('sucesso', $alvo->reservado()
            ? 'Material ligado ao pedido. O arquivo agora é privado: só abre com login e não vai ao site.'
            : 'Material ligado.');
    }

    public function destroy(Request $request, Material $material, MaterialVinculo $vinculo): RedirectResponse
    {
        abort_unless($vinculo->material_id === $material->getKey(), 404);
        abort_unless($request->user()->can($vinculo->alvo_tipo->permissao()), 403);

        $vinculo->delete();

        return back()->with('sucesso', 'Ligação desfeita. O material continua cadastrado.');
    }

    public function baixar(Request $request, Material $material): Response
    {
        $this->exigirAcessoAoMaterial($request, $material);

        if (! $material->privado) {
            return redirect()->away((string) $material->link());
        }

        abort_unless(Storage::disk('local')->exists($material->arquivo_path), 404);

        return Storage::disk('local')->download($material->arquivo_path, $material->arquivo_nome_original ?: basename($material->arquivo_path));
    }

    private function exigirAcessoAoMaterial(Request $request, Material $material): void
    {
        abort_unless(
            VinculosDeMaterial::soOsQuePodeVer(Material::query()->whereKey($material->getKey()), $request->user())->exists(),
            404,
        );
    }
}
