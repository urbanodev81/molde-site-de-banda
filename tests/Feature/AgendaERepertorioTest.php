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
use App\Support\ConfiguracaoDoSite;

function showDaRodada(array $atributos = []): Show
{
    return Show::create([
        'local_id' => Local::firstOrCreate(['slug' => 'bar-da-rodada'], ['nome' => 'Bar da Rodada', 'cidade' => 'São Paulo'])->id,
        'comeca_em' => now()->subWeek(),
        'status' => StatusShow::Realizado,
        'tipo' => TipoShow::Publico,
        'publicado' => true,
        ...$atributos,
    ]);
}

it('não desenha vaga vazia de vídeo no site', function () {
    Video::create([
        'titulo' => 'O único que existe', 'tipo' => TipoVideo::Youtube,
        'youtube_id' => 'dQw4w9WgXcQ', 'publicado' => true,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('O único que existe')
        ->assertDontSee('Vaga 2')
        ->assertDontSee('class="vaga"', false);
});

it('a tarja rolante sai do repertório quando ninguém a escreve', function () {
    Musica::create(['titulo' => 'Uma', 'estilo' => 'punk rock', 'publicada' => true]);

    $this->get('/')->assertOk()->assertSee('punk rock');
});

it('⚠️ o que a banda escreve no painel manda na tarja', function () {
    Musica::create(['titulo' => 'Uma', 'estilo' => 'punk rock', 'publicada' => true]);
    ConfiguracaoDoSite::gravar(['banda.faixa' => 'Rock de bar, Voz e violão, Baile']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Rock de bar')
        ->assertSee('Voz e violão')

        ->assertDontSee('punk rock');
});

it('diz na cara que o show já aconteceu', function () {
    showDaRodada();

    $this->get('/')->assertOk()->assertSee('já aconteceu');
});

it('não diz "já aconteceu" de um show que ainda vem', function () {
    showDaRodada(['comeca_em' => now()->addMonth(), 'status' => StatusShow::Confirmado]);

    $this->get('/agenda')->assertOk()->assertDontSee('já aconteceu');
});

it('⚠️ o item da agenda leva à página do show, na home', function () {
    $show = showDaRodada();
    Foto::create(['show_id' => $show->id, 'arquivo_path' => 'fotos/uma.webp', 'publicada' => true]);

    $this->get('/')
        ->assertOk()
        ->assertSee(route('site.show', $show), false)
        ->assertSee('Ver como foi');
});

it('o título da linha é link mesmo sem foto nenhuma', function () {
    $show = showDaRodada(['comeca_em' => now()->addMonth(), 'status' => StatusShow::Confirmado]);

    $this->get('/agenda')
        ->assertOk()
        ->assertSee(route('site.show', $show), false)
        ->assertDontSee('Ver como foi');
});

it('⚠️ a home não anuncia agenda vazia tendo um próximo show marcado', function () {
    showDaRodada();
    showDaRodada(['comeca_em' => now()->addMonth(), 'status' => StatusShow::Confirmado]);

    $this->get('/')->assertOk()->assertDontSee('A próxima data está sendo fechada');
});

it('mostra o repertório atual pelos destaques quando não há setlist', function () {
    Musica::create(['titulo' => 'A que não falta', 'publicada' => true, 'destaque' => true]);
    Musica::create(['titulo' => 'Uma qualquer', 'publicada' => true]);

    $this->get('/repertorio')
        ->assertOk()
        ->assertSee('Repertório atual')
        ->assertSee('As que não faltam')
        ->assertSee('A que não falta');
});

it('⚠️ "outras que a gente já tocou" não repete as do atual', function () {
    Musica::create(['titulo' => 'A destacada', 'publicada' => true, 'destaque' => true]);
    Musica::create(['titulo' => 'A outra', 'publicada' => true]);

    $html = $this->get('/repertorio')->assertOk()->getContent();

    expect(substr_count($html, 'A destacada'))->toBe(1)
        ->and($html)->toContain('Outras que a gente já tocou');
});

it('não mostra a seção das outras quando o catálogo cabe todo no atual', function () {
    Musica::create(['titulo' => 'A única', 'publicada' => true, 'destaque' => true]);

    $this->get('/repertorio')->assertOk()->assertDontSee('Outras que a gente já tocou');
});
