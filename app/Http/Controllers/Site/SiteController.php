<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Enums\TipoEvento;
use App\Http\Controllers\Controller;
use App\Models\Depoimento;
use App\Models\Foto;
use App\Models\Integrante;
use App\Models\Material;
use App\Models\Musica;
use App\Models\ParticipacaoEspecial;
use App\Models\Pergunta;
use App\Models\PoliticaRetencao;
use App\Models\Publicacao;
use App\Models\Show;
use App\Models\TipoGaleria;
use App\Models\Video;
use App\Support\ConfiguracaoDoSite;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class SiteController extends Controller
{
    public function home(): View
    {
        $proximo = Show::query()->with('local')->publicaveis()->futuros()->first();

        $comMaterial = [
            'fotos as fotos_publicadas_count' => fn ($q) => $q->where('publicada', true),
            'videos as videos_publicados_count' => fn ($q) => $q->where('publicado', true),
        ];

        return view('site.home', [
            'config' => ConfiguracaoDoSite::todas(),
            'proximo' => $proximo,

            'paginaDaHome' => true,

            'versoesDePrevia' => $this->versoesDePrevia(null),

            'agenda' => Show::query()->with('local')->withCount($comMaterial)->publicaveis()->futuros()
                ->when($proximo, fn ($q) => $q->whereKeyNot($proximo->getKey()))
                ->limit(4)->get(),

            'passados' => Show::query()->with('local')->withCount($comMaterial)
                ->publicaveis()->passados()->limit(6)->get(),

            'integrantes' => Integrante::query()->noPalco()->get(),

            'videos' => Video::query()->with('local')->doSite()->limit(Video::LIMITE_NA_HOME)->get(),

            'fotos' => Foto::daVitrine(),
            'haGaleria' => Foto::query()->doSite()->exists() || Video::query()->doSite()->exists(),
            'destaques' => Musica::query()->publicadas()->where('destaque', true)->limit(8)->get(),
            'perguntas' => Pergunta::query()->publicadas()->get(),
            'depoimentos' => Depoimento::query()->publicaveis()->limit(3)->get(),
            'tiposEvento' => TipoEvento::opcoes(),
            'limiteDeVideos' => Video::LIMITE_NA_HOME,

            'showEmDestaque' => $this->showEmDestaque(),

            'estilos' => Musica::query()
                ->where('publicada', true)
                ->whereNotNull('estilo')->where('estilo', '!=', '')
                ->distinct()->orderBy('estilo')->pluck('estilo'),
        ]);
    }

    private function showEmDestaque(): ?Show
    {
        return Show::query()
            ->publicaveis()
            ->whereNotNull('cartaz_path')
            ->orderByDesc('destaque')
            ->orderByDesc('comeca_em')
            ->first();
    }

    public function previa(string $variante): View
    {
        abort_unless($this->comparandoLayout(), 404);

        $variantes = [
            'kit' => [
                'partial' => 'site._palco-kit',
                'folha' => 'resources/css/previa-kit.css',
            ],
            'v3' => [
                'partial' => 'site._palco-v3',
                'folha' => 'resources/css/previa-v3.css',
            ],
        ];

        abort_unless(isset($variantes[$variante]), 404);
        $escolhida = $variantes[$variante];

        $dados = $this->home()->getData();

        return view('site.home', [
            ...$dados,
            'palcoVariante' => $escolhida['partial'],
            'folhasExtras' => [$escolhida['folha']],
            'versoesDePrevia' => $this->versoesDePrevia($variante),
        ]);
    }

    private function versoesDePrevia(?string $atual): ?array
    {
        if (! $this->comparandoLayout()) {
            return null;
        }

        return [
            [
                'rotulo' => '1v',
                'titulo' => 'Versão 1 — o topo aprovado',
                'url' => route('site.home'),
                'atual' => $atual === null,
            ],
            [
                'rotulo' => '2v',
                'titulo' => 'Versão 2 — o palco do bar, do kit de marca novo',
                'url' => route('site.previa', 'kit'),
                'atual' => $atual === 'kit',
            ],
            [
                'rotulo' => '3v',
                'titulo' => 'Versão 3 — o nome da banda grande, sobre o palco',
                'url' => route('site.previa', 'v3'),
                'atual' => $atual === 'v3',
            ],
        ];
    }

    private function comparandoLayout(): bool
    {
        return app()->environment(['local', 'testing'])
            || (bool) config('site.comparacao_de_layout');
    }

    public function agenda(): View
    {
        $comMaterial = [
            'fotos as fotos_publicadas_count' => fn ($q) => $q->where('publicada', true),
            'videos as videos_publicados_count' => fn ($q) => $q->where('publicado', true),
        ];

        return view('site.agenda', [
            'config' => ConfiguracaoDoSite::todas(),

            'tiposEvento' => TipoEvento::opcoes(),
            'futuros' => Show::query()->with('local')->withCount($comMaterial)
                ->publicaveis()->futuros()->get(),
            'passados' => Show::query()->with('local')->withCount($comMaterial)
                ->publicaveis()->passados()->limit(30)->get(),

            'haGaleria' => Foto::query()->doSite()->exists() || Video::query()->doSite()->exists(),
            'capaDaGaleria' => Foto::daVitrine(1)->first(),
        ]);
    }

    public function banda(): View
    {
        $integrantes = Integrante::query()->noPalco()->with([
            'fotos' => fn ($q) => $q->doSite()->with(['show.local', 'tipoGaleria'])->maisRecentes(),
            'videos' => fn ($q) => $q->doSite()->with('local')->orderBy('ordem'),
        ])->get();

        return view('site.banda', [
            'config' => ConfiguracaoDoSite::todas(),
            'integrantes' => $integrantes,

            'participacoes' => ParticipacaoEspecial::query()->publicaveis()
                ->with(['shows' => fn ($q) => $q->publicaveis()->with('local')->reorder()->orderByDesc('comeca_em')])
                ->get(),
        ]);
    }

    public function showPeloUuid(string $uuid): RedirectResponse
    {
        $show = Show::query()->where('uuid', $uuid)->firstOrFail();

        abort_unless($show->motivosParaNaoAparecer() === [], 404);

        return redirect()->route('site.show', $show, 301);
    }

    public function show(Show $show): View
    {
        abort_unless($show->motivosParaNaoAparecer() === [], 404);

        $show->load([
            'local',
            'setlist.video',
            'fotos' => fn ($q) => $q->publicadas(),
            'videos' => fn ($q) => $q->publicados(),
            'depoimentos' => fn ($q) => $q->publicaveis(),
            'participacoes' => fn ($q) => $q->publicaveis(),
        ]);

        return view('site.show', [
            'config' => ConfiguracaoDoSite::todas(),
            'show' => $show,

            'materiais' => $show->materiais()->publicos()->get(),

            'levaParaAsNoites' => Foto::haGaleriaDasNoites() && Foto::haNoitesAlemDe($show),

            'vizinhos' => [
                'anterior' => Show::query()->with('local')->publicaveis()
                    ->where('comeca_em', '<', $show->comeca_em)->orderByDesc('comeca_em')->first(),
                'proximo' => Show::query()->with('local')->publicaveis()
                    ->where('comeca_em', '>', $show->comeca_em)
                    ->when(
                        Show::query()->publicaveis()->futuros()->value('id'),
                        fn ($q, $doTopo) => $q->whereKeyNot($doTopo),
                    )
                    ->orderBy('comeca_em')->first(),
            ],
        ]);
    }

    public function galeria(): View
    {
        return view('site.galeria', $this->dadosDaGaleria(null));
    }

    public function galeriaDoTipo(TipoGaleria $tipo): View
    {
        abort_unless($tipo->publicado, 404);

        return view('site.galeria', $this->dadosDaGaleria($tipo));
    }

    private function dadosDaGaleria(?TipoGaleria $tipo): array
    {
        $fotos = Foto::query()->doSite()
            ->when($tipo, fn ($q) => $q->doTipo($tipo))
            ->with(['show.local', 'tipoGaleria'])
            ->maisRecentes()
            ->paginate(Foto::POR_PAGINA_NA_GALERIA)
            ->withQueryString();

        $videos = Video::query()->doSite()
            ->when($tipo, fn ($q) => $q->doTipo($tipo))
            ->with(['local', 'show'])
            ->get();

        return [
            'config' => ConfiguracaoDoSite::todas(),
            'tipo' => $tipo,

            'abas' => TipoGaleria::comMaterial(),
            'fotos' => $fotos,
            'videos' => $videos,
        ];
    }

    public function repertorio(): View
    {
        $referencia = Show::query()->with('local')->publicaveis()
            ->has('setlist')->futuros()->first()
            ?? Show::query()->with('local')->publicaveis()
                ->has('setlist')->passados()->first();

        $referencia?->load(['setlist' => fn ($q) => $q->where('publicada', true)->with('video')]);

        $catalogo = Musica::query()->publicadas()->with('video')->get();

        $tetoDoAtual = 12;

        [$atual, $origemDoAtual] = match (true) {
            (bool) $referencia?->setlist?->isNotEmpty() => [$referencia->setlist, 'setlist'],
            $catalogo->where('destaque', true)->isNotEmpty() => [$catalogo->where('destaque', true)->values(), 'destaques'],
            default => [$catalogo->take($tetoDoAtual), 'catalogo'],
        };

        return view('site.repertorio', [
            'config' => ConfiguracaoDoSite::todas(),
            'referencia' => $origemDoAtual === 'setlist' ? $referencia : null,
            'atual' => $atual,
            'origemDoAtual' => $origemDoAtual,

            'outras' => $catalogo->whereNotIn('id', $atual->pluck('id'))->values(),
            'total' => $catalogo->count(),
        ]);
    }

    public function imprensa(): View
    {
        return view('site.imprensa', [
            'config' => ConfiguracaoDoSite::todas(),
            'materiais' => Material::query()->publicos()->get(),

            'publicacoes' => Publicacao::query()->publicaveis()->limit(12)->get(),

            'integrantes' => Integrante::query()->publicaveis()->get(),
            'fotos' => Foto::query()->publicadas()->limit(8)->get(),
        ]);
    }

    public function privacidade(): View
    {
        return view('site.privacidade', [
            'config' => ConfiguracaoDoSite::todas(),
            'retencao' => PoliticaRetencao::query()->orderBy('recurso')->get(),
        ]);
    }

    public function sitemap(): Response
    {
        return response()
            ->view('site.sitemap', [
                'config' => ConfiguracaoDoSite::todas(),
                'atualizadoEm' => Show::query()->publicaveis()->max('updated_at'),

                'abasDaGaleria' => TipoGaleria::comMaterial(),

                'shows' => Show::query()->publicaveis()
                    ->where(fn ($q) => $q
                        ->whereNotNull('observacoes_publicas')
                        ->orWhereHas('fotos', fn ($f) => $f->where('publicada', true))
                        ->orWhereHas('videos', fn ($v) => $v->where('publicado', true))
                        ->orHas('setlist'))
                    ->orderByDesc('comeca_em')->limit(200)->get(),
            ])
            ->header('Content-Type', 'application/xml');
    }

    public function llms(): Response
    {
        $texto = view('site.llms', [
            'config' => ConfiguracaoDoSite::todas(),
            'futuros' => Show::query()->with('local')->publicaveis()->futuros()->limit(10)->get(),
            'musicas' => Musica::query()->publicadas()->limit(30)->get(),
            'perguntas' => Pergunta::query()->publicadas()->get(),
            'publicacoes' => Publicacao::query()->publicaveis()->limit(12)->get(),
        ])->render();

        return response(html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
