<?php

declare(strict_types=1);

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Enums\TipoVideo;
use App\Models\Foto;
use App\Models\Local;
use App\Models\Musica;
use App\Models\Show;
use App\Models\Video;
use App\Support\Youtube;

beforeEach(function () {
    $this->local = Local::create(['nome' => 'Bar do Zé', 'slug' => 'bar-do-ze']);
});

function show(array $atributos = []): Show
{
    return Show::create([
        'local_id' => test()->local->id,
        'comeca_em' => now()->addWeek(),
        'status' => StatusShow::Confirmado,
        'tipo' => TipoShow::Publico,
        'publicado' => true,
        ...$atributos,
    ]);
}

it('abre a página de um show pelo endereço legível', function () {
    $s = show(['observacoes_publicas' => 'Dois sets, com intervalo.']);

    $this->get(route('site.show', $s))
        ->assertOk()
        ->assertSee('Bar do Zé')
        ->assertSee('Dois sets, com intervalo.');
});

it('o endereço é show-local-mês-ano, nunca o id sequencial', function () {
    $s = show(['comeca_em' => '2026-09-12 21:00']);

    expect(route('site.show', $s))->toEndWith('/agenda/show-bar-do-ze-setembro-2026')
        ->and(route('site.show', $s))->not->toContain('/agenda/'.$s->id);
});

it('dois shows no mesmo local no mesmo mês se separam pelo dia', function () {
    show(['comeca_em' => '2026-09-05 21:00']);
    $segundo = show(['comeca_em' => '2026-09-19 21:00']);

    expect($segundo->slug)->toBe('show-bar-do-ze-19-setembro-2026');
    $this->get(route('site.show', $segundo))->assertOk();
});

it('⚠️ o endereço acompanha a remarcação para outro mês', function () {
    $s = show(['comeca_em' => '2026-09-12 21:00']);
    $s->update(['comeca_em' => '2026-10-03 21:00']);

    expect($s->fresh()->slug)->toBe('show-bar-do-ze-outubro-2026');
});

it('⚠️ o endereço antigo por uuid continua abrindo, com 301 para o novo', function () {
    $s = show(['comeca_em' => '2026-09-12 21:00']);

    $this->get('/agenda/'.$s->uuid)
        ->assertStatus(301)
        ->assertRedirect(route('site.show', $s));
});

it('⚠️ o uuid de show privado não redireciona — o Location entregaria o nome do local', function () {
    $s = show(['tipo' => TipoShow::Particular]);

    $this->get('/agenda/'.$s->uuid)->assertNotFound();
    $this->get(route('site.show', $s))->assertNotFound();
});

it('devolve 404 no show que não vai para o site', function (array $atributos) {
    $s = show($atributos);

    $this->get(route('site.show', $s))->assertNotFound();
})->with([
    'rascunho' => [['status' => StatusShow::Rascunho]],
    'particular' => [['tipo' => TipoShow::Particular]],
    'não publicado' => [['publicado' => false]],
]);

it('mostra a galeria e o vídeo de um show que já aconteceu', function () {
    $s = show(['comeca_em' => now()->subWeek()]);

    Foto::create([
        'show_id' => $s->id, 'arquivo_path' => 'sementes/palco.webp',
        'legenda' => 'A casa cheia', 'credito' => 'Fulana', 'publicada' => true,
    ]);
    Video::create([
        'titulo' => 'Set de sábado', 'show_id' => $s->id, 'tipo' => TipoVideo::Youtube,
        'youtube_id' => 'dQw4w9WgXcQ', 'publicado' => true,
    ]);

    $this->get(route('site.show', $s))
        ->assertOk()
        ->assertSee('A casa cheia')
        ->assertSee('data-credito="Fulana"', false)
        ->assertSee('Set de sábado');
});

it('não publica foto nem vídeo despublicado', function () {
    $s = show(['comeca_em' => now()->subWeek()]);

    Foto::create(['show_id' => $s->id, 'arquivo_path' => 'x.webp', 'legenda' => 'Rejeitada', 'publicada' => false]);
    Video::create(['titulo' => 'Ainda editando', 'show_id' => $s->id, 'tipo' => TipoVideo::Youtube, 'youtube_id' => 'abc123', 'publicado' => false]);

    $this->get(route('site.show', $s))
        ->assertOk()
        ->assertDontSee('Rejeitada')
        ->assertDontSee('Ainda editando');
});

it('a agenda só oferece a página do show quando há o que mostrar', function () {
    $vazio = show(['comeca_em' => now()->subMonth()]);
    $cheio = show(['comeca_em' => now()->subWeek()]);
    Foto::create(['show_id' => $cheio->id, 'arquivo_path' => 'x.webp', 'publicada' => true]);

    $html = $this->get('/agenda')->assertOk()->getContent();

    expect($html)->toContain(route('site.show', $cheio))
        ->and($html)->not->toContain(route('site.show', $vazio));
});

it('o repertório atual sai do PRÓXIMO show quando ele tem setlist', function () {
    $passado = show(['comeca_em' => now()->subWeek()]);
    $futuro = show(['comeca_em' => now()->addDays(3)]);

    $antiga = Musica::create(['titulo' => 'Só no show velho', 'publicada' => true]);
    $nova = Musica::create(['titulo' => 'Vai tocar sábado', 'publicada' => true]);

    $passado->setlist()->attach($antiga->id, ['ordem' => 1]);
    $futuro->setlist()->attach($nova->id, ['ordem' => 1]);

    $this->get('/repertorio')
        ->assertOk()
        ->assertSee('O que vai tocar no próximo')
        ->assertSeeInOrder(['Repertório atual', 'Vai tocar sábado']);
});

it('sem próximo com setlist, o repertório atual cai no último show', function () {
    $passado = show(['comeca_em' => now()->subWeek()]);
    show(['comeca_em' => now()->addDays(3)]);

    $musica = Musica::create(['titulo' => 'Tocou no sábado', 'publicada' => true]);
    $passado->setlist()->attach($musica->id, ['ordem' => 1]);

    $this->get('/repertorio')
        ->assertOk()
        ->assertSee('O que tocou no último show')
        ->assertSee('Tocou no sábado');
});

it('sem setlist nenhum, o repertório mostra só o catálogo', function () {
    Musica::create(['titulo' => 'Uma do catálogo', 'publicada' => true]);

    $this->get('/repertorio')
        ->assertOk()
        ->assertSee('Repertório atual')
        ->assertDontSee('O que tocou no último show')
        ->assertDontSee('O que vai tocar no próximo')
        ->assertSee('Uma do catálogo');
});

it('a música com vídeo ganha o "ver a versão", que abre no modal', function () {
    Musica::create(['titulo' => 'Highway to Hell', 'artista' => 'AC/DC', 'youtube_id' => 'dQw4w9WgXcQ', 'publicada' => true]);

    $this->get('/repertorio')
        ->assertOk()
        ->assertSee('data-youtube="dQw4w9WgXcQ"', escape: false)
        ->assertSee('id="modal-video"', escape: false)

        ->assertDontSee('<iframe', escape: false);
});

it('extrai o id de qualquer formato de link do YouTube', function (string $entrada) {
    expect(Youtube::id($entrada))->toBe('dQw4w9WgXcQ');
})->with([
    'id puro' => ['dQw4w9WgXcQ'],
    'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
    'curto com rastreio' => ['https://youtu.be/dQw4w9WgXcQ?si=AbCdEf'],
    'com tempo' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s'],
    'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ'],
    'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ'],
]);

it('vazio continua vazio', function () {
    expect(Youtube::id(''))->toBeNull()
        ->and(Youtube::id(null))->toBeNull()
        ->and(Youtube::url(null))->toBeNull()
        ->and(Youtube::capa(null))->toBeNull();
});

it('a capa usa hqdefault, que existe para todo vídeo', function () {
    expect(Youtube::capa('dQw4w9WgXcQ'))->toBe('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg');
});

it('o embed é no domínio sem cookie', function () {
    expect(Youtube::embed('dQw4w9WgXcQ'))->toStartWith('https://www.youtube-nocookie.com/embed/');
});

it('⚠️ o "show seguinte" não repete o show que o topo já anuncia', function () {
    $passado = show(['comeca_em' => now()->subWeek(), 'status' => StatusShow::Realizado]);
    show(['comeca_em' => now()->addMonth()]);

    $this->get(route('site.show', $passado))
        ->assertOk()
        ->assertSee('Próximo show')
        ->assertDontSee('Show seguinte');
});

it('o "show seguinte" pula o do topo quando há outro depois dele', function () {
    $passado = show(['comeca_em' => now()->subWeek(), 'status' => StatusShow::Realizado]);
    show(['comeca_em' => now()->addWeek()]);
    $depois = show(['comeca_em' => now()->addMonths(2)]);

    $this->get(route('site.show', $passado))
        ->assertOk()
        ->assertSee('Show seguinte')
        ->assertSee(route('site.show', $depois), false);
});
