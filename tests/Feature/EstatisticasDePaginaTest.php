<?php

declare(strict_types=1);

use App\Models\Show;
use App\Models\User;
use App\Models\VisitaDiaria;
use App\Support\Perfis;
use Database\Seeders\ConteudoInicial;
use Database\Seeders\PerfisESeguranca;
use Database\Seeders\TiposDaGaleria;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
    $this->seed(TiposDaGaleria::class);
    $this->seed(ConteudoInicial::class);
});

function comoVisitante(): array
{
    return ['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) AppleWebKit/605.1.15 Safari/604.1'];
}

describe('o que entra na conta', function () {
    it('conta a visita de quem chega de fora', function () {
        $this->withHeaders(comoVisitante())->get('/')->assertOk();

        expect(VisitaDiaria::query()->where('caminho', '/')->value('visitas'))->toBe(1);
    });

    it('soma na MESMA linha quando a página é aberta de novo no mesmo dia', function () {
        $this->withHeaders(comoVisitante())->get('/agenda')->assertOk();
        $this->withHeaders(comoVisitante())->get('/agenda')->assertOk();
        $this->withHeaders(comoVisitante())->get('/agenda')->assertOk();

        expect(VisitaDiaria::query()->where('caminho', '/agenda')->count())->toBe(1)
            ->and(VisitaDiaria::query()->where('caminho', '/agenda')->value('visitas'))->toBe(3);
    });

    it('guarda o nome da rota junto, para agrupar sem `like` no caminho', function () {
        $this->withHeaders(comoVisitante())->get('/agenda')->assertOk();

        expect(VisitaDiaria::query()->where('caminho', '/agenda')->value('rota'))->toBe('site.agenda');
    });

    it('conta cada página de show separadamente — é a pergunta que a banda faz', function () {
        $show = Show::query()->publicaveis()->firstOrFail();

        $this->withHeaders(comoVisitante())->get("/agenda/{$show->slug}")->assertOk();

        expect(VisitaDiaria::query()->where('caminho', "/agenda/{$show->slug}")->value('visitas'))->toBe(1);
    });

    it('ignora a query string — `/agenda?utm_source=x` é a mesma página', function () {
        $this->withHeaders(comoVisitante())->get('/agenda')->assertOk();
        $this->withHeaders(comoVisitante())->get('/agenda?utm_source=instagram')->assertOk();

        expect(VisitaDiaria::query()->where('caminho', '/agenda')->value('visitas'))->toBe(2)
            ->and(VisitaDiaria::query()->count())->toBe(1);
    });
});

describe('o que NÃO entra na conta', function () {
    it('não conta quem está logado no painel', function () {
        $usuario = User::create([
            'name' => 'Ana', 'email' => 'ana@teste.local', 'password' => 'senha-de-teste-123',
            'ativo' => true, 'email_verified_at' => now(),
        ]);
        $usuario->syncRoles([Perfis::BANDA]);

        $this->actingAs($usuario)->withHeaders(comoVisitante())->get('/')->assertOk();

        expect(VisitaDiaria::query()->count())->toBe(0);
    });

    it('não conta pré-visualizador de link nem robô', function (string $agente) {
        $this->withHeaders(['User-Agent' => $agente])->get('/')->assertOk();

        expect(VisitaDiaria::query()->count())->toBe(0);
    })->with([
        'WhatsApp/2.23.20.0',
        'facebookexternalhit/1.1',
        'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'curl/8.4.0',
        'Mozilla/5.0 (X11; Linux x86_64) HeadlessChrome/120.0.0.0',
    ]);

    it('não conta requisição sem user-agent, porque navegador sempre manda um', function () {
        $this->withHeaders(['User-Agent' => ''])->get('/')->assertOk();

        expect(VisitaDiaria::query()->count())->toBe(0);
    });

    it('não conta o sitemap nem o llms.txt — são endereço de robô por definição', function (string $rota) {
        $this->withHeaders(comoVisitante())->get($rota)->assertOk();

        expect(VisitaDiaria::query()->count())->toBe(0);
    })->with(['/sitemap.xml', '/llms.txt']);

    it('não conta rota do painel', function () {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@teste.local', 'password' => 'senha-de-teste-123',
            'ativo' => true, 'email_verified_at' => now(),
        ]);
        $admin->syncRoles([Perfis::ADMINISTRADOR]);

        $this->actingAs($admin)->withHeaders(comoVisitante())->get('/painel')->assertOk();

        expect(VisitaDiaria::query()->count())->toBe(0);
    });

    it('não conta página que não existe', function () {
        $this->withHeaders(comoVisitante())->get('/agenda/show-que-nunca-existiu')->assertNotFound();

        expect(VisitaDiaria::query()->count())->toBe(0);
    });
});

it('não guarda nada que identifique quem visitou', function (string $coluna) {
    expect(Schema::hasColumn('visitas_diarias', $coluna))->toBeFalse(
        "A coluna `{$coluna}` apareceu em `visitas_diarias`. A página de privacidade promete que ela não existe — "
        .'ou a coluna sai, ou a promessa é reescrita ANTES.',
    );
})->with(['ip', 'ip_address', 'endereco_ip', 'user_agent', 'agente', 'visitante_id', 'session_id', 'cookie', 'user_id']);

describe('a tela', function () {
    it('mostra o ranking para quem pode ver', function () {
        $usuario = User::create([
            'name' => 'Carol', 'email' => 'carol@teste.local', 'password' => 'senha-de-teste-123',
            'ativo' => true, 'email_verified_at' => now(),
        ]);
        $usuario->syncRoles([Perfis::BANDA]);

        VisitaDiaria::somarUma(now(), '/agenda', 'site.agenda');
        VisitaDiaria::somarUma(now(), '/agenda', 'site.agenda');
        VisitaDiaria::somarUma(now(), '/', 'site.home');

        $this->actingAs($usuario)
            ->get('/painel/estatisticas')
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina
                ->component('Painel/Estatisticas/Index')
                ->where('kpis.totalPeriodo', 3)
                ->where('paginas.0.caminho', '/agenda')
                ->where('paginas.0.visitas', 2)
                ->where('paginas.0.titulo', 'Agenda'));
    });

    it('preenche o dia sem visita com zero, em vez de pular o dia', function () {
        $usuario = User::create([
            'name' => 'Bia', 'email' => 'bia@teste.local', 'password' => 'senha-de-teste-123',
            'ativo' => true, 'email_verified_at' => now(),
        ]);
        $usuario->syncRoles([Perfis::BANDA]);

        VisitaDiaria::somarUma(now(), '/', 'site.home');

        $this->actingAs($usuario)
            ->get('/painel/estatisticas?dias=7')
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina->has('serie', 7));
    });

    it('recusa janela fora da lista e volta para 30 dias', function () {
        $usuario = User::create([
            'name' => 'Alex', 'email' => 'alex@teste.local', 'password' => 'senha-de-teste-123',
            'ativo' => true, 'email_verified_at' => now(),
        ]);
        $usuario->syncRoles([Perfis::ADMINISTRADOR]);

        $this->actingAs($usuario)
            ->get('/painel/estatisticas?dias=100000')
            ->assertOk()
            ->assertInertia(fn ($pagina) => $pagina->where('dias', 30)->has('serie', 30));
    });

    it('exige a permissão', function () {
        $semPermissao = User::create([
            'name' => 'Sem', 'email' => 'sem@teste.local', 'password' => 'senha-de-teste-123',
            'ativo' => true, 'email_verified_at' => now(),
        ]);

        $this->actingAs($semPermissao)->get('/painel/estatisticas')->assertForbidden();
    });
});
