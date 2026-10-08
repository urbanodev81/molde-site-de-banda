<?php

declare(strict_types=1);

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Enums\TipoVideo;
use App\Models\Foto;
use App\Models\Local;
use App\Models\Show;
use App\Models\TipoGaleria;
use App\Models\Video;

beforeEach(function () {
    $this->local = Local::create(['nome' => 'Bar do Zé', 'slug' => 'bar-do-ze']);
});

function noite(array $atributos = []): Show
{
    return Show::create([
        'local_id' => test()->local->id,
        'comeca_em' => now()->subWeek(),
        'status' => StatusShow::Realizado,
        'tipo' => TipoShow::Publico,
        'publicado' => true,
        ...$atributos,
    ]);
}

function fotoDe(?Show $show, string $legenda, array $atributos = []): Foto
{
    return Foto::create([
        'show_id' => $show?->id,
        'arquivo_path' => 'sementes/palco.webp',
        'legenda' => $legenda,
        'publicada' => true,
        ...$atributos,
    ]);
}

function tipoDeGaleria(string $nome, array $atributos = []): TipoGaleria
{
    return TipoGaleria::create(['nome' => $nome, ...$atributos]);
}

it('mostra na home as fotos em destaque antes das outras', function () {
    $s = noite();

    fotoDe($s, 'A recente', ['registrada_em' => now()->subDay()]);
    fotoDe($s, 'A escolhida', ['registrada_em' => now()->subYear(), 'destaque' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    expect(strpos($html, 'A escolhida'))->toBeLessThan(strpos($html, 'A recente'));
});

it('⚠️ completa a vitrine com as mais recentes quando não há destaque nenhum', function () {
    $s = noite();
    fotoDe($s, 'Sem destaque nenhum');

    $this->get('/')->assertOk()->assertSee('Sem destaque nenhum');
});

it('⚠️ para em oito fotos na home, por mais que a banda suba', function () {
    $s = noite();

    for ($i = 1; $i <= 12; $i++) {
        fotoDe($s, "Foto numero {$i}", ['ordem' => $i]);
    }

    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'data-foto='))->toBe(Foto::LIMITE_NA_HOME);
});

it('a home manda para a galeria', function () {
    $s = noite();
    fotoDe($s, 'Publica');

    $this->get('/')
        ->assertOk()
        ->assertSee('Ver galeria')
        ->assertSee(route('site.galeria'), false);
});

it('⚠️ a home junta vídeo e foto numa galeria só, e ela vem logo depois da agenda', function () {
    $s = noite();
    fotoDe($s, 'Foto da noite');
    Video::create(['titulo' => 'Clipe da noite', 'tipo' => TipoVideo::Youtube, 'youtube_id' => 'dQw4w9WgXcQ', 'show_id' => $s->id, 'publicado' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'id="tit-galeria"'))->toBe(1)
        ->and($html)->not->toContain('id="tit-videos"');

    $galeria = strpos($html, 'id="galeria"');

    $clipe = strpos($html, 'data-titulo="Clipe da noite"');
    $foto = strpos($html, 'data-legenda="Foto da noite"');

    expect(strpos($html, 'id="agenda"'))->toBeLessThan($galeria)
        ->and($galeria)->toBeLessThan($clipe)
        ->and($clipe)->toBeLessThan($foto)
        ->and($foto)->toBeLessThan(strpos($html, 'id="contratar"'));
});

it('⚠️ a home NÃO mostra foto de evento particular', function () {
    fotoDe(noite(), 'Publica');
    fotoDe(noite(['tipo' => TipoShow::Particular]), 'Da festa privada');

    $this->get('/')
        ->assertOk()
        ->assertSee('Publica')
        ->assertDontSee('Da festa privada');
});

it('⚠️ a home NÃO mostra foto de show cancelado nem de rascunho', function () {
    fotoDe(noite(), 'Publica');
    fotoDe(noite(['status' => StatusShow::Cancelado]), 'Do cancelado');
    fotoDe(noite(['publicado' => false]), 'Do rascunho');

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Do cancelado')
        ->assertDontSee('Do rascunho');
});

it('mostra na galeria a foto de show e a foto de ensaio, cada uma na sua aba', function () {
    fotoDe(noite(), 'De uma noite');
    fotoDe(null, 'De um ensaio', ['tipo_galeria_id' => tipoDeGaleria('Ensaio')->id]);

    $this->get(route('site.galeria'))
        ->assertOk()
        ->assertSee('De uma noite')
        ->assertSee('De um ensaio')

        ->assertSee(route('site.galeria.tipo', 'ensaio'), false);

    $this->get(route('site.galeria.tipo', 'ensaio'))
        ->assertOk()
        ->assertSee('De um ensaio')
        ->assertDontSee('De uma noite');
});

it('⚠️ a aba de shows herda a foto que tem show e nenhum tipo', function () {
    $tipo = tipoDeGaleria('Shows', ['slug' => TipoGaleria::SLUG_DOS_SHOWS]);
    fotoDe(noite(), 'Sem tipo, com show');

    expect(Foto::query()->doSite()->doTipo($tipo)->count())->toBe(1);

    $this->get(route('site.galeria.tipo', TipoGaleria::SLUG_DOS_SHOWS))
        ->assertOk()
        ->assertSee('Sem tipo, com show');
});

it('⚠️ NÃO publica foto solta sem tipo — a porta é fail-closed', function () {
    fotoDe(null, 'Guardada no painel, sem tipo');

    $this->get(route('site.galeria'))->assertOk()->assertDontSee('Guardada no painel, sem tipo');
    $this->get('/')->assertOk()->assertDontSee('Guardada no painel, sem tipo');
});

it('⚠️ NÃO publica foto de tipo que está fora do ar, nem abre a aba dele', function () {
    $escondido = tipoDeGaleria('Bastidores', ['publicado' => false]);
    fotoDe(null, 'Do camarim', ['tipo_galeria_id' => $escondido->id]);

    $this->get(route('site.galeria'))->assertOk()->assertDontSee('Do camarim');
    $this->get(route('site.galeria.tipo', 'bastidores'))->assertNotFound();
});

it('⚠️ não publica na galeria a foto de evento particular, nem com tipo publicado', function () {
    $tipo = tipoDeGaleria('Bastidores');
    fotoDe(noite(['tipo' => TipoShow::Particular]), 'Da festa privada', ['tipo_galeria_id' => $tipo->id]);

    $this->get(route('site.galeria'))->assertOk()->assertDontSee('Da festa privada');
    $this->get(route('site.galeria.tipo', 'bastidores'))->assertOk()->assertDontSee('Da festa privada');
});

it('não traz foto despublicada nem foto de show que ainda vai acontecer', function () {
    fotoDe(noite(), 'Rejeitada', ['publicada' => false]);
    fotoDe(noite(['comeca_em' => now()->addWeek(), 'status' => StatusShow::Confirmado]), 'De um show que nem rolou');

    $this->get(route('site.galeria'))
        ->assertOk()
        ->assertDontSee('Rejeitada')
        ->assertDontSee('De um show que nem rolou');
});

it('⚠️ só mostra aba que tem material', function () {
    tipoDeGaleria('Gravação');
    fotoDe(noite(), 'De uma noite');

    $this->get(route('site.galeria'))->assertOk()->assertDontSee(route('site.galeria.tipo', 'gravacao'), false);
});

it('pagina o acervo em vez de servir tudo de uma vez', function () {
    $s = noite();

    for ($i = 1; $i <= Foto::POR_PAGINA_NA_GALERIA + 3; $i++) {
        fotoDe($s, "Foto numero {$i}", ['registrada_em' => now()->subDays($i)]);
    }

    $html = $this->get(route('site.galeria'))->assertOk()->getContent();
    expect(substr_count($html, 'data-foto='))->toBe(Foto::POR_PAGINA_NA_GALERIA);

    $this->get(route('site.galeria').'?page=2')->assertOk()->assertSee('Foto numero '.(Foto::POR_PAGINA_NA_GALERIA + 1));
});

it('mostra os vídeos na galeria, separados das fotos', function () {
    $tipo = tipoDeGaleria('Ensaio');
    fotoDe(null, 'Foto do ensaio', ['tipo_galeria_id' => $tipo->id]);

    Video::create([
        'titulo' => 'Ensaio de quinta', 'tipo' => TipoVideo::Youtube, 'youtube_id' => 'dQw4w9WgXcQ',
        'tipo_galeria_id' => $tipo->id, 'publicado' => true,
    ]);

    $this->get(route('site.galeria.tipo', 'ensaio'))
        ->assertOk()
        ->assertSee('Ensaio de quinta')
        ->assertSee('Foto do ensaio')

        ->assertSee('tit-galeria-videos', false)
        ->assertSee('tit-galeria-fotos', false);
});

it('⚠️ o vídeo de um evento particular não vai para o site', function () {
    Video::create([
        'titulo' => 'Aniversario da Fulana', 'tipo' => TipoVideo::Youtube, 'youtube_id' => 'dQw4w9WgXcQ',
        'show_id' => noite(['tipo' => TipoShow::Particular])->id, 'publicado' => true,
    ]);

    $this->get('/')->assertOk()->assertDontSee('Aniversario da Fulana');
    $this->get(route('site.galeria'))->assertOk()->assertDontSee('Aniversario da Fulana');
});

it('⚠️ toda foto do site abre no visualizador', function () {
    $s = noite(['comeca_em' => now()->subWeek()]);
    fotoDe($s, 'A casa cheia', ['credito' => 'Fulana']);

    foreach (['/', route('site.galeria'), route('site.show', $s)] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertSee('id="modal-foto"', false)
            ->assertSee('data-foto=', false);
    }
});

it('⚠️ o "ver a noite inteira" só vai para o visualizador na vitrine da home', function () {
    $s = noite(['comeca_em' => now()->subWeek()]);
    fotoDe($s, 'A casa cheia');
    $link = 'data-link="'.route('site.show', $s).'"';

    $this->get('/')->assertOk()->assertSee($link, false);

    foreach ([route('site.galeria'), route('site.show', $s)] as $url) {
        $this->get($url)->assertOk()
            ->assertSee('data-foto=', false)
            ->assertDontSee($link, false);
    }
});

it('leva o crédito e a legenda para dentro do visualizador', function () {
    $s = noite();
    fotoDe($s, 'A casa cheia', ['credito' => 'Fulana']);

    $this->get(route('site.galeria'))
        ->assertOk()
        ->assertSee('data-legenda="A casa cheia"', false)
        ->assertSee('data-credito="Fulana"', false);
});

it('a galeria de um show se diz DAQUELA NOITE, com a data', function () {
    $s = noite(['comeca_em' => now()->subWeek()->setTime(21, 0)]);
    fotoDe($s, 'Fulana');

    $this->get(route('site.show', $s))
        ->assertOk()
        ->assertSee('Só desta noite', false)
        ->assertSee($s->comeca_em->translatedFormat('d \d\e F \d\e Y'), false);
});

it('leva da noite para a galeria quando há outras noites', function () {
    $esta = noite(['comeca_em' => now()->subWeek()]);
    fotoDe($esta, 'Desta noite');

    $outra = noite(['comeca_em' => now()->subMonth()]);
    fotoDe($outra, 'De outra 1');
    fotoDe($outra, 'De outra 2');

    $this->get(route('site.show', $esta))
        ->assertOk()
        ->assertSee('Ver as fotos de todas as noites')
        ->assertSee(route('site.galeria'), false);
});

it('⚠️ NÃO leva para a galeria quando a única noite com foto é esta', function () {
    $s = noite();
    fotoDe($s, 'Fulana');
    fotoDe($s, 'Beltrana');
    fotoDe($s, 'Sicrana');

    $this->get(route('site.show', $s))
        ->assertOk()
        ->assertDontSee('Ver as fotos de todas as noites');
});

it('não diz "só desta noite" num show que ainda vai acontecer', function () {
    $futuro = noite(['comeca_em' => now()->addWeek(), 'status' => StatusShow::Confirmado]);

    $this->get(route('site.show', $futuro))
        ->assertOk()
        ->assertDontSee('Só desta noite', false);
});

it('a agenda convida para a galeria em vez de repetir o índice', function () {
    $s = noite();
    fotoDe($s, 'De uma noite');

    $this->get('/agenda')
        ->assertOk()
        ->assertSee('As noites em imagens')
        ->assertSee(route('site.galeria'), false);
});

it('⚠️ não convida para a galeria quando não há nada nela', function () {
    noite();

    $this->get('/agenda')->assertOk()->assertDontSee('As noites em imagens');
});

it('a galeria some do sitemap quando a aba não tem material', function () {
    tipoDeGaleria('Gravação');
    fotoDe(null, 'De um ensaio', ['tipo_galeria_id' => tipoDeGaleria('Ensaio')->id]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee(route('site.galeria'), false)
        ->assertSee(route('site.galeria.tipo', 'ensaio'), false)
        ->assertDontSee(route('site.galeria.tipo', 'gravacao'), false);
});

it('escreve o título sobre a foto na grade e leva a descrição ao visualizador', function () {
    $s = noite();
    fotoDe($s, 'As três no palco', ['descricao' => 'Primeira noite na casa.']);

    $this->get(route('site.show', $s))
        ->assertOk()
        ->assertSee('<span class="foto__titulo" aria-hidden="true">As três no palco</span>', false)
        ->assertSee('data-descricao="Primeira noite na casa."', false)
        ->assertSee('id="modal-foto-descricao"', false);
});

it('⚠️ foto sem título não ganha faixa vazia sobre a imagem', function () {
    $s = noite();
    Foto::create(['show_id' => $s->id, 'arquivo_path' => 'sementes/palco.webp', 'publicada' => true]);

    $this->get(route('site.show', $s))
        ->assertOk()
        ->assertSee('data-foto=', false)
        ->assertDontSee('class="foto__titulo"', false);
});

it('⚠️ o visualizador traz a ficha ANTES da foto', function () {
    $s = noite();
    fotoDe($s, 'A casa cheia');

    $html = $this->get(route('site.show', $s))->assertOk()->getContent();
    $modal = substr($html, strpos($html, 'id="modal-foto"'));

    expect(strpos($modal, 'id="modal-foto-titulo"'))->toBeLessThan(strpos($modal, 'id="modal-foto-imagem"'));
});

it('a página do show separa os vídeos das fotos, cada bloco com nome', function () {
    $s = noite();
    fotoDe($s, 'A casa cheia');
    Video::create([
        'titulo' => 'Titanium', 'tipo' => TipoVideo::Youtube, 'youtube_id' => 'dQw4w9WgXcQ',
        'show_id' => $s->id, 'publicado' => true,
    ]);

    $html = $this->get(route('site.show', $s))->assertOk()->getContent();

    expect($html)->toContain('id="tit-noite-videos">Vídeos</h3>')
        ->toContain('id="tit-noite-fotos">Fotos</h3>')
        ->and(strpos($html, 'id="tit-noite-videos"'))->toBeLessThan(strpos($html, 'data-video'))
        ->and(strpos($html, 'id="tit-noite-fotos"'))->toBeLessThan(strpos($html, 'data-foto='))
        ->and(strpos($html, 'data-video'))->toBeLessThan(strpos($html, 'id="tit-noite-fotos"'));
});

it('⚠️ bloco sem material não aparece com título vazio', function () {
    $s = noite();
    fotoDe($s, 'A casa cheia');

    $this->get(route('site.show', $s))
        ->assertOk()
        ->assertSee('id="tit-noite-fotos"', false)
        ->assertDontSee('id="tit-noite-videos"', false);
});
