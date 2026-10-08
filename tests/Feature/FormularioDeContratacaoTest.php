<?php

declare(strict_types=1);

use App\Enums\OrigemContratacao;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Contratacao;

it('grava o pedido com a data e o IP do consentimento', function () {
    $this->post(route('site.contratar'), [
        'nome' => 'RH da empresa',
        'email' => 'rh@empresa.com.br',
        'tipo_evento' => 'empresa',
        'consentimento' => '1',
    ])->assertRedirect();

    $pedido = Contratacao::query()->latest('id')->first();

    expect($pedido->nome)->toBe('RH da empresa')
        ->and($pedido->origem)->toBe(OrigemContratacao::Site)
        ->and($pedido->temConsentimentoRegistrado())->toBeTrue()
        ->and($pedido->consentimento_ip)->not->toBeNull();
});

it('recusa envio sem consentimento', function () {
    $this->post(route('site.contratar'), [
        'nome' => 'Alguém',
        'telefone' => '11900000000',
        'tipo_evento' => 'bar',
    ])->assertSessionHasErrors('consentimento');

    expect(Contratacao::query()->count())->toBe(0);
});

it('exige ao menos um meio de resposta', function () {
    $this->post(route('site.contratar'), [
        'nome' => 'Alguém',
        'tipo_evento' => 'bar',
        'consentimento' => '1',
    ])->assertSessionHasErrors(['email', 'telefone']);
});

it('descarta envio de robô sem devolver erro que o ensine a tentar de novo', function () {
    $this->post(route('site.contratar'), [
        'nome' => 'Robô',
        'telefone' => '11900000000',
        'tipo_evento' => 'bar',
        'consentimento' => '1',
        'site' => 'http://spam.example',
    ])->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('sucesso');

    expect(Contratacao::query()->count())->toBe(0);
});

it('descarta envio feito em menos de 3 segundos, calado', function () {
    $this->post(route('site.contratar'), [
        'nome' => 'Robô apressado',
        'telefone' => '11900000000',
        'tipo_evento' => 'bar',
        'consentimento' => '1',
        'aberto_em' => time() - 1,
    ])->assertSessionHasNoErrors()->assertSessionHas('sucesso');

    expect(Contratacao::query()->count())->toBe(0);
});

it('grava o envio de quem levou o tempo de gente', function () {
    $this->post(route('site.contratar'), [
        'nome' => 'Gente',
        'telefone' => '11900000000',
        'tipo_evento' => 'bar',
        'consentimento' => '1',
        'aberto_em' => time() - 30,
    ])->assertSessionHasNoErrors();

    expect(Contratacao::query()->count())->toBe(1);
});

it('o formulário servido carrega a hora em que saiu do servidor', function () {
    $this->get(route('site.home'))
        ->assertOk()
        ->assertSee('name="aberto_em"', false);
});

test('em ambiente publicado o pedido exige captcha', function () {
    app()['env'] = 'production';
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $this->post(route('site.contratar'), [
        'nome' => 'RH da empresa',
        'email' => 'rh@empresa.com.br',
        'tipo_evento' => 'empresa',
        'consentimento' => '1',
    ])->assertSessionHasErrors('captcha_token');

    app()['env'] = 'testing';
});

test('em testing o pedido não exige captcha (fail-open de dev)', function () {
    $this->post(route('site.contratar'), [
        'nome' => 'RH da empresa',
        'email' => 'rh@empresa.com.br',
        'tipo_evento' => 'empresa',
        'consentimento' => '1',
    ])->assertSessionDoesntHaveErrors('captcha_token');
});

test('o token do captcha não vira dado do pedido', function () {
    $this->post(route('site.contratar'), [
        'nome' => 'RH da empresa',
        'email' => 'rh@empresa.com.br',
        'tipo_evento' => 'empresa',
        'consentimento' => '1',
        'captcha_token' => 'payload-qualquer',
    ])->assertSessionHasNoErrors();

    expect(Contratacao::query()->latest('id')->first()->nome)->toBe('RH da empresa');
});

test('recado com mais de dois links é recusado, e nada é gravado', function () {
    $antes = Contratacao::count();

    $this->from('/')->post(route('site.contratar'), [
        'nome' => 'Vendedor de Link',
        'email' => 'links@exemplo.com',
        'tipo_evento' => 'empresa',
        'mensagem' => 'veja http://a.example e https://b.example e www.c.example agora',
        'consentimento' => '1',
    ])->assertSessionHasErrors('mensagem');

    expect(Contratacao::count())->toBe($antes);
});
