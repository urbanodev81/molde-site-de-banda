<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Support\Lote\ListasEmLote;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ImprimirListaController extends Controller
{
    public function __invoke(Request $request, string $lista): View
    {
        $definicao = ListasEmLote::de($lista);

        $dados = $request->validate([
            'ids' => ['nullable', 'array', 'max:100'],
            'ids.*' => [ListasEmLote::regraDaChave($definicao)],
        ]);

        $consulta = $definicao['modelo']::withTrashed()->whereIn(ListasEmLote::chave($definicao), $dados['ids'] ?? []);
        $definicao['ordem']($consulta);

        return view('painel.imprimir-lista', [
            'nome' => $definicao['nome'],
            'itens' => $consulta->get()->map(fn (Model $item) => [
                ...$definicao['linha']($item),
                'situacao' => ListasEmLote::situacao($definicao, $item),
            ]),
        ]);
    }
}
