<?php

declare(strict_types=1);

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Models\Local;
use App\Models\Show;
use App\Models\TipoGaleria;
use App\Models\User;
use App\Models\Video;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;

beforeEach(function () {
    $this->seed(PerfisESeguranca::class);

    $this->admin = User::create([
        'name' => 'Admin', 'email' => 'admin@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $this->admin->syncRoles([Perfis::ADMINISTRADOR]);
});

describe('locais', function () {
    it('abre a edição, salva e remove pelo uuid, que é o que a lista manda', function () {
        $local = Local::create(['nome' => 'Casa da Esquina']);

        $this->actingAs($this->admin)
            ->get("/painel/locais/{$local->uuid}/editar")
            ->assertOk();

        $this->actingAs($this->admin)
            ->put("/painel/locais/{$local->uuid}", ['nome' => 'Casa da Esquina Bar', 'ativa' => '1'])
            ->assertRedirect(route('painel.locais.index'))
            ->assertSessionHasNoErrors();

        expect($local->fresh()->nome)->toBe('Casa da Esquina Bar');

        $this->actingAs($this->admin)
            ->delete("/painel/locais/{$local->uuid}")
            ->assertRedirect(route('painel.locais.index'));

        expect(Local::query()->whereKey($local->id)->exists())->toBeFalse();
    });
});

describe('tipos de galeria', function () {
    it('salva e remove pelo id, que é o que a tela manda', function () {
        $tipo = TipoGaleria::create(['nome' => 'Festival', 'publicado' => true]);

        $this->actingAs($this->admin)
            ->put("/painel/tipos-galeria/{$tipo->id}", ['nome' => 'Festivais', 'publicado' => true])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect($tipo->fresh())->nome->toBe('Festivais')->slug->toBe('festival');

        $this->actingAs($this->admin)->delete("/painel/tipos-galeria/{$tipo->id}")->assertRedirect();

        expect(TipoGaleria::query()->whereKey($tipo->id)->exists())->toBeFalse();
    });

    it('não deixa remover o tipo de shows, que o código acha pelo slug', function () {
        $shows = TipoGaleria::create(['nome' => 'Shows', 'slug' => TipoGaleria::SLUG_DOS_SHOWS, 'publicado' => true]);

        $this->actingAs($this->admin)
            ->delete("/painel/tipos-galeria/{$shows->id}")
            ->assertRedirect()
            ->assertSessionHas('erro');

        expect(TipoGaleria::dosShows()?->id)->toBe($shows->id);
    });
});

describe('vídeo por link na página do show', function () {
    it('não diz "adicionado" quando o link já é de outra noite, e não o tira de lá', function () {
        $noite = fn (string $quando) => Show::create([
            'titulo' => "Noite {$quando}", 'comeca_em' => $quando,
            'status' => StatusShow::Realizado, 'tipo' => TipoShow::Publico, 'publicado' => true,
        ]);
        $primeira = $noite('2026-08-01 21:00');
        $segunda = $noite('2026-08-08 21:00');
        $link = ['titulo' => 'Titanium', 'modo' => 'link', 'link' => 'https://youtu.be/dQw4w9WgXcQ'];

        $this->actingAs($this->admin)
            ->post("/painel/shows/{$primeira->uuid}/videos", $link)
            ->assertSessionHas('sucesso');

        $this->actingAs($this->admin)
            ->post("/painel/shows/{$segunda->uuid}/videos", $link)
            ->assertSessionHas('erro')
            ->assertSessionMissing('sucesso');

        expect(Video::query()->count())->toBe(1)
            ->and(Video::query()->first()->show_id)->toBe($primeira->id);
    });
});

describe('relato de problema', function () {
    it('só aceita quem está autenticado', function () {
        $this->post('/reportar-erro', ['title' => 'spam'])->assertRedirect(route('login'));
    });

    it('avisa quem relatou quando o Central está desligado, em vez de agradecer', function () {
        config(['services.central.base_url' => null]);

        $this->actingAs($this->admin)
            ->post('/reportar-erro', ['title' => 'Botão não salva', 'severity' => 'high'])
            ->assertSessionHas('erro');
    });
});
