<?php

declare(strict_types=1);

use App\Models\Contratacao;
use App\Models\Integrante;
use App\Models\Local;
use App\Models\Material;
use App\Models\ParticipacaoEspecial;
use App\Models\Show;
use App\Models\TipoGaleria;
use App\Models\User;
use App\Models\Video;
use App\Support\Perfis;
use Database\Seeders\ConteudoInicial;
use Database\Seeders\PerfisESeguranca;
use Database\Seeders\TiposDaGaleria;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
    $this->seed(TiposDaGaleria::class);
    $this->seed(ConteudoInicial::class);

    $this->admin = User::create([
        'name' => 'Admin da varredura',
        'email' => 'varredura@teste.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    $this->admin->syncRoles([Perfis::ADMINISTRADOR]);
});

function exemplaresParaRota(User $admin): array
{
    $show = Show::query()->first() ?? Show::create([
        'titulo' => 'Show de varredura',
        'comeca_em' => now()->addMonth(),
    ]);

    $local = Local::query()->first() ?? Local::create(['nome' => 'Local de varredura']);
    $integrante = Integrante::query()->first() ?? Integrante::create(['nome' => 'Integrante de varredura']);

    $participacao = ParticipacaoEspecial::query()->first() ?? ParticipacaoEspecial::create([
        'nome' => 'Convidada de varredura',
    ]);

    $video = Video::query()->first() ?? Video::create([
        'titulo' => 'Vídeo de varredura',
        'youtube_id' => 'dQw4w9WgXcQ',
    ]);

    $contratacao = Contratacao::query()->first() ?? Contratacao::create([
        'nome' => 'Pedido de varredura',
        'email' => 'pedido@teste.local',
    ]);

    $tipoGaleria = TipoGaleria::query()->first();

    $material = Material::query()->first() ?? Material::create([
        'titulo' => 'Material de varredura',
        'arquivo_path' => 'materiais/varredura.pdf',
    ]);

    return [

        'show' => (string) $show->uuid,
        'uuid' => (string) $show->uuid,
        'tipo' => (string) ($tipoGaleria?->slug ?? 'show'),
        'variante' => 'kit',

        'local' => (string) $local->uuid,
        'integrante' => (string) $integrante->uuid,
        'participacao' => (string) $participacao->uuid,
        'video' => (string) $video->uuid,
        'contratacao' => (string) $contratacao->uuid,
        'material' => (string) $material->uuid,
        'usuario' => (string) $admin->uuid,
    ];
}

function exemplaresPorRota(Show $show): array
{
    return [
        'site.show' => ['show' => (string) $show->slug],
    ];
}

const ROTAS_FORA_DA_VARREDURA = [

    'sanctum.csrf-cookie' => 'não é tela',

    'storage.local' => 'serve arquivo, não tela',
    'storage.local.upload' => 'serve arquivo, não tela',

    'up' => 'health check do framework',

    'verification.verify' => 'exige link assinado; coberto pelo fluxo de verificação',
    'password.reset' => 'exige token válido; coberto pelo fluxo de senha',

    'password.confirm' => 'tela de confirmação de senha, coberta em Auth',

    'captcha.desafio' => 'devolve JSON, e 404 em testing é o comportamento certo',
];

it('responde em toda rota GET do site e do painel', function () {
    $exemplares = exemplaresParaRota($this->admin);
    $porRota = exemplaresPorRota(Show::query()->firstOrFail());

    $falhas = [];
    $visitadas = 0;

    foreach (Route::getRoutes() as $rota) {
        $nome = $rota->getName();

        if (! in_array('GET', $rota->methods(), true)) {
            continue;
        }

        if ($nome !== null && array_key_exists($nome, ROTAS_FORA_DA_VARREDURA)) {
            continue;
        }

        if ($nome === null || $rota->uri() === 'up') {
            continue;
        }

        $uri = $rota->uri();
        $desconhecidos = [];
        $doCaso = array_merge($exemplares, $porRota[$nome] ?? []);

        foreach ($rota->parameterNames() as $parametro) {
            if (! array_key_exists($parametro, $doCaso)) {
                $desconhecidos[] = $parametro;

                continue;
            }

            $uri = preg_replace('/\{'.$parametro.'(:[^}]+)?\??\}/', $doCaso[$parametro], $uri) ?? $uri;
        }

        if ($desconhecidos !== []) {
            $falhas[] = "{$nome} → sem exemplar para: ".implode(', ', $desconhecidos)
                .' (crie um em `exemplaresParaRota()`)';

            continue;
        }

        $resposta = $this->actingAs($this->admin)->get('/'.ltrim($uri, '/'));
        $visitadas++;

        $codigo = $resposta->baseResponse->getStatusCode();

        if (! in_array($codigo, [200, 301, 302], true)) {
            $falhas[] = "{$nome} ({$uri}) → HTTP {$codigo}";
        }
    }

    expect($falhas)->toBe([], "Rotas que não responderam:\n  ".implode("\n  ", $falhas));
    expect($visitadas)->toBeGreaterThan(40);
});
