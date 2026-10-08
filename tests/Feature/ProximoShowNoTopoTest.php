<?php

declare(strict_types=1);

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Models\Local;
use App\Models\Show;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();

    $this->local = Local::create(['nome' => 'Bar do Zé', 'slug' => 'bar-do-ze']);
});

function proximoShow(array $atributos = []): Show
{
    return Show::create([
        'local_id' => test()->local->id,
        'comeca_em' => now()->addDays(3),
        'status' => StatusShow::Confirmado,
        'tipo' => TipoShow::Publico,
        'publicado' => true,
        ...$atributos,
    ]);
}

it('mostra a data do próximo show no topo da home', function () {
    $show = proximoShow(['comeca_em' => now()->addDays(3)->setTime(21, 0)]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Próximo show')
        ->assertSee($show->comeca_em->format('d/m'))

        ->assertSee('href="#proximo-show"', false);
});

it('a seção que a âncora promete EXISTE na home', function () {
    proximoShow();

    $this->get('/')->assertOk()->assertSee('id="proximo-show"', false);
});

it('nas outras páginas a âncora é ABSOLUTA — senão o link não sai do lugar', function () {
    proximoShow();

    foreach (['/agenda', '/repertorio', '/imprensa', '/privacidade'] as $pagina) {
        $this->get($pagina)
            ->assertOk()
            ->assertSee('Próximo show')
            ->assertSee('href="'.route('site.home').'#proximo-show"', false);
    }
});

it('o topo não tem mais o quadradinho "agenda completa"', function () {
    proximoShow();

    $this->get('/agenda')
        ->assertOk()
        ->assertSee('Próximo show')
        ->assertDontSee('agenda completa')
        ->assertSee('href="'.route('site.home').'#agenda"', false);
});

it('o menu não repete Vídeos nem Contratar', function () {
    $this->get('/repertorio')
        ->assertOk()
        ->assertDontSee('topo__link" href="'.route('site.home').'#videos"', false)
        ->assertDontSee('topo__link" href="'.route('site.home').'#contratar"', false)
        ->assertDontSee('</span> Vídeos</a>', false)
        ->assertDontSee('</span> Contratar</a>', false)
        ->assertSee(route('site.galeria'), false);
});

it('⚠️ some quando não há show futuro publicado', function () {
    $this->get('/')->assertOk()->assertDontSee('topo__proximo', false);

    proximoShow(['comeca_em' => now()->subDay()]);
    proximoShow(['publicado' => false]);
    Cache::flush();

    $this->get('/')->assertOk()->assertDontSee('topo__proximo', false);
});

it('o menu segue a ordem pedida, com Contato por último — no topo e na gaveta', function () {
    $html = $this->get('/repertorio')->assertOk()->getContent();

    foreach (['topo__link', 'gaveta__link'] as $classe) {
        preg_match_all('/class="'.$classe.'"[^>]*>(?:<span>\d+<\/span>)?\s*([^<]+)<\/a>/u', $html, $m);

        expect(array_map('trim', $m[1]))
            ->toBe(['Início', 'A banda', 'Agenda', 'Galeria', 'Repertório', 'Dúvidas', 'Contato']);
    }
});

it('⚠️ fora da home, Contato abre o modal — e o link continua indo à seção sem JavaScript', function () {
    $html = $this->get('/repertorio')->assertOk()->getContent();

    expect($html)
        ->toContain('href="'.route('site.home').'#contato"')
        ->toContain('data-abre-modal="modal-contato"')
        ->toContain('<dialog class="modal modal--contato" id="modal-contato"');
});

it('⚠️ na home, Contato desce até a seção e não existe modal duplicado', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('href="#contato"')
        ->toContain('id="tit-contato"')
        ->not->toContain('data-abre-modal="modal-contato"')
        ->not->toContain('id="modal-contato"');
});
