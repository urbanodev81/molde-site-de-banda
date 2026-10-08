<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Http\Controllers\Controller;
use App\Http\Requests\Painel\ShowRequest;
use App\Models\Foto;
use App\Models\Local;
use App\Models\Musica;
use App\Models\Show;
use App\Models\Video;
use App\Support\Arquivos;
use App\Support\EnvioDeVideo;
use App\Support\Lote\ListasEmLote;
use App\Support\VinculosDeMaterial;
use App\Support\Youtube;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowController extends Controller
{
    public function index(Request $request): Response
    {
        $quando = $request->string('quando')->toString() ?: 'futuros';
        $busca = $request->string('busca')->toString();
        $arquivados = $request->boolean('arquivados');

        $shows = Show::query()
            ->with('local')

            ->when($arquivados, fn ($q) => $q->onlyTrashed()->orderByDesc('comeca_em'))
            ->when(! $arquivados && $quando === 'futuros', fn ($q) => $q->futuros())
            ->when(! $arquivados && $quando === 'passados', fn ($q) => $q->passados())
            ->when(! $arquivados && $quando === 'todos', fn ($q) => $q->orderByDesc('comeca_em'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($busca !== '', fn ($q) => $q->where(function ($sub) use ($busca) {
                $sub->where('titulo', 'ilike', "%{$busca}%")
                    ->orWhereHas('local', fn ($c) => $c->where('nome', 'ilike', "%{$busca}%"));
            }))
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Show $show) => $this->paraLista($show));

        return Inertia::render('Painel/Shows/Index', [
            'shows' => $shows,

            'filtros' => $request->only(['quando', 'status', 'busca']),
            'status' => StatusShow::opcoes(),
            ...ListasEmLote::abas('shows', $request),
            'podeGerenciar' => $request->user()?->can('shows.gerenciar') ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Painel/Shows/Formulario', $this->dadosDoFormulario());
    }

    public function store(ShowRequest $request): RedirectResponse
    {
        $show = Show::create($this->dados($request));

        return redirect()
            ->route('painel.shows.show', $show)
            ->with('sucesso', 'Show cadastrado.');
    }

    public function show(Show $show): Response
    {
        $show->load(['local', 'setlist', 'fotos' => fn ($q) => $q->orderBy('ordem')->latest(), 'videos' => fn ($q) => $q->latest(), 'depoimentos']);
        $usuario = request()->user();

        return Inertia::render('Painel/Shows/Detalhe', [
            'materiais' => VinculosDeMaterial::de($show, $usuario),
            'show' => [
                ...$this->paraLista($show),
                'observacoesPublicas' => $show->observacoes_publicas,
                'observacoesInternas' => $show->observacoes_internas,
                'entrada' => $show->entrada,
                'mapa' => $show->linkDoMapa(),
                'cartaz' => Arquivos::url($show->cartaz_path),
                'setlist' => $show->setlist->map(fn (Musica $m) => [
                    'id' => $m->id,
                    'linha' => $m->linha(),
                    'bloco' => $m->pivot->bloco,
                    'ordem' => $m->pivot->ordem,
                ])->all(),
            ],

            'cache' => $this->podeVerValores() ? $show->cache : null,

            'repertorio' => Musica::query()->orderBy('titulo')
                ->get(['id', 'titulo', 'artista'])
                ->map(fn (Musica $m) => ['id' => $m->id, 'linha' => $m->linha()])->all(),

            'midia' => [

                'showId' => $show->id,
                'fotos' => $show->fotos->map(fn (Foto $f) => [
                    'uuid' => $f->uuid,
                    'url' => Arquivos::url($f->arquivo_path),
                    'alt' => $f->textoAlternativo(),
                    'publicada' => $f->publicada,
                ])->all(),
                'videos' => $show->videos->map(fn (Video $v) => [
                    'uuid' => $v->uuid,
                    'titulo' => $v->titulo,
                    'tipo' => $v->tipo->rotulo(),
                    'duracao' => EnvioDeVideo::duracaoLegivel($v->duracao_segundos),
                    'capa' => Arquivos::url($v->capa_path) ?: Youtube::capa($v->youtube_id),
                    'publicado' => $v->publicado,
                ])->all(),
                'biblioteca' => Video::query()->whereNull('show_id')->latest()->limit(100)->get()
                    ->map(fn (Video $v) => ['valor' => $v->id, 'rotulo' => $v->titulo.' · '.$v->tipo->rotulo()])->all(),
                'limites' => ['mb' => Video::LIMITE_MB, 'segundos' => Video::LIMITE_SEGUNDOS],
                'podeFotos' => $usuario?->can('fotos.gerenciar') ?? false,
                'podeVideos' => $usuario?->can('videos.gerenciar') ?? false,
            ],

            'motivosParaNaoAparecer' => $show->motivosParaNaoAparecer(),
            'podeGerenciar' => request()->user()?->can('shows.gerenciar') ?? false,
        ]);
    }

    public function edit(Show $show): Response
    {
        return Inertia::render('Painel/Shows/Formulario', [
            ...$this->dadosDoFormulario(),
            'show' => [
                'uuid' => $show->uuid,
                'local_id' => $show->local_id,
                'titulo' => $show->titulo,
                'endereco_livre' => $show->endereco_livre,
                'mapa_url' => $show->mapa_url,

                'comeca_em' => $show->comeca_em->format('Y-m-d\TH:i'),
                'termina_em' => $show->termina_em?->format('Y-m-d\TH:i'),
                'status' => $show->status->value,
                'tipo' => $show->tipo->value,
                'entrada' => $show->entrada,
                'observacoes_publicas' => $show->observacoes_publicas,
                'observacoes_internas' => $show->observacoes_internas,
                'cache' => $this->podeVerValores() ? $show->cache : null,
                'destaque' => $show->destaque,
                'publicado' => $show->publicado,
                'cartaz' => Arquivos::url($show->cartaz_path),
            ],
        ]);
    }

    public function update(ShowRequest $request, Show $show): RedirectResponse
    {
        $show->update($this->dados($request, $show));

        return redirect()
            ->route('painel.shows.show', $show)
            ->with('sucesso', 'Show atualizado.');
    }

    public function destroy(Show $show): RedirectResponse
    {
        $show->delete();

        return redirect()
            ->route('painel.shows.index')
            ->with('sucesso', 'Show arquivado. Ele está na aba Arquivados.');
    }

    private function dadosDoFormulario(): array
    {
        return [
            'locais' => Local::query()->ativas()->orderBy('nome')
                ->get(['id', 'nome', 'cidade'])
                ->map(fn (Local $c) => [
                    'valor' => $c->id,
                    'rotulo' => $c->cidade ? "{$c->nome} · {$c->cidade}" : $c->nome,
                ])->all(),
            'status' => StatusShow::opcoes(),
            'tipos' => TipoShow::opcoes(),
            'podeVerValores' => $this->podeVerValores(),
        ];
    }

    private function dados(ShowRequest $request, ?Show $show = null): array
    {
        $dados = $request->validated();

        if (! $this->podeVerValores()) {
            unset($dados['cache']);
        }

        $dados['cartaz_path'] = Arquivos::guardar(
            $request->file('cartaz'),
            'cartazes',
            $show?->cartaz_path,
        );

        unset($dados['cartaz']);

        return $dados;
    }

    private function podeVerValores(): bool
    {
        return request()->user()?->can('contratacoes.ver_valores') ?? false;
    }

    private function paraLista(Show $show): array
    {
        return [
            'uuid' => $show->uuid,
            'nome' => $show->nome(),
            'local' => $show->local?->nome,
            'cidade' => $show->local?->cidade,
            'quando' => $show->comeca_em->toIso8601String(),
            'dia' => $show->comeca_em->format('d'),
            'mes' => mb_strtoupper($show->comeca_em->translatedFormat('M')),
            'ano' => $show->comeca_em->format('Y'),
            'hora' => $show->comeca_em->format('H\hi'),
            'status' => $show->status->value,
            'statusRotulo' => $show->status->rotulo(),
            'statusToken' => $show->status->token(),
            'statusIcone' => $show->status->icone(),
            'tipo' => $show->tipo->value,
            'tipoRotulo' => $show->tipo->rotulo(),
            'destaque' => $show->destaque,
            'publicado' => $show->publicado,
            'noSite' => $show->motivosParaNaoAparecer() === [],
            'endereco' => $show->endereco(),
        ];
    }
}
