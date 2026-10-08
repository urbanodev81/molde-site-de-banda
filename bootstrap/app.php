<?php

use App\Http\Middleware\ContarVisita;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrackCentralPresence;
use App\Http\Middleware\VerifyCsrfToken;
use App\Support\Alertas\AlertaDeErro;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,

            AddLinkHeadersForPreloadedAssets::using(6),

            SecurityHeaders::class,

            TrackCentralPresence::class,

            ContarVisita::class,
        ]);

        $middleware->web(replace: [
            PreventRequestForgery::class => VerifyCsrfToken::class,
        ]);

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(fn (Throwable $e) => AlertaDeErro::avisar($e));

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                return $response;
            }

            $tratados = [403, 404, 419, 429, 503];

            if (! in_array($status, $tratados, true) && ! ($status === 500 && ! config('app.debug'))) {
                return $response;
            }

            return Inertia::render('Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
