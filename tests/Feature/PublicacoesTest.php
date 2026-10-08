<?php

declare(strict_types=1);

use App\Models\Publicacao;
use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(PerfisESeguranca::class);

    $this->banda = User::create([
        'name' => 'Banda', 'email' => 'banda@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $this->banda->syncRoles([Perfis::BANDA]);
});

function materia(array $dados = []): array
{
    return [
        'tipo' => 'entrevista', 'titulo' => 'Rock no bairro', 'veiculo' => 'Rádio Teste',
        'saiu_em' => now()->subDay()->format('Y-m-d'), 'link' => 'https://radio.teste/entrevista',
        'resumo' => 'A banda contou como monta o repertório.', 'publicada' => true, 'destaque' => false,
        ...$dados,
    ];
}

it('o site mostra só a publicada, e a arquivada some', function () {
    Publicacao::create(materia(['titulo' => 'Saiu no jornal']));
    Publicacao::create(materia(['titulo' => 'Ainda em rascunho', 'publicada' => false]));
    Publicacao::create(materia(['titulo' => 'Matéria antiga']))->delete();

    foreach (['/imprensa', '/llms.txt'] as $pagina) {
        $this->get($pagina)->assertOk()
            ->assertSee('Saiu no jornal')
            ->assertDontSee('Ainda em rascunho')
            ->assertDontSee('Matéria antiga');
    }
});

it('sem publicação no ar a página não desenha a seção vazia', function () {
    Publicacao::create(materia(['publicada' => false]));

    $this->get('/imprensa')->assertOk()->assertDontSee('Na mídia');
    $this->get('/llms.txt')->assertOk()->assertDontSee('Na mídia');
});

it('o link sai como endereço externo, em nova aba e sem referência', function () {
    Publicacao::create(materia());

    $this->get('/imprensa')
        ->assertSee('href="https://radio.teste/entrevista" target="_blank" rel="noopener noreferrer"', escape: false);
});

it('destaque vem primeiro, depois a mais recente', function () {
    Publicacao::create(materia(['titulo' => 'Recente', 'saiu_em' => now()->subDay()]));
    Publicacao::create(materia(['titulo' => 'Em destaque', 'saiu_em' => now()->subYear(), 'destaque' => true]));
    Publicacao::create(materia(['titulo' => 'Sem data', 'saiu_em' => null]));

    expect(Publicacao::query()->publicaveis()->pluck('titulo')->all())
        ->toBe(['Em destaque', 'Recente', 'Sem data']);
});

it('recusa link que não é http ou https', function (string $link) {
    $this->actingAs($this->banda)
        ->post(route('painel.publicacoes.store'), materia(['link' => $link]))
        ->assertSessionHasErrors('link');

    expect(Publicacao::count())->toBe(0);
})->with(['javascript:alert(1)', 'data:text/html,<script>alert(1)</script>', 'radio.teste/entrevista', 'ftp://radio.teste/a']);

it('o resumo é texto: marcação escrita nele chega escapada ao site', function () {
    Publicacao::create(materia(['resumo' => '<script>alert(1)</script>']));

    $this->get('/imprensa')->assertDontSee('<script>alert(1)</script>', escape: false);
});

it('a banda cria, edita trocando a imagem e arquiva', function () {
    $this->actingAs($this->banda)
        ->post(route('painel.publicacoes.store'), materia(['imagem' => UploadedFile::fake()->create('capa.jpg', 20, 'image/jpeg')]))
        ->assertRedirect()->assertSessionHasNoErrors();

    $publicacao = Publicacao::firstOrFail();
    $primeira = $publicacao->imagem_path;
    Storage::disk('public')->assertExists($primeira);

    $this->actingAs($this->banda)
        ->post(route('painel.publicacoes.update', $publicacao), materia([
            '_method' => 'put', 'titulo' => 'Rock no bairro, parte 2', 'publicada' => false,
            'imagem' => UploadedFile::fake()->create('nova.jpg', 20, 'image/jpeg'),
        ]))->assertSessionHasNoErrors();

    $publicacao->refresh();
    expect($publicacao->titulo)->toBe('Rock no bairro, parte 2')
        ->and($publicacao->publicada)->toBeFalse()
        ->and($publicacao->imagem_path)->not->toBe($primeira);
    Storage::disk('public')->assertMissing($primeira);

    $this->actingAs($this->banda)
        ->put(route('painel.publicacoes.update', $publicacao), materia())->assertSessionHasNoErrors();
    expect($publicacao->fresh()->imagem_path)->toBe($publicacao->imagem_path);

    $this->actingAs($this->banda)->delete(route('painel.publicacoes.destroy', $publicacao))->assertRedirect();
    expect(Publicacao::count())->toBe(0)->and(Publicacao::onlyTrashed()->count())->toBe(1);

    Storage::disk('public')->assertExists($publicacao->imagem_path);
});

it('data no futuro é recusada', function () {
    $this->actingAs($this->banda)
        ->post(route('painel.publicacoes.store'), materia(['saiu_em' => now()->addWeek()->format('Y-m-d')]))
        ->assertSessionHasErrors('saiu_em');
});

it('o llms.txt é texto puro: apóstrofo e "e comercial" não saem como entidade de HTML', function () {
    Publicacao::create(materia(['titulo' => "Noite no Casa da Esquina & convidadas"]));

    $this->get('/llms.txt')->assertOk()
        ->assertSee("Noite no Casa da Esquina & convidadas", escape: false)
        ->assertDontSee('&#039;', escape: false)
        ->assertDontSee('&amp;', escape: false);
});
