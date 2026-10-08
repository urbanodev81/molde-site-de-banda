<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Enums\TipoPublicacao;
use App\Http\Controllers\Controller;
use App\Models\Publicacao;
use App\Support\Arquivos;
use App\Support\Lote\ListasEmLote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class PublicacaoController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Painel/Publicacoes/Index', [
            'publicacoes' => Publicacao::query()->when($request->boolean('arquivados'), fn ($q) => $q->onlyTrashed())
                ->orderByRaw('saiu_em desc nulls last')->orderByDesc('id')->get()
                ->map(fn (Publicacao $p) => [
                    'id' => $p->id,
                    'tipo' => $p->tipo->value,
                    'tipoRotulo' => $p->tipo->rotulo(),
                    'titulo' => $p->titulo,
                    'veiculo' => $p->veiculo,
                    'saiu_em' => $p->saiu_em?->format('Y-m-d'),
                    'origem' => $p->origem(),
                    'link' => $p->link,
                    'resumo' => $p->resumo,
                    'imagem' => Arquivos::url($p->imagem_path),
                    'publicada' => $p->publicada,
                    'destaque' => $p->destaque,
                ])->all(),
            'tipos' => TipoPublicacao::opcoes(),
            ...ListasEmLote::abas('publicacoes', $request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);

        Publicacao::create([
            ...collect($dados)->except('imagem')->all(),
            'imagem_path' => Arquivos::guardar($request->file('imagem'), 'publicacoes'),
        ]);

        return back()->with('sucesso', 'Publicação adicionada.');
    }

    public function update(Request $request, Publicacao $publicacao): RedirectResponse
    {
        $dados = $this->validar($request);

        $publicacao->update([
            ...collect($dados)->except('imagem')->all(),
            'imagem_path' => Arquivos::guardar($request->file('imagem'), 'publicacoes', $publicacao->imagem_path),
        ]);

        return back()->with('sucesso', 'Publicação atualizada.');
    }

    public function destroy(Publicacao $publicacao): RedirectResponse
    {
        $publicacao->delete();

        return back()->with('sucesso', 'Publicação arquivada. Ela está na aba Arquivados.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'tipo' => ['required', new Enum(TipoPublicacao::class)],
            'titulo' => ['required', 'string', 'max:255'],
            'veiculo' => ['nullable', 'string', 'max:255'],
            'saiu_em' => ['nullable', 'date', 'before_or_equal:today'],
            'link' => ['required', 'string', 'max:2048', 'url:http,https'],
            'resumo' => ['nullable', 'string', 'max:600'],
            'imagem' => ['nullable', 'image', 'max:4096'],
            'publicada' => ['boolean'],
            'destaque' => ['boolean'],
        ], [
            'link.url' => 'O link precisa começar com http:// ou https://.',
            'saiu_em.before_or_equal' => 'A data não pode estar no futuro.',
        ]);
    }
}
