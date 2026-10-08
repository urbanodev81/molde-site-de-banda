<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Enums\OrigemContratacao;
use App\Enums\StatusContratacao;
use App\Enums\TipoEvento;
use App\Enums\TipoInteracao;
use App\Http\Controllers\Controller;
use App\Models\Contratacao;
use App\Models\User;
use App\Support\Lote\ListasEmLote;
use App\Support\VinculosDeMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class ContratacaoController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $arquivados = $request->boolean('arquivados');

        $pedidos = Contratacao::query()
            ->with('responsavel')

            ->when($arquivados, fn ($q) => $q->onlyTrashed())
            ->when(! $arquivados && ($status === '' || $status === 'abertos'), fn ($q) => $q->abertas())
            ->when(! $arquivados && $status !== '' && $status !== 'abertos' && $status !== 'todos',
                fn ($q) => $q->where('status', $status))

            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Contratacao $c) => [
                'uuid' => $c->uuid,
                'nome' => $c->nome,
                'telefone' => $c->telefone,
                'email' => $c->email,
                'tipoEvento' => $c->tipo_evento->rotulo(),
                'dataPretendida' => $c->data_pretendida?->format('d/m/Y'),
                'cidade' => $c->cidade,
                'origem' => $c->origem->rotulo(),
                'status' => $c->status->value,
                'statusRotulo' => $c->status->rotulo(),
                'statusToken' => $c->status->token(),
                'statusIcone' => $c->status->icone(),
                'responsavel' => $c->responsavel?->name,
                'diasEsperando' => $c->diasEsperando(),
                'valor' => $this->podeVerValores() ? $c->valor_proposto : null,
            ]);

        return Inertia::render('Painel/Contratacoes/Index', [
            'pedidos' => $pedidos,
            'filtros' => $request->only('status'),
            ...ListasEmLote::abas('contratacoes', $request),
            'status' => StatusContratacao::opcoes(),
            'podeVerValores' => $this->podeVerValores(),
            'podeGerenciar' => $request->user()?->can('contratacoes.gerenciar') ?? false,
            'resumo' => [
                'novos' => Contratacao::query()->semResposta()->count(),
                'abertos' => Contratacao::query()->abertas()->count(),
                'fechadosNoAno' => Contratacao::query()
                    ->where('status', StatusContratacao::Fechado->value)
                    ->whereYear('created_at', now()->year)->count(),
            ],
        ]);
    }

    public function show(Contratacao $contratacao): Response
    {
        $contratacao->load(['interacoes.autor', 'responsavel', 'show']);

        return Inertia::render('Painel/Contratacoes/Detalhe', [

            'materiais' => VinculosDeMaterial::de($contratacao, request()->user()),
            'pedido' => [
                'uuid' => $contratacao->uuid,
                'nome' => $contratacao->nome,
                'email' => $contratacao->email,
                'telefone' => $contratacao->telefone,
                'tipoEvento' => $contratacao->tipo_evento->value,
                'tipoEventoRotulo' => $contratacao->tipo_evento->rotulo(),
                'dataPretendida' => $contratacao->data_pretendida?->format('Y-m-d'),
                'cidade' => $contratacao->cidade,
                'local' => $contratacao->local,
                'mensagem' => $contratacao->mensagem,
                'origem' => $contratacao->origem->value,
                'origemRotulo' => $contratacao->origem->rotulo(),
                'status' => $contratacao->status->value,
                'statusRotulo' => $contratacao->status->rotulo(),
                'statusToken' => $contratacao->status->token(),
                'motivoPerda' => $contratacao->motivo_perda,
                'responsavelId' => $contratacao->responsavel_id,
                'showId' => $contratacao->show_id,
                'criadoEm' => $contratacao->created_at->format('d/m/Y H:i'),
                'diasEsperando' => $contratacao->diasEsperando(),
                'valor' => $this->podeVerValores() ? $contratacao->valor_proposto : null,

                'consentimento' => $contratacao->temConsentimentoRegistrado()
                    ? $contratacao->consentimento_em->format('d/m/Y H:i')
                    : null,
            ],

            'interacoes' => $contratacao->interacoes->map(fn ($i) => [
                'id' => $i->id,
                'tipo' => $i->tipo->value,
                'tipoRotulo' => $i->tipo->rotulo(),
                'tipoIcone' => $i->tipo->icone(),
                'descricao' => $i->descricao,
                'autor' => $i->nomeDoAutor(),
                'quando' => $i->ocorrido_em->format('d/m/Y H:i'),
            ])->all(),

            'status' => StatusContratacao::opcoes(),
            'tiposEvento' => TipoEvento::opcoes(),
            'origens' => OrigemContratacao::opcoes(),
            'tiposInteracao' => TipoInteracao::opcoes(),
            'responsaveis' => User::query()->ativos()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $u) => ['valor' => $u->id, 'rotulo' => $u->name])->all(),
            'podeVerValores' => $this->podeVerValores(),
            'podeGerenciar' => request()->user()?->can('contratacoes.gerenciar') ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Painel/Contratacoes/Formulario', [
            'tiposEvento' => TipoEvento::opcoes(),
            'origens' => OrigemContratacao::opcoes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'tipo_evento' => ['required', new Enum(TipoEvento::class)],
            'data_pretendida' => ['nullable', 'date'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'local' => ['nullable', 'string', 'max:255'],
            'mensagem' => ['nullable', 'string', 'max:2000'],
            'origem' => ['required', new Enum(OrigemContratacao::class)],
        ]);

        $pedido = Contratacao::create($dados);

        $pedido->registrarInteracao(
            'Pedido cadastrado no painel, com origem '.$pedido->origem->rotulo().'.',
            TipoInteracao::Nota->value,
            $request->user(),
        );

        return redirect()->route('painel.contratacoes.show', $pedido)
            ->with('sucesso', 'Pedido registrado.');
    }

    public function update(Request $request, Contratacao $contratacao): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'tipo_evento' => ['required', new Enum(TipoEvento::class)],
            'data_pretendida' => ['nullable', 'date'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'local' => ['nullable', 'string', 'max:255'],
            'status' => ['required', new Enum(StatusContratacao::class)],
            'motivo_perda' => ['nullable', 'string', 'max:255'],
            'responsavel_id' => ['nullable', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'show_id' => ['nullable', Rule::exists('shows', 'id')->whereNull('deleted_at')],
            'valor_proposto' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
        ]);

        if (! $this->podeVerValores()) {
            unset($dados['valor_proposto']);
        }

        $statusAnterior = $contratacao->status;

        $contratacao->update($dados);

        if ($statusAnterior !== $contratacao->status) {
            $contratacao->registrarInteracao(
                "Etapa alterada de {$statusAnterior->rotulo()} para {$contratacao->status->rotulo()}.",
                TipoInteracao::MudancaStatus->value,
                $request->user(),
            );
        }

        return back()->with('sucesso', 'Pedido atualizado.');
    }

    public function registrarInteracao(Request $request, Contratacao $contratacao): RedirectResponse
    {
        $dados = $request->validate([
            'tipo' => ['required', new Enum(TipoInteracao::class)],
            'descricao' => ['required', 'string', 'max:2000'],
        ]);

        $contratacao->registrarInteracao($dados['descricao'], $dados['tipo'], $request->user());

        return back()->with('sucesso', 'Registrado no histórico.');
    }

    public function destroy(Contratacao $contratacao): RedirectResponse
    {
        $contratacao->delete();

        return redirect()->route('painel.contratacoes.index')->with('sucesso', 'Pedido arquivado. Ele está na aba Arquivados.');
    }

    private function podeVerValores(): bool
    {
        return request()->user()?->can('contratacoes.ver_valores') ?? false;
    }
}
