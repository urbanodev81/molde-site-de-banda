<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function eventoDoSchedule(string $comando): ?Event
{
    return collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains((string) $e->command, $comando));
}

it('agenda o clean antes do run, só fora do ambiente local', function () {
    $clean = eventoDoSchedule('backup:clean');
    $run = eventoDoSchedule('backup:run');

    expect($clean)->not->toBeNull()
        ->and($run)->not->toBeNull()
        ->and($clean->expression)->toBe('30 2 * * *')
        ->and($run->expression)->toBe('0 3 * * *')
        ->and($run->environments)->toBe(['staging', 'production']);
});

it('guarda o zip num disco que não é servido e cifra pela senha do .env', function () {
    expect(config('backup.backup.destination.disks'))->toBe(['backups'])
        ->and(config('filesystems.disks.backups.root'))->toBe(storage_path('app/backups'))
        ->and(config('filesystems.disks.backups'))->not->toHaveKey('serve')
        ->and(config('backup.backup.password'))->toBe(env('BACKUP_ARCHIVE_PASSWORD'));
});

it('relata o backup ao Central com o token do projeto', function () {
    app()->detectEnvironment(fn () => 'local');
    config([
        'services.central.base_url' => 'https://central.test',
        'services.central.project_token' => 'token-do-banda',
    ]);
    Http::fake(['central.test/*' => Http::response(['ok' => true])]);

    $this->artisan('backup:reportar', ['--status' => 'falha', '--mensagem' => 'disco cheio'])
        ->expectsOutputToContain('relatado ao Central')
        ->assertSuccessful();

    Http::assertSent(fn (Request $r) => $r->url() === 'https://central.test/api/backups/report'
        && $r->hasHeader('Authorization', 'Bearer token-do-banda')
        && $r['status'] === 'falha'
        && $r['mensagem'] === 'disco cheio'
        && $r['destino'] === 'backups');
});

it('não derruba o cron quando o Central está fora do ar', function () {
    app()->detectEnvironment(fn () => 'local');
    config([
        'services.central.base_url' => 'https://central.test',
        'services.central.project_token' => 'token-do-banda',
    ]);
    Http::fake(['central.test/*' => Http::response('fora', 503)]);

    $this->artisan('backup:reportar')
        ->expectsOutputToContain('não recebeu')
        ->assertSuccessful();
});
