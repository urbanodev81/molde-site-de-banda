<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\VisitaDiaria;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContarVisita
{
    private const FORA_DA_CONTA = [
        'site.sitemap',
        'site.llms',
        'site.previa',
    ];

    private const MARCAS_DE_ROBO = [
        'bot', 'crawl', 'spider', 'slurp', 'facebookexternalhit', 'whatsapp',
        'telegram', 'discord', 'preview', 'headless', 'lighthouse', 'monitor',
        'curl', 'wget', 'python-requests', 'go-http-client',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $resposta = $next($request);

        if ($this->deveContar($request, $resposta)) {
            try {
                VisitaDiaria::somarUma(
                    now(),
                    $this->caminho($request),
                    $request->route()?->getName(),
                );
            } catch (\Throwable) {
            }
        }

        return $resposta;
    }

    private function deveContar(Request $request, Response $resposta): bool
    {
        if (! $request->isMethod('GET') || $resposta->getStatusCode() !== 200) {
            return false;
        }

        if ($request->user() !== null) {
            return false;
        }

        $rota = $request->route()?->getName();

        if ($rota === null || ! str_starts_with($rota, 'site.')) {
            return false;
        }

        if (in_array($rota, self::FORA_DA_CONTA, true)) {
            return false;
        }

        return ! $this->pareceRobo((string) $request->userAgent());
    }

    private function pareceRobo(string $userAgent): bool
    {
        if ($userAgent === '') {
            return true;
        }

        $agente = mb_strtolower($userAgent);

        foreach (self::MARCAS_DE_ROBO as $marca) {
            if (str_contains($agente, $marca)) {
                return true;
            }
        }

        return false;
    }

    private function caminho(Request $request): string
    {
        $caminho = '/'.ltrim($request->path(), '/');

        return mb_substr($caminho === '//' ? '/' : $caminho, 0, 255);
    }
}
