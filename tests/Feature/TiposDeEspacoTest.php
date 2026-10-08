<?php

declare(strict_types=1);

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Models\Foto;
use App\Models\Local;
use App\Models\Show;
use App\Models\TipoEspaco;
use App\Models\TipoGaleria;
use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(PerfisESeguranca::class);

    $this->admin = User::create([
        'name' => 'Admin', 'email' => 'admin@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $this->admin->syncRoles([Perfis::ADMINISTRADOR]);
});

function showNo(Local $local): Show
{
    return Show::create([
        'titulo' => 'Noite de teste', 'local_id' => $local->id, 'comeca_em' => now()->addWeek()->setTime(21, 0),
        'status' => StatusShow::Confirmado, 'tipo' => TipoShow::Publico, 'publicado' => true,
    ]);
}

it('a migration entrega os tipos de espaço, e cada classe só vê o seu grupo', function () {
    $galeria = TipoGaleria::create(['nome' => 'Ensaio']);

    expect(TipoEspaco::query()->pluck('nome')->all())->toContain('Bar', 'Casa de show', 'Teatro')
        ->and(TipoEspaco::query()->whereKey($galeria->id)->exists())->toBeFalse()
        ->and(TipoGaleria::query()->where('nome', 'Bar')->exists())->toBeFalse()
        ->and(DB::table('tipos')->where('id', $galeria->id)->value('grupo'))->toBe('galeria');
});

it('o mesmo nome cabe nos dois grupos, com o mesmo slug', function () {
    $this->actingAs($this->admin)->post(route('painel.tipos-galeria.store'), ['nome' => 'Festival', 'publicado' => true])
        ->assertSessionHasNoErrors();

    expect(TipoGaleria::where('slug', 'festival')->exists())->toBeTrue()
        ->and(TipoEspaco::where('slug', 'festival')->exists())->toBeTrue();

    $this->actingAs($this->admin)->post(route('painel.tipos-espaco.store'), ['nome' => 'Festival', 'publicado' => true])
        ->assertSessionHasErrors('nome');
});

it('local não aceita tipo da galeria, nem tipo arquivado', function () {
    $aba = TipoGaleria::create(['nome' => 'Ensaio']);
    $arquivado = TipoEspaco::create(['nome' => 'Galpão']);
    $arquivado->delete();

    foreach ([$aba->id, $arquivado->id] as $id) {
        $this->actingAs($this->admin)->post(route('painel.locais.store'), ['nome' => 'Bar do Teste', 'ativa' => true, 'tipo_id' => $id])
            ->assertSessionHasErrors('tipo_id');
    }

    $bar = TipoEspaco::where('slug', 'bar')->firstOrFail();
    $this->actingAs($this->admin)->post(route('painel.locais.store'), ['nome' => 'Bar do Teste', 'ativa' => true, 'tipo_id' => $bar->id])
        ->assertSessionHasNoErrors();

    expect(Local::where('nome', 'Bar do Teste')->firstOrFail()->tipo->nome)->toBe('Bar');
});

it('foto não aceita tipo de espaço como aba', function () {
    $foto = Foto::create(['arquivo_path' => 'fotos/a.jpg', 'publicada' => true]);
    $bar = TipoEspaco::where('slug', 'bar')->firstOrFail();

    $this->actingAs($this->admin)->put(route('painel.fotos.update', $foto), ['tipo_galeria_id' => $bar->id])
        ->assertSessionHasErrors('tipo_galeria_id');
});

it('o site mostra o tipo ao lado do endereço, e só o publicado', function () {
    $teatro = TipoEspaco::where('slug', 'teatro')->firstOrFail();
    $local = Local::create(['nome' => 'Sala Azul', 'endereco' => 'Rua das Flores, 10', 'cidade' => 'São Paulo', 'uf' => 'SP', 'tipo_id' => $teatro->id]);
    $show = showNo($local);

    $this->get(route('site.show', $show))->assertOk()->assertSee('Teatro · Rua das Flores, 10');
    $this->get('/agenda')->assertOk()->assertSee('Teatro · Rua das Flores, 10');

    $teatro->update(['publicado' => false]);
    $this->get(route('site.show', $show))->assertDontSee('Teatro ·')->assertSee('Rua das Flores, 10');

    $teatro->update(['publicado' => true]);
    $teatro->delete();
    $this->get(route('site.show', $show))->assertDontSee('Teatro ·')->assertSee('Rua das Flores, 10');
});

it('arquivar o tipo não mexe no local', function () {
    $bar = TipoEspaco::where('slug', 'bar')->firstOrFail();
    $local = Local::create(['nome' => 'Bar do Teste', 'tipo_id' => $bar->id]);

    $this->actingAs($this->admin)->delete(route('painel.tipos-espaco.destroy', $bar->id))->assertRedirect();

    expect($local->fresh())->not->toBeNull()
        ->and($local->fresh()->tipo)->toBeNull()
        ->and(TipoEspaco::onlyTrashed()->whereKey($bar->id)->exists())->toBeTrue();
});

it('a rota de um grupo não alcança tipo do outro', function () {
    $aba = TipoGaleria::create(['nome' => 'Ensaio']);

    $this->actingAs($this->admin)->delete(route('painel.tipos-espaco.destroy', $aba->id))->assertNotFound();
    $this->actingAs($this->admin)->post(route('painel.tipos-espaco.lote'), ['acao' => 'arquivar', 'ids' => [$aba->id]]);

    expect($aba->fresh()->trashed())->toBeFalse();
});
