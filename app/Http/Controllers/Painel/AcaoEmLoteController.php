<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Support\Lote\ListasEmLote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcaoEmLoteController extends Controller
{
    public function __invoke(Request $request, string $lista): RedirectResponse
    {
        $definicao = ListasEmLote::de($lista);

        $acoes = $definicao['coluna'] === null
            ? ['arquivar', 'restaurar']
            : ['ligar', 'desligar', 'arquivar', 'restaurar'];

        $dados = $request->validate([
            'acao' => ['required', Rule::in($acoes)],

            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => [ListasEmLote::regraDaChave($definicao)],
        ]);

        $intocavel = $definicao['intocavel'] ?? null;
        $feitos = 0;
        $pulados = 0;

        foreach ($definicao['modelo']::withTrashed()->whereIn(ListasEmLote::chave($definicao), $dados['ids'])->get() as $item) {
            if ($intocavel !== null && $intocavel($item, $request, $dados['acao'])) {
                $pulados++;

                continue;
            }

            $feitos += (int) $this->aplicar($dados['acao'], $item, $definicao['coluna']);
        }

        $estado = [
            'ligar' => mb_strtolower((string) ($definicao['ligado'] ?? '')),
            'desligar' => mb_strtolower((string) ($definicao['desligado'] ?? '')),
            'arquivar' => 'arquivad',
            'restaurar' => 'restaurad',
        ][$dados['acao']];

        $frase = in_array($dados['acao'], ['arquivar', 'restaurar'], true)
            ? ListasEmLote::frase($definicao, $feitos, $estado)
            : $this->fraseDeEstado($definicao, $feitos, $estado);

        return back()->with('sucesso', $frase.($pulados > 0 ? ' '.($definicao['aviso'] ?? 'Parte dos itens ficou como estava.') : ''));
    }

    private function aplicar(string $acao, Model $item, ?string $coluna): bool
    {
        return match ($acao) {
            'ligar' => ! $item->trashed() && ! $item->{$coluna} && $item->update([$coluna => true]),
            'desligar' => ! $item->trashed() && $item->{$coluna} && $item->update([$coluna => false]),
            'arquivar' => ! $item->trashed() && (bool) $item->delete(),
            'restaurar' => $item->trashed() && (bool) $item->restore(),
        };
    }

    private function fraseDeEstado(array $definicao, int $feitos, string $estado): string
    {
        [$um, $varios, $genero] = $definicao['nome'];

        if ($feitos === 0) {
            return $genero === 'a' ? "Nenhuma {$um} precisou ser alterada." : "Nenhum {$um} precisou ser alterado.";
        }

        return $feitos === 1
            ? "1 {$um} agora está como \"{$estado}\"."
            : "{$feitos} {$varios} agora estão como \"{$estado}\".";
    }
}
