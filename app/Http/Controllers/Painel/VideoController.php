<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Enums\TipoVideo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Painel\VideoRequest;
use App\Models\Integrante;
use App\Models\Local;
use App\Models\Show;
use App\Models\TipoGaleria;
use App\Models\Video;
use App\Support\Arquivos;
use App\Support\EnvioDeVideo;
use App\Support\Lote\ListasEmLote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VideoController extends Controller
{
    public function index(Request $request): Response
    {
        $videos = Video::query()->when($request->boolean('arquivados'), fn ($q) => $q->onlyTrashed())
            ->with(['local', 'tipoGaleria'])->orderBy('ordem')->get();

        $emUso = $request->boolean('arquivados') ? Video::query()->get() : $videos;
        $publicados = $emUso->where('publicado', true)->count();

        return Inertia::render('Painel/Videos/Index', [
            'videos' => $videos->map(fn (Video $v) => [
                'uuid' => $v->uuid,
                'titulo' => $v->titulo,
                'ondeFoi' => $v->ondeFoi(),
                'tipoGaleria' => $v->tipoGaleria?->nome,
                'gravadoEm' => $v->gravado_em?->format('d/m/Y'),
                'tipo' => $v->tipo->value,
                'tipoRotulo' => $v->tipo->rotulo(),
                'demonstracao' => $v->demonstracao,
                'publicado' => $v->publicado,
                'reproduzivel' => $v->reproduzivel(),
                'ordem' => $v->ordem,
                'capa' => Arquivos::url($v->capa_path),
                'youtubeId' => $v->youtube_id,
                'duracao' => EnvioDeVideo::duracaoLegivel($v->duracao_segundos),
            ])->all(),

            'vagas' => [
                'limite' => Video::LIMITE_NA_HOME,
                'preenchidas' => $publicados,
                'demonstracoes' => $emUso->where('publicado', true)->where('demonstracao', true)->count(),
                'livres' => max(0, Video::LIMITE_NA_HOME - $publicados),
            ],

            ...ListasEmLote::abas('videos', $request),
            'podeGerenciar' => $request->user()?->can('videos.gerenciar') ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Painel/Videos/Formulario', $this->dadosDoFormulario());
    }

    public function store(VideoRequest $request): RedirectResponse
    {
        $video = Video::create($this->dados($request));
        $video->integrantes()->sync($request->validated('integrantes') ?? []);

        return redirect()->route('painel.videos.index')->with('sucesso', 'Vídeo cadastrado.');
    }

    public function edit(Video $video): Response
    {
        return Inertia::render('Painel/Videos/Formulario', [
            ...$this->dadosDoFormulario(),
            'video' => [
                'uuid' => $video->uuid,
                'titulo' => $video->titulo,
                'local_id' => $video->local_id,
                'local_nome' => $video->local_nome,

                'show_id' => $video->show_id,
                'integrantes' => $video->integrantes()->pluck('integrantes.id')->all(),
                'gravado_em' => $video->gravado_em?->format('Y-m-d'),
                'tipo_galeria_id' => $video->tipo_galeria_id,
                'tipo' => $video->tipo === TipoVideo::Arquivo ? 'arquivo' : 'link',
                'link' => $video->linkDeOrigem(),
                'duracao' => EnvioDeVideo::duracaoLegivel($video->duracao_segundos),
                'demonstracao' => $video->demonstracao,
                'ordem' => $video->ordem,
                'publicado' => $video->publicado,
                'capa' => Arquivos::url($video->capa_path),
                'temWebm' => filled($video->arquivo_webm_path),
                'temMp4' => filled($video->arquivo_mp4_path),
            ],
        ]);
    }

    public function update(VideoRequest $request, Video $video): RedirectResponse
    {
        $video->update($this->dados($request, $video));

        $video->integrantes()->sync($request->validated('integrantes') ?? []);

        return redirect()->route('painel.videos.index')->with('sucesso', 'Vídeo atualizado.');
    }

    public function destroy(Video $video): RedirectResponse
    {
        $video->delete();

        return redirect()->route('painel.videos.index')->with('sucesso', 'Vídeo arquivado. Ele está na aba Arquivados.');
    }

    private function dadosDoFormulario(): array
    {
        return [
            'locais' => Local::query()->ativas()->orderBy('nome')->get(['id', 'nome'])
                ->map(fn (Local $c) => ['valor' => $c->id, 'rotulo' => $c->nome])->all(),

            'shows' => Show::query()->with('local')->orderByDesc('comeca_em')->limit(40)->get()
                ->map(fn (Show $s) => [
                    'valor' => $s->id,
                    'rotulo' => $s->comeca_em->format('d/m/Y').' · '.$s->nome(),
                ])->all(),
            'limites' => ['mb' => Video::LIMITE_MB, 'segundos' => Video::LIMITE_SEGUNDOS],

            'tiposDeGaleria' => TipoGaleria::query()->orderBy('ordem')->orderBy('nome')->get()
                ->map(fn (TipoGaleria $t) => [
                    'valor' => $t->id,
                    'rotulo' => $t->nome.($t->publicado ? '' : ' (fora do ar)'),
                ])->all(),
            'limite' => Video::LIMITE_NA_HOME,
            'integrantes' => Integrante::query()->orderBy('ordem')->get()
                ->map(fn (Integrante $i) => ['valor' => $i->id, 'rotulo' => $i->comoAparece()])->all(),
        ];
    }

    private function dados(VideoRequest $request, ?Video $video = null): array
    {
        $dados = collect($request->validated())->except(['link', 'mp4', 'capa', 'integrantes'])->all();

        $dados['capa_path'] = Arquivos::guardar($request->file('capa'), 'videos', $video?->capa_path);

        if ($dados['tipo'] !== TipoVideo::Arquivo->value) {
            Arquivos::apagar($video?->arquivo_mp4_path);
            Arquivos::apagar($video?->arquivo_webm_path);

            return [
                ...$dados,
                ...EnvioDeVideo::doLink($request->validated('link')),
                'arquivo_mp4_path' => null,
                'arquivo_webm_path' => null,
                'duracao_segundos' => null,
                'tamanho_bytes' => null,
            ];
        }

        if ($request->hasFile('mp4')) {
            return [...$dados, ...EnvioDeVideo::guardarArquivo($request->file('mp4'), $video)];
        }

        return [...$dados, 'youtube_id' => null, 'link_url' => null];
    }
}
