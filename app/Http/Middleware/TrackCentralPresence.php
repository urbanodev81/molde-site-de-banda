<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Jobs\Central\PingCentralPresenceJob;
use App\Services\Central\CentralPresenceClient;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackCentralPresence
{
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly CentralPresenceClient $cliente,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $this->cliente->isConfigured()) {
            $chave = "central-presence-ping:{$usuario->id}";

            if ($this->limiter->attempt($chave, 1, function () {}, 60)) {
                PingCentralPresenceJob::dispatchAfterResponse(
                    (string) $usuario->email,
                    (string) $usuario->name,
                    $request->path(),
                );
            }
        }

        return $next($request);
    }
}
