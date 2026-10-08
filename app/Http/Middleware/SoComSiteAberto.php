<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\TipoEvento;
use App\Support\ConfiguracaoDoSite;
use App\Support\SitePublicado;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SoComSiteAberto
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SitePublicado::bloqueia($request)) {
            return $next($request);
        }

        if (! $request->routeIs('site.home')) {
            return redirect()->route('site.home');
        }

        return response()->view('site.em-breve', [
            'config' => ConfiguracaoDoSite::todas(),
            'tiposEvento' => TipoEvento::opcoes(),
        ]);
    }
}
