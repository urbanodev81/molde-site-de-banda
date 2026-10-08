<?php

declare(strict_types=1);

namespace App\Providers;

use App\Jobs\Central\EntregarOutboxDoCentralJob;
use App\Models\Show;
use App\Models\User;
use App\Services\Central\OutboxDoCentral;
use App\Support\Mail\RedirecionarDestinatarios;
use App\Support\Security\AlertaDeSeguranca;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Event::listen(Lockout::class, function (Lockout $e) {
            $email = Str::lower((string) $e->request->input('email'));

            AlertaDeSeguranca::avisar(
                'login.travado',
                'Conta travada por excesso de senhas erradas',
                'Cinco senhas erradas para a mesma conta. Pode ser o dono esquecido — ou força bruta.',
                [
                    'target_email' => $email ?: null,
                    'actor_ip' => $e->request->ip(),
                    'user_agent' => $e->request->userAgent(),
                    'changes' => ['host' => $e->request->getHost()],
                ],
                freio: $email.'|'.$e->request->ip(),
            );
        });

        Vite::prefetch(concurrency: 3);

        Event::listen(Login::class, function (Login $evento): void {
            if ($evento->user instanceof User) {
                $evento->user->registrarAcesso();

                rescue(function () use ($evento) {
                    $registrado = app(OutboxDoCentral::class)->registrar(OutboxDoCentral::LOGIN_REPORTADO, [
                        'user_identifier' => (string) $evento->user->email,
                        'user_name' => (string) $evento->user->name,
                        'occurred_at' => now()->toIso8601String(),
                    ]);

                    if ($registrado !== null) {
                        EntregarOutboxDoCentralJob::dispatch();
                    }
                });
            }
        });

        Event::listen(MessageSending::class, RedirecionarDestinatarios::class);

        View::composer('site.layout', function ($view): void {
            $view->with('proximoNoTopo', Cache::remember(
                'site.proximo-no-topo',
                now()->addMinutes(5),
                function (): ?array {
                    $show = Show::query()->publicaveis()->futuros()->first();

                    return $show ? [
                        'iso' => $show->comeca_em->toIso8601String(),
                        'dia' => $show->comeca_em->format('d/m'),
                        'semana' => $show->comeca_em->translatedFormat('D'),
                    ] : null;
                },
            ));
        });
    }
}
