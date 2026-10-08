<?php

declare(strict_types=1);

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Models\Contratacao;
use App\Models\Integrante;
use App\Models\Local;
use App\Models\Material;
use App\Models\MaterialVinculo;
use App\Models\PoliticaRetencao;
use App\Models\Show;
use App\Models\User;
use App\Support\Perfis;
use App\Support\VinculosDeMaterial;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    $this->seed(PerfisESeguranca::class);

    $conta = fn (string $nome, string $perfil) => tap(User::create([
        'name' => $nome, 'email' => str($nome)->slug().'@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]), fn (User $u) => $u->syncRoles([$perfil]));

    $this->admin = $conta('Admin', Perfis::ADMINISTRADOR);
    $this->banda = $conta('Banda', Perfis::BANDA);
    $this->semPedidos = tap($conta('Só material', Perfis::BANDA), fn (User $u) => $u->syncRoles([]))
        ->givePermissionTo(['materiais.ver', 'materiais.gerenciar', 'shows.ver']);

    $this->show = Show::create([
        'titulo' => 'Noite de teste', 'comeca_em' => now()->addWeek()->setTime(21, 0),
        'status' => StatusShow::Confirmado, 'tipo' => TipoShow::Publico, 'publicado' => true,
    ]);
});

function material(string $titulo, bool $publico = false): Material
{
    $caminho = 'materiais/'.str($titulo)->slug().'.pdf';
    Storage::disk('public')->put($caminho, 'conteudo de '.$titulo);

    return Material::create(['titulo' => $titulo, 'arquivo_path' => $caminho, 'arquivo_nome_original' => $titulo.'.pdf', 'publico' => $publico]);
}

it('liga um material a show, local e integrante, sem duplicar', function () {
    $rider = material('Rider da noite');
    $local = Local::create(['nome' => 'Bar do Teste']);
    $integrante = Integrante::create(['nome' => 'Ana', 'ativa' => true]);

    foreach (["show:{$this->show->id}", "local:{$local->id}", "integrante:{$integrante->id}", "show:{$this->show->id}"] as $alvo) {
        $this->actingAs($this->admin)->post(route('painel.materiais.vinculos.store', $rider), ['alvo' => $alvo])
            ->assertRedirect()->assertSessionHasNoErrors();
    }

    expect(MaterialVinculo::count())->toBe(3)
        ->and($this->show->materiais()->pluck('titulo')->all())->toBe(['Rider da noite'])
        ->and($local->materiais()->count())->toBe(1)
        ->and($integrante->materiais()->count())->toBe(1)

        ->and($rider->fresh()->privado)->toBeFalse();
});

it('o vínculo é por tipo: o show 1 não lê o material do local 1', function () {
    $local = Local::create(['nome' => 'Bar do Teste']);
    VinculosDeMaterial::ligar(material('Mapa de palco'), $local);

    $show = Show::query()->whereKey($local->id)->first() ?? $this->show;

    expect($show->materiais()->count())->toBe(0)->and($local->materiais()->count())->toBe(1);
});

it('a página do show mostra só o material público ligado a ele', function () {
    VinculosDeMaterial::ligar(material('Cartaz em alta', publico: true), $this->show);
    VinculosDeMaterial::ligar(material('Rider interno', publico: false), $this->show);
    material('Logo geral', publico: true);

    $this->get(route('site.show', $this->show))->assertOk()
        ->assertSee('Materiais desta noite')
        ->assertSee('Cartaz em alta')
        ->assertDontSee('Rider interno')
        ->assertDontSee('Logo geral');
});

it('ligar a pedido tira o arquivo do disco público e fecha o material para o site', function () {
    $pedido = Contratacao::create(['nome' => 'Fulano de Tal', 'email' => 'fulano@teste.local']);
    $contrato = material('Contrato assinado', publico: true);
    $caminho = $contrato->arquivo_path;

    $this->actingAs($this->admin)->post(route('painel.materiais.vinculos.store', $contrato), ['alvo' => "contratacao:{$pedido->id}"])
        ->assertSessionHasNoErrors();

    $contrato->refresh();
    expect($contrato->privado)->toBeTrue()->and($contrato->publico)->toBeFalse();
    Storage::disk('public')->assertMissing($caminho);
    Storage::disk('local')->assertExists($contrato->arquivo_path);

    $this->actingAs($this->admin)->post(route('painel.materiais.lote'), ['acao' => 'ligar', 'ids' => [(string) $contrato->uuid]]);
    $this->actingAs($this->admin)->put(route('painel.materiais.update', $contrato), ['titulo' => 'Contrato assinado', 'tipo' => 'contrato', 'publico' => true]);
    expect($contrato->fresh()->publico)->toBeFalse();

    $contrato->forceFill(['publico' => true])->save();
    expect(Material::query()->publicos()->count())->toBe(0);
    auth()->logout();
    $this->get('/imprensa')->assertDontSee('Contrato assinado');
});

it('o arquivo privado só sai com login e com permissão de ver pedido', function () {
    $pedido = Contratacao::create(['nome' => 'Fulano de Tal', 'email' => 'fulano@teste.local']);
    $contrato = material('Contrato assinado');
    VinculosDeMaterial::ligar($contrato, $pedido);

    $this->get(route('painel.materiais.baixar', $contrato))->assertRedirect(route('login'));
    $this->actingAs($this->semPedidos)->get(route('painel.materiais.baixar', $contrato))->assertNotFound();
    $this->actingAs($this->admin)->get(route('painel.materiais.baixar', $contrato))->assertOk()
        ->assertDownload('Contrato assinado.pdf');
});

it('quem não vê pedido não recebe o pedido, o vínculo nem o material dele', function () {
    $pedido = Contratacao::create(['nome' => 'Fulano de Tal', 'email' => 'fulano@teste.local']);
    VinculosDeMaterial::ligar(material('Contrato assinado'), $pedido);
    VinculosDeMaterial::ligar($rider = material('Rider da noite'), $this->show);

    $this->actingAs($this->semPedidos)->get(route('painel.materiais.index'))->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->has('materiais', 1)
            ->where('materiais.0.titulo', 'Rider da noite')
            ->has('materiais.0.vinculos', 1)
            ->where('alvos', fn ($alvos) => collect($alvos)->every(fn ($a) => str_starts_with($a['valor'], 'show:'))));

    $this->actingAs($this->semPedidos)
        ->post(route('painel.materiais.vinculos.store', $rider), ['alvo' => "contratacao:{$pedido->id}"])
        ->assertForbidden();

    expect($rider->fresh()->privado)->toBeFalse();
});

it('desligar não apaga o material, e o vínculo de outro material não é alcançado', function () {
    $rider = material('Rider da noite');
    $outro = material('Outro');
    VinculosDeMaterial::ligar($rider, $this->show);
    $vinculo = MaterialVinculo::firstOrFail();

    $this->actingAs($this->admin)->delete(route('painel.materiais.vinculos.destroy', [$outro, $vinculo]))->assertNotFound();
    $this->actingAs($this->admin)->delete(route('painel.materiais.vinculos.destroy', [$rider, $vinculo]))->assertRedirect();

    expect(MaterialVinculo::count())->toBe(0)->and($rider->fresh())->not->toBeNull();
});

it('a tela do show e a do pedido listam os materiais ligados', function () {
    $pedido = Contratacao::create(['nome' => 'Fulano de Tal', 'email' => 'fulano@teste.local']);
    VinculosDeMaterial::ligar(material('Rider da noite'), $this->show);
    VinculosDeMaterial::ligar(material('Contrato assinado'), $pedido);

    $this->actingAs($this->admin)->get(route('painel.shows.show', $this->show))->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p->has('materiais', 1)->where('materiais.0.titulo', 'Rider da noite'));

    $this->actingAs($this->admin)->get(route('painel.contratacoes.show', $pedido))->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p->has('materiais', 1)
            ->where('materiais.0.privado', true)
            ->where('materiais.0.url', route('painel.materiais.baixar', $pedido->materiais()->first())));
});

it('o expurgo da LGPD leva o material que só existia pelo pedido', function () {
    PoliticaRetencao::query()->updateOrCreate(['recurso' => 'contratacao'], ['meses' => 12, 'nunca_expurgar' => false, 'justificativa' => 'teste']);

    $velho = Contratacao::create(['nome' => 'Pedido antigo', 'email' => 'velho@teste.local']);
    $velho->forceFill(['created_at' => now()->subMonths(30)])->save();
    $novo = Contratacao::create(['nome' => 'Pedido novo', 'email' => 'novo@teste.local']);

    VinculosDeMaterial::ligar($soDoVelho = material('Contrato antigo'), $velho);
    VinculosDeMaterial::ligar($dosDois = material('Modelo usado nos dois'), $velho);
    VinculosDeMaterial::ligar($dosDois, $novo);
    $arquivoDoVelho = $soDoVelho->fresh()->arquivo_path;

    $this->artisan('lgpd:expurgo', ['--dry-run' => true])->assertSuccessful();
    expect(Material::count())->toBe(2);

    $this->artisan('lgpd:expurgo')->assertSuccessful();

    expect(Contratacao::withTrashed()->whereKey($velho->id)->exists())->toBeFalse()
        ->and(Material::withTrashed()->whereKey($soDoVelho->id)->exists())->toBeFalse()
        ->and(Material::whereKey($dosDois->id)->exists())->toBeTrue()
        ->and(MaterialVinculo::where('alvo_tipo', 'contratacao')->pluck('alvo_id')->all())->toBe([$novo->id]);
    Storage::disk('local')->assertMissing($arquivoDoVelho);
    Storage::disk('local')->assertExists($dosDois->fresh()->arquivo_path);
});
