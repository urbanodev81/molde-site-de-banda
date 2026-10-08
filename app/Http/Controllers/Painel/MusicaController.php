<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Musica;
use App\Models\Show;
use App\Models\Video;
use App\Support\EnvioDeVideo;
use App\Support\Lote\ListasEmLote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MusicaController extends Controller
{
    public function index(Request $request): Response
    {
        $busca = $request->string('busca')->toString();

        return Inertia::render('Painel/Musicas/Index', [
            'musicas' => Musica::query()->with('video')
                ->when($request->boolean('arquivados'), fn ($q) => $q->onlyTrashed())
                ->when($busca !== '', fn ($q) => $q->where(function ($sub) use ($busca) {
                    $sub->where('titulo', 'ilike', "%{$busca}%")
                        ->orWhere('artista', 'ilike', "%{$busca}%");
                }))
                ->orderBy('ordem')->orderBy('titulo')
                ->get()
                ->map(fn (Musica $m) => [
                    'id' => $m->id,
                    'titulo' => $m->titulo,
                    'artista' => $m->artista,
                    'estilo' => $m->estilo,
                    'tom' => $m->tom,
                    'ano' => $m->ano,
                    'ordem' => $m->ordem,
                    'publicada' => $m->publicada,
                    'destaque' => $m->destaque,
                    'observacoes' => $m->observacoes,
                    'video_id' => $m->video_id,
                    'youtube_id' => $m->youtube_id,
                    'video' => $m->video ? $m->video->titulo.' · '.$m->video->tipo->rotulo() : null,
                ])->all(),

            'biblioteca' => Video::query()->latest()->limit(100)->get()
                ->map(fn (Video $v) => [
                    'valor' => $v->id,
                    'rotulo' => $v->titulo.' · '.$v->tipo->rotulo()
                        .($v->duracao_segundos ? ' · '.EnvioDeVideo::duracaoLegivel($v->duracao_segundos) : '')
                        .($v->publicado ? '' : ' (fora do ar)'),
                ])->all(),
            'shows' => Show::query()->with('local')->orderByDesc('comeca_em')->limit(40)->get()
                ->map(fn (Show $s) => ['valor' => $s->id, 'rotulo' => $s->comeca_em->format('d/m/Y').' · '.$s->nome()])->all(),
            'limites' => ['mb' => Video::LIMITE_MB, 'segundos' => Video::LIMITE_SEGUNDOS],
            'filtros' => $request->only('busca'),
            ...ListasEmLote::abas('musicas', $request),
            'publicadas' => Musica::query()->where('publicada', true)->count(),
            'podeGerenciar' => $request->user()?->can('musicas.gerenciar') ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);

        Musica::create([...$dados, ...$this->versao($request, $dados)]);

        return back()->with('sucesso', 'Música adicionada.');
    }

    public function update(Request $request, Musica $musica): RedirectResponse
    {
        $dados = $this->validar($request);

        $musica->update([...$dados, ...$this->versao($request, $dados)]);

        return back()->with('sucesso', 'Música atualizada.');
    }

    public function destroy(Musica $musica): RedirectResponse
    {
        $musica->delete();

        return back()->with('sucesso', 'Música arquivada. Ela está na aba Arquivadas.');
    }

    private function validar(Request $request): array
    {
        $validador = Validator::make($request->all(), [
            'titulo' => ['required', 'string', 'max:255'],
            'artista' => ['nullable', 'string', 'max:255'],
            'estilo' => ['nullable', 'string', 'max:255'],
            'tom' => ['nullable', 'string', 'max:10'],
            'ano' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'duracao_segundos' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'publicada' => ['boolean'],
            'destaque' => ['boolean'],
            'observacoes' => ['nullable', 'string', 'max:2000'],

            'video_modo' => ['nullable', Rule::in(['manter', 'nenhum', 'biblioteca', 'link', 'arquivo'])],
            'video_id' => ['nullable', 'required_if:video_modo,biblioteca', Rule::exists('videos', 'id')->whereNull('deleted_at')],
            'video_link' => ['nullable', 'required_if:video_modo,link', 'string', 'max:500'],
            'video_arquivo' => [...EnvioDeVideo::regrasDoArquivo(), 'required_if:video_modo,arquivo'],
            'video_capa' => ['nullable', 'image', 'max:4096'],
            'video_show_id' => ['nullable', Rule::exists('shows', 'id')->whereNull('deleted_at')],
        ], [
            ...EnvioDeVideo::mensagens('video_arquivo'),
            'video_id.required_if' => 'Escolha o vídeo da biblioteca.',
            'video_link.required_if' => 'Cole o link do vídeo.',
            'video_arquivo.required_if' => 'Escolha o arquivo do vídeo.',
        ]);

        $validador->after(function ($v) use ($request): void {
            $modo = $request->input('video_modo');

            if ($modo === 'link' && filled($request->input('video_link')) && EnvioDeVideo::doLink($request->input('video_link')) === null) {
                $v->errors()->add('video_link', EnvioDeVideo::mensagemDeLinkDesconhecido());
            }

            if ($modo === 'arquivo') {
                EnvioDeVideo::conferirDuracao($v, $request->file('video_arquivo'), 'video_arquivo');
            }
        });

        return collect($validador->validate())
            ->except(['video_modo', 'video_id', 'video_link', 'video_arquivo', 'video_capa', 'video_show_id'])
            ->all();
    }

    private function versao(Request $request, array $dados): array
    {
        $modo = $request->input('video_modo', 'manter');

        if ($modo === 'nenhum') {
            return ['video_id' => null, 'youtube_id' => null];
        }

        if ($modo === 'biblioteca') {
            return ['video_id' => (int) $request->input('video_id'), 'youtube_id' => null];
        }

        if (! in_array($modo, ['link', 'arquivo'], true)) {
            return [];
        }

        $show = $request->filled('video_show_id') ? Show::with('local')->find($request->input('video_show_id')) : null;
        $titulo = filled($dados['artista'] ?? null) ? "{$dados['titulo']} — {$dados['artista']}" : $dados['titulo'];

        $video = EnvioDeVideo::criarNaBiblioteca(
            [
                'titulo' => $titulo,
                'show_id' => $show?->id,
                'local_id' => $show?->local_id,
                'gravado_em' => $show?->comeca_em?->toDateString(),
                'publicado' => true,
            ],
            $modo === 'arquivo' ? $request->file('video_arquivo') : null,
            $modo === 'link' ? $request->input('video_link') : null,
            $request->file('video_capa'),
        );

        return ['video_id' => $video->id, 'youtube_id' => null];
    }
}
