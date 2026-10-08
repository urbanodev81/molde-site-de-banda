<?php

declare(strict_types=1);

use App\Enums\TipoEvento;
use App\Http\Middleware\SoComSiteAberto;
use App\Models\Contratacao;
use App\Models\User;
use App\Support\ConfiguracaoDoSite;
use App\Support\Perfis;
use App\Support\SitePublicado;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Support\Facades\Route;

function fecharOSite(): void
{
    ConfiguracaoDoSite::gravar([SitePublicado::CHAVE => false]);
}

function alguemDaCasa(): User
{
    test()->seed(PerfisESeguranca::class);

    $usuario = User::create([
        'name' => 'Da casa',
        'email' => 'casa@teste.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);

    $usuario->syncRoles([Perfis::ADMINISTRADOR]);

    return $usuario;
}

it('nasce fechado quando o ambiente manda, sem ninguém ter gravado a chave', function () {
    config(['site.publicado_por_padrao' => false]);
    ConfiguracaoDoSite::esquecer();

    expect(SitePublicado::aberto())->toBeFalse();

    $this->get('/')->assertOk()->assertSee('id="tit-embreve"', escape: false);
});

it('a chave gravada manda no padrão do ambiente, nos dois sentidos', function () {
    config(['site.publicado_por_padrao' => false]);
    ConfiguracaoDoSite::gravar([SitePublicado::CHAVE => true]);
    expect(SitePublicado::aberto())->toBeTrue();

    config(['site.publicado_por_padrao' => true]);
    ConfiguracaoDoSite::gravar([SitePublicado::CHAVE => false]);
    expect(SitePublicado::aberto())->toBeFalse();
});

it('mostra a "Em breve" na raiz, com o WhatsApp e o formulário, e sem o conteúdo do site', function () {
    fecharOSite();

    $this->get('/')
        ->assertOk()
        ->assertSee('id="tit-embreve"', escape: false)
        ->assertSee('https://wa.me/', escape: false)
        ->assertSee('action="'.route('site.contratar').'"', escape: false)
        ->assertSee('name="consentimento"', escape: false)
        ->assertSee('noindex', escape: false)

        ->assertDontSee('id="agenda"', escape: false)
        ->assertDontSee(route('site.repertorio'));
});

it('devolve à raiz toda outra página do site, sitemap e llms inclusive', function (string $caminho) {
    fecharOSite();

    $this->get($caminho)->assertRedirect(route('site.home'));
})->with(['/a-banda', '/agenda', '/repertorio', '/galeria', '/imprensa', '/sitemap.xml', '/llms.txt']);

it('nenhuma rota GET do site fica fora da página de espera sem estar na lista', function () {
    $abertas = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($rota) => str_starts_with((string) $rota->getName(), 'site.') && in_array('GET', $rota->methods(), true))
        ->reject(fn ($rota) => in_array(SoComSiteAberto::class, $rota->gatherMiddleware(), true))
        ->map->getName()
        ->values()
        ->all();

    expect($abertas)->toBe(['site.privacidade']);
});

it('mantém a política de privacidade aberta, sem o menu do site', function () {
    fecharOSite();

    $this->get('/privacidade')
        ->assertOk()
        ->assertSee('Política de privacidade')
        ->assertDontSee(route('site.repertorio'));
});

it('quem está logado vê o site inteiro, com o aviso de prévia', function () {
    fecharOSite();

    $this->actingAs(alguemDaCasa())
        ->get('/')
        ->assertOk()
        ->assertSee('id="agenda"', escape: false)
        ->assertSee('O site está fechado');

    $this->get('/repertorio')->assertOk();
});

it('aberto, não há aviso de prévia nem página de espera', function () {
    $this->get('/')->assertOk()->assertDontSee('O site está fechado')->assertDontSee('id="tit-embreve"', escape: false);
});

it('o formulário da "Em breve" vira pedido no painel', function () {
    fecharOSite();

    $this->from('/')->post('/contratar', [
        'nome' => 'Marina',
        'telefone' => '11900000000',
        'tipo_evento' => TipoEvento::cases()[0]->value,
        'consentimento' => '1',
        'aberto_em' => time() - 30,
    ])->assertRedirect();

    expect(Contratacao::query()->where('nome', 'Marina')->exists())->toBeTrue();

    $this->get('/')->assertSee('id="tit-embreve"', escape: false);
});

it('o painel abre e fecha o site pela configuração', function () {
    $admin = alguemDaCasa();

    $this->actingAs($admin)
        ->put(route('painel.configuracoes.update'), ['valores' => [SitePublicado::CHAVE => false]])
        ->assertSessionHasNoErrors();

    expect(SitePublicado::aberto())->toBeFalse();

    $this->actingAs($admin)
        ->put(route('painel.configuracoes.update'), ['valores' => [SitePublicado::CHAVE => true]])
        ->assertSessionHasNoErrors();

    expect(SitePublicado::aberto())->toBeTrue();
});

it('a tela de configuração grava de verdade uma chave com ponto no nome', function () {
    $this->actingAs(alguemDaCasa())
        ->put(route('painel.configuracoes.update'), ['valores' => ['contato.whatsapp' => '5511900000000']])
        ->assertSessionHasNoErrors();

    expect(ConfiguracaoDoSite::valor('contato.whatsapp'))->toBe('5511900000000');

    $this->put(route('painel.configuracoes.update'), ['valores' => ['contato.email' => 'isto-nao-e-email']])
        ->assertSessionHasErrors();
});
