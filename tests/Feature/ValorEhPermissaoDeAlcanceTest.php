<?php

declare(strict_types=1);

use App\Enums\OrigemContratacao;
use App\Enums\StatusContratacao;
use App\Enums\TipoEvento;
use App\Models\Contratacao;
use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;

beforeEach(function () {
    $this->seed(PerfisESeguranca::class);

    $this->pedido = Contratacao::create([
        'nome' => 'Dono do bar',
        'telefone' => '11900000000',
        'tipo_evento' => TipoEvento::Bar,
        'origem' => OrigemContratacao::Whatsapp,
        'status' => StatusContratacao::PropostaEnviada,
        'valor_proposto' => '1234.56',
    ]);
});

function conta(string $perfil): User
{
    $usuario = User::create([
        'name' => 'Conta '.$perfil,
        'email' => $perfil.'@valores.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);

    $usuario->syncRoles([$perfil]);

    return $usuario;
}

it('mostra o valor para a produção', function () {
    $this->actingAs(conta(Perfis::PRODUCAO))
        ->get(route('painel.contratacoes.show', $this->pedido))
        ->assertOk()
        ->assertSee('1234.56');
});

it('NÃO manda o valor no payload para a banda', function () {
    $this->actingAs(conta(Perfis::BANDA))
        ->get(route('painel.contratacoes.show', $this->pedido))
        ->assertOk()
        ->assertDontSee('1234.56');
});

it('não deixa quem não vê valor apagar o valor ao salvar o pedido', function () {
    $this->actingAs(conta(Perfis::BANDA))
        ->put(route('painel.contratacoes.update', $this->pedido), [
            'nome' => 'Dono do bar',
            'tipo_evento' => TipoEvento::Bar->value,
            'status' => StatusContratacao::PropostaEnviada->value,
        ])
        ->assertRedirect();

    expect($this->pedido->fresh()->valor_proposto)->toEqual('1234.56');
});
