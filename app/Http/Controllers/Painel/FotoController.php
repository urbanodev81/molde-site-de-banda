<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Foto;
use App\Models\Integrante;
use App\Models\Show;
use App\Models\TipoGaleria;
use App\Support\Arquivos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FotoController extends Controller
{
    public function index(Request $request): Response
    {
        $arquivadas = $request->boolean('arquivadas');

        return Inertia::render('Painel/Fotos/Index', [
            'fotos' => Foto::query()->when($arquivadas, fn ($q) => $q->onlyTrashed())
                ->with(['show.local', 'integrantes', 'tipoGaleria'])
                ->orderBy('ordem')->orderByDesc('created_at')
                ->paginate(24)
                ->withQueryString()
                ->through(fn (Foto $f) => [
                    'uuid' => $f->uuid,
                    'legenda' => $f->legenda,
                    'descricao' => $f->descricao,
                    'alt' => $f->textoAlternativo(),
                    'url' => Arquivos::url($f->arquivo_path),
                    'credito' => $f->credito,
                    'show' => $f->show?->nome(),
                    'show_id' => $f->show_id,
                    'integrantes' => $f->integrantes->map->comoAparece()->all(),
                    'integrante_ids' => $f->integrantes->modelKeys(),
                    'tipo_galeria_id' => $f->tipo_galeria_id,
                    'tipo' => $f->tipoGaleria?->nome,
                    'destaque' => $f->destaque,
                    'registrada_em' => $f->registrada_em?->toDateString(),
                    'ordem' => $f->ordem,
                    'publicada' => $f->publicada,
                    'arquivada' => $f->trashed(),
                ]),
            'shows' => Show::query()->orderByDesc('comeca_em')->limit(50)->get()
                ->map(fn (Show $s) => ['valor' => $s->id, 'rotulo' => $s->nome().' · '.$s->comeca_em->format('d/m/Y')])->all(),
            'integrantes' => Integrante::query()->orderBy('ordem')->get()
                ->map(fn (Integrante $i) => ['valor' => $i->id, 'rotulo' => $i->comoAparece()])->all(),

            'tipos' => TipoGaleria::query()->orderBy('ordem')->orderBy('nome')->get()
                ->map(fn (TipoGaleria $t) => [
                    'valor' => $t->id,
                    'rotulo' => $t->nome.($t->publicado ? '' : ' (fora do ar)'),
                ])->all(),
            'arquivadas' => $arquivadas,
            'totais' => ['ativas' => Foto::query()->count(), 'arquivadas' => Foto::onlyTrashed()->count()],
            'podeGerenciar' => $request->user()?->can('fotos.gerenciar') ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([

            'arquivo' => ['required', 'image', 'max:8192'],
            ...$this->regrasComuns(),
        ]);

        $imagem = $request->file('arquivo');
        $medidas = @getimagesize($imagem->getRealPath());

        $foto = Foto::create([
            ...collect($dados)->except(['arquivo', 'integrantes'])->all(),
            'arquivo_path' => Arquivos::guardar($imagem, 'fotos'),

            'largura' => $medidas[0] ?? null,
            'altura' => $medidas[1] ?? null,
        ]);

        $foto->integrantes()->sync($dados['integrantes'] ?? []);

        return back()->with('sucesso', 'Foto adicionada.');
    }

    public function update(Request $request, Foto $foto): RedirectResponse
    {
        $dados = $request->validate($this->regrasComuns());

        $foto->update(collect($dados)->except('integrantes')->all());

        if ($request->has('integrantes')) {
            $foto->integrantes()->sync($dados['integrantes'] ?? []);
        }

        return back()->with('sucesso', 'Foto atualizada.');
    }

    public function destroy(Foto $foto): RedirectResponse
    {
        $foto->delete();

        return back()->with('sucesso', 'Foto arquivada. Ela está na aba Arquivadas.');
    }

    private function regrasComuns(): array
    {
        return [

            'legenda' => ['nullable', 'string', 'max:120'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'credito' => ['nullable', 'string', 'max:255'],
            'show_id' => ['nullable', Rule::exists('shows', 'id')->whereNull('deleted_at')],

            'integrantes' => ['nullable', 'array'],
            'integrantes.*' => ['integer', Rule::exists('integrantes', 'id')->whereNull('deleted_at')],
            'tipo_galeria_id' => ['nullable', Rule::exists('tipos', 'id')->where('grupo', TipoGaleria::GRUPO)->whereNull('deleted_at')],

            'registrada_em' => ['nullable', 'date'],
            'destaque' => ['boolean'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'publicada' => ['boolean'],
        ];
    }
}
