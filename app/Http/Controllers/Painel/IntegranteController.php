<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Painel\IntegranteRequest;
use App\Models\Integrante;
use App\Support\Arquivos;
use App\Support\Lote\ListasEmLote;
use App\Support\VinculosDeMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntegranteController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Painel/Integrantes/Index', [
            'integrantes' => Integrante::query()->when($request->boolean('arquivados'), fn ($q) => $q->onlyTrashed())->orderBy('ordem')->get()
                ->map(fn (Integrante $i) => [
                    'uuid' => $i->uuid,
                    'nome' => $i->nome,
                    'comoAparece' => $i->comoAparece(),
                    'instrumento' => $i->instrumento,
                    'ordem' => $i->ordem,
                    'ativa' => $i->ativa,
                    'autorizada' => $i->autorizada(),
                    'autorizadaEm' => $i->autorizacao_imagem_em?->format('d/m/Y'),
                    'foto' => Arquivos::url($i->foto_path),
                    'recorte' => Arquivos::url($i->recorte_path),
                    'motivos' => $i->motivosParaNaoAparecer(),
                ])->all(),
            ...ListasEmLote::abas('integrantes', $request),
            'podeGerenciar' => $request->user()?->can('integrantes.gerenciar') ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Painel/Integrantes/Formulario');
    }

    public function store(IntegranteRequest $request): RedirectResponse
    {
        Integrante::create($this->dados($request));

        return redirect()->route('painel.integrantes.index')->with('sucesso', 'Integrante cadastrada.');
    }

    public function edit(Integrante $integrante): Response
    {
        return Inertia::render('Painel/Integrantes/Formulario', [
            'materiais' => VinculosDeMaterial::de($integrante, request()->user()),
            'integrante' => [
                'uuid' => $integrante->uuid,
                'nome' => $integrante->nome,
                'nome_artistico' => $integrante->nome_artistico,
                'instrumento' => $integrante->instrumento,
                'bio' => $integrante->bio,
                'instagram' => $integrante->instagram,
                'facebook' => $integrante->facebook,
                'tiktok' => $integrante->tiktok,
                'ordem' => $integrante->ordem,
                'ativa' => $integrante->ativa,
                'palco_esquerda' => $integrante->palco_esquerda,
                'palco_largura' => $integrante->palco_largura,
                'palco_base' => $integrante->palco_base,
                'autorizacao_imagem_em' => $integrante->autorizacao_imagem_em?->format('Y-m-d'),
                'temDocumento' => filled($integrante->autorizacao_documento_path),
                'foto' => Arquivos::url($integrante->foto_path),
                'recorte' => Arquivos::url($integrante->recorte_path),
                'motivos' => $integrante->motivosParaNaoAparecer(),
            ],
        ]);
    }

    public function update(IntegranteRequest $request, Integrante $integrante): RedirectResponse
    {
        $integrante->update($this->dados($request, $integrante));

        return redirect()->route('painel.integrantes.index')->with('sucesso', 'Integrante atualizada.');
    }

    public function destroy(Integrante $integrante): RedirectResponse
    {
        $integrante->delete();

        return redirect()->route('painel.integrantes.index')->with('sucesso', 'Integrante arquivada. Ela está na aba Arquivadas.');
    }

    private function dados(IntegranteRequest $request, ?Integrante $integrante = null): array
    {
        $dados = $request->validated();

        $dados['foto_path'] = Arquivos::guardar($request->file('foto'), 'integrantes', $integrante?->foto_path);
        $dados['recorte_path'] = Arquivos::guardar($request->file('recorte'), 'integrantes', $integrante?->recorte_path);

        $dados['autorizacao_documento_path'] = Arquivos::guardarPrivado(
            $request->file('autorizacao_documento'),
            'autorizacoes',
            $integrante?->autorizacao_documento_path,
        );

        unset($dados['foto'], $dados['recorte'], $dados['autorizacao_documento']);

        return $dados;
    }
}
