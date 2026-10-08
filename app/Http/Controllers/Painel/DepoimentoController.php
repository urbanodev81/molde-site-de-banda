<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Depoimento;
use App\Models\Show;
use App\Support\Lote\ListasEmLote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DepoimentoController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Painel/Depoimentos/Index', [
            'depoimentos' => Depoimento::query()->when($request->boolean('arquivados'), fn ($q) => $q->onlyTrashed())->with('show.local')->orderBy('ordem')->get()
                ->map(fn (Depoimento $d) => [
                    'id' => $d->id,
                    'autor' => $d->autor,
                    'papel' => $d->papel,
                    'texto' => $d->texto,
                    'show_id' => $d->show_id,
                    'show' => $d->show?->nome(),
                    'ocorrido_em' => $d->ocorrido_em?->format('Y-m-d'),
                    'autorizado' => $d->autorizado,
                    'publicado' => $d->publicado,
                    'ordem' => $d->ordem,
                    'noSite' => $d->autorizado && $d->publicado,
                ])->all(),
            'shows' => Show::query()->orderByDesc('comeca_em')->limit(50)->get()
                ->map(fn (Show $s) => ['valor' => $s->id, 'rotulo' => $s->nome().' · '.$s->comeca_em->format('d/m/Y')])->all(),
            ...ListasEmLote::abas('depoimentos', $request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Depoimento::create($this->validar($request));

        return back()->with('sucesso', 'Depoimento adicionado.');
    }

    public function update(Request $request, Depoimento $depoimento): RedirectResponse
    {
        $depoimento->update($this->validar($request));

        return back()->with('sucesso', 'Depoimento atualizado.');
    }

    public function destroy(Depoimento $depoimento): RedirectResponse
    {
        $depoimento->delete();

        return back()->with('sucesso', 'Depoimento arquivado. Ele está na aba Arquivados.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'autor' => ['required', 'string', 'max:255'],
            'papel' => ['nullable', 'string', 'max:255'],
            'texto' => ['required', 'string', 'max:2000'],
            'show_id' => ['nullable', Rule::exists('shows', 'id')->whereNull('deleted_at')],
            'ocorrido_em' => ['nullable', 'date'],
            'autorizado' => ['boolean'],
            'publicado' => ['boolean'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}
