<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;

beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
});

function usuarioCom(string $perfil): User
{
    $usuario = User::create([
        'name' => 'Teste '.$perfil,
        'email' => $perfil.'@teste.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);

    $usuario->syncRoles([$perfil]);

    return $usuario;
}

it('exige autenticação em todo o painel', function (string $rota) {
    $this->get($rota)->assertRedirect(route('login'));
})->with([
    '/painel',
    '/painel/shows',
    '/painel/contratacoes',
    '/painel/usuarios',
    '/painel/configuracoes',
    '/painel/auditoria',
]);

it('deixa a banda cuidar do conteúdo do site', function (string $rota) {
    $this->actingAs(usuarioCom(Perfis::BANDA))->get($rota)->assertOk();
})->with([
    '/painel',
    '/painel/shows',
    '/painel/integrantes',
    '/painel/videos',
    '/painel/musicas',
    '/painel/fotos',

    '/painel/tipos-galeria',

    '/painel/participacoes',
    '/painel/publicacoes',
    '/painel/contratacoes',
]);

it('não deixa a banda mexer em conta de acesso nem na configuração do site', function (string $rota) {
    $this->actingAs(usuarioCom(Perfis::BANDA))->get($rota)->assertForbidden();
})->with([
    '/painel/usuarios',
    '/painel/configuracoes',
    '/painel/auditoria',
]);

it('não deixa a produção mexer no conteúdo de marca', function (string $rota) {
    $this->actingAs(usuarioCom(Perfis::PRODUCAO))->get($rota)->assertForbidden();
})->with([
    '/painel/integrantes',
    '/painel/participacoes',
    '/painel/videos',
    '/painel/publicacoes',

    '/painel/tipos-galeria',
    '/painel/usuarios',
]);

it('deixa a produção cuidar do tipo de espaço, que vive sob locais', function () {
    $this->actingAs(usuarioCom(Perfis::PRODUCAO))->get('/painel/tipos-espaco')->assertOk();
    $this->actingAs(usuarioCom(Perfis::BANDA))->get('/painel/tipos-espaco')->assertOk();
});

it('abre tudo para o administrador', function (string $rota) {
    $this->actingAs(usuarioCom(Perfis::ADMINISTRADOR))->get($rota)->assertOk();
})->with([
    '/painel/shows',
    '/painel/integrantes',
    '/painel/usuarios',
    '/painel/configuracoes',
    '/painel/auditoria',
]);
