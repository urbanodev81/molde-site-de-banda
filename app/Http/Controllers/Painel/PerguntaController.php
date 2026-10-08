<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Pergunta;
use App\Support\Lote\ListasEmLote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerguntaController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Painel/Perguntas/Index', [
            'perguntas' => Pergunta::query()->when($request->boolean('arquivados'), fn ($q) => $q->onlyTrashed())->orderBy('ordem')->get()
                ->map(fn (Pergunta $p) => [
                    'id' => $p->id,
                    'pergunta' => $p->pergunta,
                    'resposta' => $p->resposta,
                    'ordem' => $p->ordem,
                    'publicada' => $p->publicada,
                ])->all(),
            ...ListasEmLote::abas('perguntas', $request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Pergunta::create($this->validar($request));

        return back()->with('sucesso', 'Dúvida adicionada.');
    }

    public function update(Request $request, Pergunta $pergunta): RedirectResponse
    {
        $pergunta->update($this->validar($request));

        return back()->with('sucesso', 'Dúvida atualizada.');
    }

    public function destroy(Pergunta $pergunta): RedirectResponse
    {
        $pergunta->delete();

        return back()->with('sucesso', 'Dúvida arquivada. Ela está na aba Arquivadas.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'pergunta' => ['required', 'string', 'max:255'],
            'resposta' => ['required', 'string', 'max:2000'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'publicada' => ['boolean'],
        ]);
    }
}
