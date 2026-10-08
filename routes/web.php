<?php

declare(strict_types=1);

use App\Http\Controllers\CaptchaChallengeController;
use App\Http\Controllers\ExportarDadosDoTitularController;
use App\Http\Controllers\FeedbackDeTelaController;
use App\Http\Controllers\Painel\AcaoEmLoteController;
use App\Http\Controllers\Painel\AcaoEmLoteFotoController;
use App\Http\Controllers\Painel\AuditoriaController;
use App\Http\Controllers\Painel\ConfiguracaoController;
use App\Http\Controllers\Painel\ContratacaoController;
use App\Http\Controllers\Painel\DepoimentoController;
use App\Http\Controllers\Painel\EnviarListaController;
use App\Http\Controllers\Painel\EnviarListaDeFotosController;
use App\Http\Controllers\Painel\EstatisticaController;
use App\Http\Controllers\Painel\FotoController;
use App\Http\Controllers\Painel\ImprimirFotosController;
use App\Http\Controllers\Painel\ImprimirListaController;
use App\Http\Controllers\Painel\IntegranteController;
use App\Http\Controllers\Painel\LocalController;
use App\Http\Controllers\Painel\MaterialController;
use App\Http\Controllers\Painel\MaterialVinculoController;
use App\Http\Controllers\Painel\MidiaDoShowController;
use App\Http\Controllers\Painel\MusicaController;
use App\Http\Controllers\Painel\PainelController;
use App\Http\Controllers\Painel\ParticipacaoEspecialController;
use App\Http\Controllers\Painel\PerguntaController;
use App\Http\Controllers\Painel\PublicacaoController;
use App\Http\Controllers\Painel\SetlistController;
use App\Http\Controllers\Painel\ShowController;
use App\Http\Controllers\Painel\TipoEspacoController;
use App\Http\Controllers\Painel\TipoGaleriaController;
use App\Http\Controllers\Painel\UsuarioController;
use App\Http\Controllers\Painel\VideoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\ReportarErroController;
use App\Http\Controllers\Site\ContratacaoPublicaController;
use App\Http\Controllers\Site\SiteController;
use App\Http\Middleware\SoComSiteAberto;
use App\Support\Lote\ListasEmLote;
use Illuminate\Support\Facades\Route;

Route::middleware(SoComSiteAberto::class)->group(function () {
    Route::get('/', [SiteController::class, 'home'])->name('site.home');

    Route::get('/a-banda', [SiteController::class, 'banda'])->name('site.banda');
    Route::get('/agenda', [SiteController::class, 'agenda'])->name('site.agenda');

    Route::get('/agenda/{uuid}', [SiteController::class, 'showPeloUuid'])->whereUuid('uuid')->name('site.show.uuid');
    Route::get('/agenda/{show:slug}', [SiteController::class, 'show'])->name('site.show');
    Route::get('/repertorio', [SiteController::class, 'repertorio'])->name('site.repertorio');

    Route::get('/galeria', [SiteController::class, 'galeria'])->name('site.galeria');
    Route::get('/galeria/{tipo:slug}', [SiteController::class, 'galeriaDoTipo'])->name('site.galeria.tipo');

    Route::get('/previa/{variante}', [SiteController::class, 'previa'])->name('site.previa');
    Route::get('/imprensa', [SiteController::class, 'imprensa'])->name('site.imprensa');
    Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('site.sitemap');
    Route::get('/llms.txt', [SiteController::class, 'llms'])->name('site.llms');
});

Route::get('/privacidade', [SiteController::class, 'privacidade'])->name('site.privacidade');

Route::post('/contratar', ContratacaoPublicaController::class)
    ->middleware('throttle:5,10')
    ->name('site.contratar');

Route::get('/captcha/desafio', CaptchaChallengeController::class)
    ->middleware('throttle:60,1')
    ->name('captcha.desafio');

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');
Route::get('/offline', [PwaController::class, 'offline'])->name('pwa.offline');
Route::get('/inicio', [PwaController::class, 'inicio'])->name('pwa.inicio');

Route::middleware(['auth', 'verified'])->prefix('painel')->name('painel.')->group(function () {
    Route::get('/', [PainelController::class, 'index'])->name('inicio');

    foreach (ListasEmLote::todas() as $slug => $lista) {
        Route::get("{$slug}/imprimir", ImprimirListaController::class)
            ->middleware("can:{$lista['ver']}")->defaults('lista', $slug)->name("{$slug}.imprimir");

        Route::post("{$slug}/lote", AcaoEmLoteController::class)
            ->middleware("can:{$lista['gerenciar']}")->defaults('lista', $slug)->name("{$slug}.lote");

        if ($lista['publicos'] !== null) {
            Route::post("{$slug}/lote/enviar", EnviarListaController::class)
                ->middleware(["can:{$lista['gerenciar']}", 'throttle:6,1'])->defaults('lista', $slug)->name("{$slug}.lote.enviar");
        }
    }

    Route::middleware('can:shows.ver')->group(function () {
        Route::get('shows', [ShowController::class, 'index'])->name('shows.index');
        Route::get('shows/{show}', [ShowController::class, 'show'])->name('shows.show');
    });
    Route::middleware('can:shows.gerenciar')->group(function () {
        Route::get('shows/criar/novo', [ShowController::class, 'create'])->name('shows.create');
        Route::post('shows', [ShowController::class, 'store'])->name('shows.store');
        Route::get('shows/{show}/editar', [ShowController::class, 'edit'])->name('shows.edit');
        Route::put('shows/{show}', [ShowController::class, 'update'])->name('shows.update');
        Route::delete('shows/{show}', [ShowController::class, 'destroy'])->name('shows.destroy');
        Route::put('shows/{show}/setlist', SetlistController::class)->name('shows.setlist');
    });

    Route::middleware('can:videos.gerenciar')->group(function () {
        Route::post('shows/{show}/videos', [MidiaDoShowController::class, 'enviarVideo'])->name('shows.videos.store');
        Route::put('shows/{show}/videos', [MidiaDoShowController::class, 'vincularVideo'])->name('shows.videos.vincular');
        Route::delete('shows/{show}/videos/{video}', [MidiaDoShowController::class, 'desligarVideo'])->name('shows.videos.desligar');
    });
    Route::delete('shows/{show}/fotos/{foto}', [MidiaDoShowController::class, 'desligarFoto'])
        ->middleware('can:fotos.gerenciar')->name('shows.fotos.desligar');

    Route::get('locais', [LocalController::class, 'index'])->middleware('can:locais.ver')->name('locais.index');
    Route::middleware('can:locais.gerenciar')->group(function () {
        Route::get('locais/criar/nova', [LocalController::class, 'create'])->name('locais.create');
        Route::post('locais', [LocalController::class, 'store'])->name('locais.store');
        Route::get('locais/{local}/editar', [LocalController::class, 'edit'])->name('locais.edit');
        Route::put('locais/{local}', [LocalController::class, 'update'])->name('locais.update');
        Route::delete('locais/{local}', [LocalController::class, 'destroy'])->name('locais.destroy');
    });

    Route::get('integrantes', [IntegranteController::class, 'index'])
        ->middleware('can:integrantes.ver')->name('integrantes.index');
    Route::middleware('can:integrantes.gerenciar')->group(function () {
        Route::get('integrantes/criar/nova', [IntegranteController::class, 'create'])->name('integrantes.create');
        Route::post('integrantes', [IntegranteController::class, 'store'])->name('integrantes.store');
        Route::get('integrantes/{integrante}/editar', [IntegranteController::class, 'edit'])->name('integrantes.edit');
        Route::put('integrantes/{integrante}', [IntegranteController::class, 'update'])->name('integrantes.update');
        Route::delete('integrantes/{integrante}', [IntegranteController::class, 'destroy'])->name('integrantes.destroy');
    });

    Route::get('participacoes', [ParticipacaoEspecialController::class, 'index'])
        ->middleware('can:integrantes.ver')->name('participacoes.index');
    Route::middleware('can:integrantes.gerenciar')->group(function () {
        Route::get('participacoes/criar/nova', [ParticipacaoEspecialController::class, 'create'])->name('participacoes.create');
        Route::post('participacoes', [ParticipacaoEspecialController::class, 'store'])->name('participacoes.store');
        Route::get('participacoes/{participacao}/editar', [ParticipacaoEspecialController::class, 'edit'])->name('participacoes.edit');
        Route::put('participacoes/{participacao}', [ParticipacaoEspecialController::class, 'update'])->name('participacoes.update');
        Route::delete('participacoes/{participacao}', [ParticipacaoEspecialController::class, 'destroy'])->name('participacoes.destroy');
    });

    Route::get('videos', [VideoController::class, 'index'])->middleware('can:videos.ver')->name('videos.index');
    Route::middleware('can:videos.gerenciar')->group(function () {
        Route::get('videos/criar/novo', [VideoController::class, 'create'])->name('videos.create');
        Route::post('videos', [VideoController::class, 'store'])->name('videos.store');
        Route::get('videos/{video}/editar', [VideoController::class, 'edit'])->name('videos.edit');
        Route::put('videos/{video}', [VideoController::class, 'update'])->name('videos.update');
        Route::delete('videos/{video}', [VideoController::class, 'destroy'])->name('videos.destroy');
    });

    Route::get('musicas', [MusicaController::class, 'index'])->middleware('can:musicas.ver')->name('musicas.index');
    Route::middleware('can:musicas.gerenciar')->group(function () {
        Route::post('musicas', [MusicaController::class, 'store'])->name('musicas.store');
        Route::put('musicas/{musica}', [MusicaController::class, 'update'])->name('musicas.update');
        Route::delete('musicas/{musica}', [MusicaController::class, 'destroy'])->name('musicas.destroy');
    });

    Route::get('fotos', [FotoController::class, 'index'])->middleware('can:fotos.ver')->name('fotos.index');
    Route::get('fotos/imprimir', ImprimirFotosController::class)->middleware('can:fotos.ver')->name('fotos.imprimir');
    Route::middleware('can:fotos.gerenciar')->group(function () {
        Route::post('fotos', [FotoController::class, 'store'])->name('fotos.store');
        Route::put('fotos/{foto}', [FotoController::class, 'update'])->name('fotos.update');
        Route::delete('fotos/{foto}', [FotoController::class, 'destroy'])->name('fotos.destroy');

        Route::post('fotos/lote', AcaoEmLoteFotoController::class)->name('fotos.lote');

        Route::post('fotos/lote/enviar', EnviarListaDeFotosController::class)
            ->middleware('throttle:6,1')->name('fotos.lote.enviar');
    });

    Route::get('tipos-galeria', [TipoGaleriaController::class, 'index'])
        ->middleware('can:fotos.ver')->name('tipos-galeria.index');

    Route::middleware('can:fotos.gerenciar')->group(function () {
        Route::post('tipos-galeria', [TipoGaleriaController::class, 'store'])->name('tipos-galeria.store');
        Route::put('tipos-galeria/{tipoGaleria:id}', [TipoGaleriaController::class, 'update'])->name('tipos-galeria.update');
        Route::delete('tipos-galeria/{tipoGaleria:id}', [TipoGaleriaController::class, 'destroy'])->name('tipos-galeria.destroy');
    });

    Route::get('tipos-espaco', [TipoEspacoController::class, 'index'])
        ->middleware('can:locais.ver')->name('tipos-espaco.index');
    Route::middleware('can:locais.gerenciar')->group(function () {
        Route::post('tipos-espaco', [TipoEspacoController::class, 'store'])->name('tipos-espaco.store');
        Route::put('tipos-espaco/{tipoEspaco:id}', [TipoEspacoController::class, 'update'])->name('tipos-espaco.update');
        Route::delete('tipos-espaco/{tipoEspaco:id}', [TipoEspacoController::class, 'destroy'])->name('tipos-espaco.destroy');
    });

    Route::middleware('can:perguntas.gerenciar')->group(function () {
        Route::get('perguntas', [PerguntaController::class, 'index'])->name('perguntas.index');
        Route::post('perguntas', [PerguntaController::class, 'store'])->name('perguntas.store');
        Route::put('perguntas/{pergunta}', [PerguntaController::class, 'update'])->name('perguntas.update');
        Route::delete('perguntas/{pergunta}', [PerguntaController::class, 'destroy'])->name('perguntas.destroy');
    });

    Route::middleware('can:depoimentos.gerenciar')->group(function () {
        Route::get('depoimentos', [DepoimentoController::class, 'index'])->name('depoimentos.index');
        Route::post('depoimentos', [DepoimentoController::class, 'store'])->name('depoimentos.store');
        Route::put('depoimentos/{depoimento}', [DepoimentoController::class, 'update'])->name('depoimentos.update');
        Route::delete('depoimentos/{depoimento}', [DepoimentoController::class, 'destroy'])->name('depoimentos.destroy');
    });

    Route::middleware('can:publicacoes.gerenciar')->group(function () {
        Route::get('publicacoes', [PublicacaoController::class, 'index'])->name('publicacoes.index');
        Route::post('publicacoes', [PublicacaoController::class, 'store'])->name('publicacoes.store');
        Route::put('publicacoes/{publicacao}', [PublicacaoController::class, 'update'])->name('publicacoes.update');
        Route::delete('publicacoes/{publicacao}', [PublicacaoController::class, 'destroy'])->name('publicacoes.destroy');
    });

    Route::get('materiais', [MaterialController::class, 'index'])
        ->middleware('can:materiais.ver')->name('materiais.index');

    Route::get('materiais/{material}/baixar', [MaterialVinculoController::class, 'baixar'])
        ->middleware('can:materiais.ver')->name('materiais.baixar');
    Route::middleware('can:materiais.gerenciar')->group(function () {
        Route::post('materiais', [MaterialController::class, 'store'])->name('materiais.store');
        Route::put('materiais/{material}', [MaterialController::class, 'update'])->name('materiais.update');
        Route::delete('materiais/{material}', [MaterialController::class, 'destroy'])->name('materiais.destroy');

        Route::post('materiais/{material}/vinculos', [MaterialVinculoController::class, 'store'])->name('materiais.vinculos.store');
        Route::delete('materiais/{material}/vinculos/{vinculo}', [MaterialVinculoController::class, 'destroy'])->name('materiais.vinculos.destroy');
    });

    Route::middleware('can:contratacoes.ver')->group(function () {
        Route::get('contratacoes', [ContratacaoController::class, 'index'])->name('contratacoes.index');
        Route::get('contratacoes/{contratacao}', [ContratacaoController::class, 'show'])->name('contratacoes.show');
    });
    Route::middleware('can:contratacoes.gerenciar')->group(function () {
        Route::get('contratacoes/criar/novo', [ContratacaoController::class, 'create'])->name('contratacoes.create');
        Route::post('contratacoes', [ContratacaoController::class, 'store'])->name('contratacoes.store');
        Route::put('contratacoes/{contratacao}', [ContratacaoController::class, 'update'])->name('contratacoes.update');
        Route::post('contratacoes/{contratacao}/interacoes', [ContratacaoController::class, 'registrarInteracao'])
            ->name('contratacoes.interacoes.store');
        Route::delete('contratacoes/{contratacao}', [ContratacaoController::class, 'destroy'])->name('contratacoes.destroy');
    });

    Route::get('estatisticas', EstatisticaController::class)
        ->middleware('can:estatisticas.ver')->name('estatisticas.index');

    Route::get('configuracoes', [ConfiguracaoController::class, 'edit'])
        ->middleware('can:configuracoes.gerenciar')->name('configuracoes.edit');
    Route::put('configuracoes', [ConfiguracaoController::class, 'update'])
        ->middleware('can:configuracoes.gerenciar')->name('configuracoes.update');

    Route::get('usuarios', [UsuarioController::class, 'index'])
        ->middleware('can:usuarios.ver')->name('usuarios.index');
    Route::middleware('can:usuarios.gerenciar')->group(function () {
        Route::get('usuarios/criar/novo', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::delete('usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
    });

    Route::get('auditoria', AuditoriaController::class)
        ->middleware('can:auditoria.ver')->name('auditoria.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/perfil', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/perfil/meus-dados', ExportarDadosDoTitularController::class)->name('perfil.dados');

    Route::post('/push/inscrever', [PushSubscriptionController::class, 'store'])->name('push.store');
    Route::delete('/push/inscrever', [PushSubscriptionController::class, 'destroy'])->name('push.destroy');

    Route::post('/feedback-de-tela', FeedbackDeTelaController::class)
        ->middleware('throttle:10,1')->name('feedback-de-tela');

    Route::post('/reportar-erro', ReportarErroController::class)
        ->middleware('throttle:10,1')->name('reportar-erro');
});

require __DIR__.'/auth.php';
