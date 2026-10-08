<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Painel\LocalRequest;
use App\Models\Local;
use App\Models\TipoEspaco;
use App\Support\Arquivos;
use App\Support\Lote\ListasEmLote;
use App\Support\VinculosDeMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocalController extends Controller
{
    public function index(Request $request): Response
    {
        $busca = $request->string('busca')->toString();

        return Inertia::render('Painel/Locais/Index', [
            'locais' => Local::query()
                ->withCount(['shows' => fn ($q) => $q->whereNull('deleted_at')])
                ->when($request->boolean('arquivados'), fn ($q) => $q->onlyTrashed())
                ->when($busca !== '', fn ($q) => $q->where('nome', 'ilike', "%{$busca}%"))
                ->orderBy('nome')
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Local $local) => [
                    'uuid' => $local->uuid,
                    'nome' => $local->nome,
                    'tipo' => $local->tipo?->nome,
                    'cidade' => $local->cidade,
                    'uf' => $local->uf,
                    'endereco' => $local->enderecoCompleto(),
                    'shows' => $local->shows_count,
                    'ativa' => $local->ativa,
                    'logo' => Arquivos::url($local->logo_path),
                    'mapa' => $local->linkDoMapa(),
                    'site' => $local->site_url,
                    'instagram' => $local->instagram,
                ]),
            'filtros' => $request->only('busca'),
            ...ListasEmLote::abas('locais', $request),
            'podeGerenciar' => $request->user()?->can('locais.gerenciar') ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Painel/Locais/Formulario', ['tipos' => $this->tipos()]);
    }

    public function store(LocalRequest $request): RedirectResponse
    {
        Local::create($this->dados($request));

        return redirect()->route('painel.locais.index')->with('sucesso', 'Local cadastrado.');
    }

    public function edit(Local $local): Response
    {
        return Inertia::render('Painel/Locais/Formulario', [
            'local' => [
                'uuid' => $local->uuid,
                'nome' => $local->nome,
                'tipo_id' => $local->tipo_id,
                'cidade' => $local->cidade,
                'uf' => $local->uf,
                'endereco' => $local->endereco,
                'bairro' => $local->bairro,
                'cep' => $local->cep,
                'mapa_url' => $local->mapa_url,
                'site_url' => $local->site_url,
                'instagram' => $local->instagram,
                'contato_nome' => $local->contato_nome,
                'contato_telefone' => $local->contato_telefone,
                'observacoes' => $local->observacoes,
                'ativa' => $local->ativa,
                'logo' => Arquivos::url($local->logo_path),
            ],
            'tipos' => $this->tipos(),
            'materiais' => VinculosDeMaterial::de($local, request()->user()),
        ]);
    }

    public function update(LocalRequest $request, Local $local): RedirectResponse
    {
        $dados = $this->dados($request, $local);

        if ($local->nome !== $dados['nome']) {
            $dados['slug'] = Local::slugUnico($dados['nome'], $local->id);
        }

        $local->update($dados);

        return redirect()->route('painel.locais.index')->with('sucesso', 'Local atualizado.');
    }

    public function destroy(Local $local): RedirectResponse
    {
        $local->delete();

        return redirect()->route('painel.locais.index')->with('sucesso', 'Local arquivado. Ele está na aba Arquivados.');
    }

    private function tipos(): array
    {
        return TipoEspaco::query()->orderBy('ordem')->orderBy('nome')->get()
            ->map(fn (TipoEspaco $t) => ['valor' => $t->id, 'rotulo' => $t->nome])->all();
    }

    private function dados(LocalRequest $request, ?Local $local = null): array
    {
        $dados = $request->validated();
        $dados['logo_path'] = Arquivos::guardar($request->file('logo'), 'locais', $local?->logo_path);
        unset($dados['logo']);

        return $dados;
    }
}
