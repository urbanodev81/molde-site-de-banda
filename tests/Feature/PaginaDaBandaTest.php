<?php

declare(strict_types=1);

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Enums\TipoVideo;
use App\Models\Foto;
use App\Models\Integrante;
use App\Models\Local;
use App\Models\Show;
use App\Models\User;
use App\Models\Video;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;

beforeEach(function () {
    $this->local = Local::create(['nome' => 'Bar do Centro', 'slug' => 'bar-do-centro']);
});

function daBanda(int $ordem, array $extra = []): Integrante
{
    return Integrante::create([
        'nome' => "Integrante $ordem",
        'ordem' => $ordem,
        'ativa' => true,
        'recorte_path' => "sementes/integrante-0$ordem.png",
        'autorizacao_imagem_em' => '2026-09-08',
        ...$extra,
    ]);
}

function noiteDaBanda(array $extra = []): Show
{
    return Show::create([
        'local_id' => test()->local->id,
        'comeca_em' => now()->subWeek(),
        'status' => StatusShow::Realizado,
        'tipo' => TipoShow::Publico,
        'publicado' => true,
        ...$extra,
    ]);
}

it('apresenta cada integrante com função, descrição e as redes dela', function () {
    daBanda(1, [
        'nome' => 'Ana', 'instrumento' => 'Voz',
        'bio' => 'Começou no coral da escola.',
        'instagram' => 'https://instagram.com/ana',
    ]);

    $this->get(route('site.banda'))
        ->assertOk()
        ->assertSee('Ana')
        ->assertSee('Voz')
        ->assertSee('Começou no coral da escola.')
        ->assertSee('href="https://instagram.com/ana"', false)
        ->assertSee('aria-label="Instagram de Ana"', false);
});

it('mostra as fotos e os vídeos marcados para ela, em blocos separados', function () {
    $ana = daBanda(1, ['nome' => 'Ana']);
    $noite = noiteDaBanda();

    $foto = Foto::create(['show_id' => $noite->id, 'arquivo_path' => 'fotos/a.webp', 'legenda' => 'As três no palco', 'publicada' => true]);
    $video = Video::create(['titulo' => 'Titanium', 'tipo' => TipoVideo::Youtube, 'youtube_id' => 'dQw4w9WgXcQ', 'show_id' => $noite->id, 'publicado' => true]);
    $ana->fotos()->attach($foto);
    $ana->videos()->attach($video);

    $html = $this->get(route('site.banda'))->assertOk()->getContent();

    expect($html)
        ->toContain('id="integrante-1-videos">Vídeos</h3>')
        ->toContain('id="integrante-1-fotos">Fotos</h3>')
        ->toContain('As três no palco')
        ->toContain('Titanium')
        ->toContain('data-grupo="integrante-1"');
});

it('⚠️ a mesma foto aparece no bloco das três — é muitos-para-muitos', function () {
    $foto = Foto::create(['show_id' => noiteDaBanda()->id, 'arquivo_path' => 'fotos/a.webp', 'legenda' => 'As três', 'publicada' => true]);

    foreach ([1, 2, 3] as $ordem) {
        daBanda($ordem)->fotos()->attach($foto);
    }

    $html = $this->get(route('site.banda'))->assertOk()->getContent();

    expect(substr_count($html, 'data-legenda="As três"'))->toBe(3);
});

it('⚠️ marcar para a integrante NÃO publica foto de evento particular', function () {
    $ana = daBanda(1);
    $foto = Foto::create([
        'show_id' => noiteDaBanda(['tipo' => TipoShow::Particular])->id,
        'arquivo_path' => 'fotos/casamento.webp', 'legenda' => 'Casamento da Fulana', 'publicada' => true,
    ]);
    $ana->fotos()->attach($foto);

    $this->get(route('site.banda'))
        ->assertOk()
        ->assertDontSee('Casamento da Fulana')
        ->assertDontSee('id="integrante-1-fotos"', false);
});

it('⚠️ sem autorização de imagem, o nome fica "a definir" também aqui', function () {
    daBanda(1, ['nome' => 'Fulana Sem Ok', 'autorizacao_imagem_em' => null]);

    $this->get(route('site.banda'))
        ->assertOk()
        ->assertDontSee('Fulana Sem Ok')
        ->assertSee('a definir');
});

it('não mostra integrante inativa', function () {
    daBanda(1, ['nome' => 'Ex Integrante', 'ativa' => false]);

    $this->get(route('site.banda'))->assertOk()->assertDontSee('Ex Integrante');
});

it('a página está no sitemap', function () {
    $this->get(route('site.sitemap'))->assertOk()->assertSee('/a-banda', false);
});

it('a banda marca quem aparece na foto e no vídeo pelo painel', function () {
    $this->seed(PerfisESeguranca::class);
    $usuaria = User::create([
        'name' => 'Banda', 'email' => 'banda@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $usuaria->syncRoles([Perfis::BANDA]);

    $ana = daBanda(1);
    $bia = daBanda(2);
    $foto = Foto::create(['arquivo_path' => 'fotos/a.webp', 'publicada' => true]);

    $this->actingAs($usuaria)
        ->put(route('painel.fotos.update', $foto), ['integrantes' => [$ana->id, $bia->id], 'publicada' => true, 'destaque' => false])
        ->assertSessionHasNoErrors();

    expect($foto->integrantes()->pluck('integrantes.id')->all())->toEqualCanonicalizing([$ana->id, $bia->id]);

    $this->actingAs($usuaria)
        ->put(route('painel.fotos.update', $foto), ['integrantes' => [], 'publicada' => true, 'destaque' => false])
        ->assertSessionHasNoErrors();

    expect($foto->integrantes()->count())->toBe(0);
});

it('⚠️ editar um vídeo de show não o tira da noite', function () {
    $this->seed(PerfisESeguranca::class);
    $usuaria = User::create([
        'name' => 'Banda', 'email' => 'banda2@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $usuaria->syncRoles([Perfis::BANDA]);

    $video = Video::create(['titulo' => 'Titanium', 'tipo' => TipoVideo::Youtube, 'youtube_id' => 'dQw4w9WgXcQ', 'show_id' => noiteDaBanda()->id, 'publicado' => true]);

    $this->actingAs($usuaria)
        ->get(route('painel.videos.edit', $video))
        ->assertInertia(fn ($pagina) => $pagina->where('video.show_id', $video->show_id));
});
