<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->isProduction()) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

            $csp = "default-src 'self'; "
                ."script-src 'self' 'unsafe-inline' https://vlibras.gov.br https://cdn.jsdelivr.net; "
                ."style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://vlibras.gov.br https://cdn.jsdelivr.net; "
                ."img-src 'self' data: blob: https:; "
                ."font-src 'self' data: https://fonts.bunny.net https://vlibras.gov.br https://cdn.jsdelivr.net; "
                ."connect-src 'self' https://vlibras.gov.br https://cdn.jsdelivr.net; "
                ."frame-src 'self' https://vlibras.gov.br https://cdn.jsdelivr.net https://www.youtube-nocookie.com https://player.vimeo.com https://www.instagram.com https://www.tiktok.com; "
                ."media-src 'self' blob:; "
                ."object-src 'none'; "

                ."worker-src 'self' blob:; "
                ."manifest-src 'self'; "
                ."base-uri 'self'; "
                ."form-action 'self';";
        } else {
            $vitePort = env('VITE_PORT', 5182);
            $viteOrigin = "http://localhost:{$vitePort}";
            $viteWs = "ws://localhost:{$vitePort}";

            $csp = "default-src 'self' {$viteOrigin} {$viteWs}; "
                ."script-src 'self' 'unsafe-inline' 'unsafe-eval' {$viteOrigin} https://vlibras.gov.br https://cdn.jsdelivr.net; "
                ."style-src 'self' 'unsafe-inline' {$viteOrigin} https://fonts.bunny.net https://vlibras.gov.br https://cdn.jsdelivr.net; "
                ."img-src 'self' data: blob: {$viteOrigin} https:; "
                ."font-src 'self' data: {$viteOrigin} https://fonts.bunny.net https://vlibras.gov.br https://cdn.jsdelivr.net; "
                ."connect-src 'self' {$viteOrigin} {$viteWs} https://vlibras.gov.br https://cdn.jsdelivr.net; "
                ."frame-src 'self' https://vlibras.gov.br https://cdn.jsdelivr.net https://www.youtube-nocookie.com https://player.vimeo.com https://www.instagram.com https://www.tiktok.com; "
                ."media-src 'self' blob:; "
                ."object-src 'none'; "

                ."worker-src 'self' blob:; "
                ."manifest-src 'self'; "
                ."base-uri 'self'; "
                ."form-action 'self';";
        }

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
