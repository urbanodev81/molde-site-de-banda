<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Foto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcaoEmLoteFotoController extends Controller
{
    private const ACOES = ['publicar', 'despublicar', 'arquivar', 'restaurar'];

    public function __invoke(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'acao' => ['required', Rule::in(self::ACOES)],

            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['uuid'],
        ]);

        $feitas = 0;

        foreach (Foto::withTrashed()->whereIn('uuid', $dados['ids'])->get() as $foto) {
            $feitas += (int) $this->aplicar($dados['acao'], $foto);
        }

        return back()->with('sucesso', $this->frase($dados['acao'], $feitas));
    }

    private function aplicar(string $acao, Foto $foto): bool
    {
        return match ($acao) {
            'publicar' => ! $foto->trashed() && ! $foto->publicada && $foto->update(['publicada' => true]),
            'despublicar' => ! $foto->trashed() && $foto->publicada && $foto->update(['publicada' => false]),
            'arquivar' => ! $foto->trashed() && (bool) $foto->delete(),
            'restaurar' => $foto->trashed() && (bool) $foto->restore(),
        };
    }

    private function frase(string $acao, int $feitas): string
    {
        if ($feitas === 0) {
            return 'Nenhuma foto precisou ser alterada.';
        }

        $verbo = [
            'publicar' => ['marcada como publicada', 'marcadas como publicadas'],
            'despublicar' => ['tirada do site', 'tiradas do site'],
            'arquivar' => ['arquivada', 'arquivadas'],
            'restaurar' => ['restaurada', 'restauradas'],
        ][$acao];

        return $feitas === 1 ? "1 foto {$verbo[0]}." : "{$feitas} fotos {$verbo[1]}.";
    }
}
