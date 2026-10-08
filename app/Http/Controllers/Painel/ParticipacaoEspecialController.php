<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Painel\ParticipacaoEspecialRequest;
use App\Models\ParticipacaoEspecial;
use App\Models\Show;
use App\Support\Arquivos;
use App\Support\Lote\ListasEmLote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ParticipacaoEspecialController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Painel/Participacoes/Index', [
            'participacoes' => ParticipacaoEspecial::query()->when($request->boolean('arquivados'), fn ($q) => $q->onlyTrashed())->withCount('shows')
                ->orderBy('ordem')->orderBy('nome')->get()
                ->map(fn (ParticipacaoEspecial $p) => [
                    'uuid' => $p->uuid,
                    'nome' => $p->nome,
                    'funcao' => $p->funcao,
                    'foto' => Arquivos::url($p->foto_path),
                    'shows' => $p->shows_count,
                    'publicada' => $p->publicada,
                    'autorizada' => $p->autorizacao_imagem_em !== null,
                    'autorizadaEm' => $p->autorizacao_imagem_em?->format('d/m/Y'),
                    'motivos' => $p->motivosParaNaoAparecer(),
                ])->all(),
            ...ListasEmLote::abas('participacoes', $request),
            'podeGerenciar' => $request->user()?->can('integrantes.gerenciar') ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Painel/Participacoes/Formulario', $this->dadosDoFormulario());
    }

    public function store(ParticipacaoEspecialRequest $request): RedirectResponse
    {
        $participacao = ParticipacaoEspecial::create($this->dados($request));
        $participacao->shows()->sync($request->validated('shows') ?? []);

        return redirect()->route('painel.participacoes.index')->with('sucesso', 'Participação cadastrada.');
    }

    public function edit(ParticipacaoEspecial $participacao): Response
    {
        return Inertia::render('Painel/Participacoes/Formulario', [
            ...$this->dadosDoFormulario(),
            'participacao' => [
                'uuid' => $participacao->uuid,
                ...$participacao->only([
                    'nome', 'funcao', 'descricao', 'instagram', 'facebook', 'tiktok',
                    'youtube', 'site_url', 'ordem', 'publicada',
                ]),
                'shows' => $participacao->shows()->pluck('shows.id')->all(),
                'autorizacao_imagem_em' => $participacao->autorizacao_imagem_em?->format('Y-m-d'),
                'temDocumento' => filled($participacao->autorizacao_documento_path),
                'foto' => Arquivos::url($participacao->foto_path),
                'motivos' => $participacao->motivosParaNaoAparecer(),
            ],
        ]);
    }

    public function update(ParticipacaoEspecialRequest $request, ParticipacaoEspecial $participacao): RedirectResponse
    {
        $participacao->update($this->dados($request, $participacao));

        $participacao->shows()->sync($request->validated('shows') ?? []);

        return redirect()->route('painel.participacoes.index')->with('sucesso', 'Participação atualizada.');
    }

    public function destroy(ParticipacaoEspecial $participacao): RedirectResponse
    {
        $participacao->delete();

        return redirect()->route('painel.participacoes.index')->with('sucesso', 'Participação arquivada. Ela está na aba Arquivadas.');
    }

    private function dadosDoFormulario(): array
    {
        return [
            'shows' => Show::query()->with('local')->orderByDesc('comeca_em')->limit(60)->get()
                ->map(fn (Show $s) => ['valor' => $s->id, 'rotulo' => $s->comeca_em->format('d/m/Y').' · '.$s->nome()])
                ->all(),
        ];
    }

    private function dados(ParticipacaoEspecialRequest $request, ?ParticipacaoEspecial $participacao = null): array
    {
        $dados = $request->validated();

        $dados['foto_path'] = Arquivos::guardar($request->file('foto'), 'participacoes', $participacao?->foto_path);

        $dados['autorizacao_documento_path'] = Arquivos::guardarPrivado(
            $request->file('autorizacao_documento'),
            'autorizacoes',
            $participacao?->autorizacao_documento_path,
        );

        unset($dados['foto'], $dados['autorizacao_documento'], $dados['shows']);

        return $dados;
    }
}
