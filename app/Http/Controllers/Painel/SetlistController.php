<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Show;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SetlistController extends Controller
{
    public function __invoke(Request $request, Show $show): RedirectResponse
    {
        $dados = $request->validate([
            'musicas' => ['array'],
            'musicas.*.id' => ['required', 'integer', Rule::exists('musicas', 'id')->whereNull('deleted_at')],
            'musicas.*.bloco' => ['nullable', 'string', 'max:40'],
        ]);

        $vinculos = [];

        foreach ($dados['musicas'] ?? [] as $ordem => $musica) {
            $vinculos[$musica['id']] = [
                'ordem' => $ordem,
                'bloco' => $musica['bloco'] ?? null,
            ];
        }

        $show->setlist()->sync($vinculos);

        return back()->with('sucesso', 'Setlist salvo.');
    }
}
