<?php

declare(strict_types=1);

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Models\Foto;
use App\Models\Local;
use App\Models\Show;

beforeEach(function () {
    $this->local = Local::create(['nome' => 'Bar do Zé', 'slug' => 'bar-do-ze', 'cidade' => 'São Paulo', 'uf' => 'SP']);
});

function showNaAgenda(array $atributos = []): Show
{
    return Show::create([
        'local_id' => test()->local->id,
        'comeca_em' => now()->subWeek(),
        'status' => StatusShow::Confirmado,
        'tipo' => TipoShow::Publico,
        'publicado' => true,
        ...$atributos,
    ]);
}

it('o que já aconteceu vira quadro, e o que vem continua linha', function () {
    showNaAgenda(['comeca_em' => now()->subWeek()]);
    showNaAgenda(['comeca_em' => now()->addWeek()]);

    $html = $this->get('/agenda')->assertOk()->getContent();

    expect($html)->toContain('class="quadros"')
        ->and($html)->toContain('class="shows"');
});

it('⚠️ o quadro continua sendo um link para a página do show', function () {
    $show = showNaAgenda(['observacoes_publicas' => 'Casa cheia, dois sets.']);

    $this->get('/agenda')
        ->assertOk()
        ->assertSee('href="'.route('site.show', $show).'"', false)
        ->assertDontSee('data-modal-show', false);
});

it('quadro sem nada para mostrar NÃO vira link', function () {
    showNaAgenda();

    $html = $this->get('/agenda')->assertOk()->getContent();

    expect($html)->toContain('quadro--sem-ficha')

        ->and($html)->not->toContain('href="'.route('site.show', Show::first()).'"');
});

it('o quadro com foto ganha o selo "ver como foi"', function () {
    $show = showNaAgenda();
    Foto::create(['show_id' => $show->id, 'arquivo_path' => 'fotos/uma.webp', 'publicada' => true]);

    $this->get('/agenda')->assertOk()->assertSee('ver como foi');
});

it('"quero marcar a minha" abre o formulário NA agenda', function () {
    showNaAgenda(['comeca_em' => now()->addWeek()]);

    $this->get('/agenda')
        ->assertOk()

        ->assertSee('id="modal-contratar"', false)
        ->assertSee('data-abre-modal="modal-contratar"', false)
        ->assertSee(route('site.contratar'), false)

        ->assertSee('name="consentimento"', false);
});

it('⚠️ sem JavaScript, "quero marcar a minha" ainda leva a algum lugar', function () {
    $this->get('/agenda')
        ->assertOk()
        ->assertSee('href="'.route('site.home').'#contratar"', false);
});

it('o formulário do modal é o MESMO da home, não uma cópia', function () {
    $daHome = $this->get('/')->assertOk()->getContent();
    $daAgenda = $this->get('/agenda')->assertOk()->getContent();

    foreach (['name="nome"', 'name="tipo_evento"', 'name="consentimento"', 'name="site"'] as $campo) {
        expect($daHome)->toContain($campo)
            ->and($daAgenda)->toContain($campo);
    }
});

it('o menu tem um jeito explícito de voltar para o início', function () {
    $this->get('/agenda')
        ->assertOk()
        ->assertSee('>Início</a>', false);
});

it('o endereço da linha quebra em rua e cidade', function () {
    $this->local->update(['endereco' => 'Rua das Flores, 100', 'bairro' => 'Tatuapé']);
    showNaAgenda(['comeca_em' => now()->addWeek()]);

    $this->get('/agenda')
        ->assertOk()
        ->assertSee('Rua das Flores, 100 · Tatuapé')
        ->assertSee('São Paulo · SP');
});

it('⚠️ endereço escrito à mão NÃO é partido', function () {
    $show = showNaAgenda(['comeca_em' => now()->addWeek(), 'endereco_livre' => 'Sítio do Vô, km 4 da estrada velha']);

    expect($show->logradouro())->toBe('Sítio do Vô, km 4 da estrada velha')
        ->and($show->praca())->toBeNull();
});
