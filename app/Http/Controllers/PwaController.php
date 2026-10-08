<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $nome = config('app.name', 'A melhor banda');

        return response()->json([

            'id' => '/',
            'name' => $nome,
            'short_name' => 'BANDA',
            'description' => 'Painel da banda A melhor banda: agenda de shows, '
                .'vídeos, repertório e pedidos de contratação.',
            'lang' => 'pt-BR',
            'dir' => 'ltr',

            'start_url' => '/inicio',

            'scope' => '/',

            'display' => 'standalone',
            'orientation' => 'any',

            'theme_color' => '#06040C',
            'background_color' => '#06040C',

            'shortcuts' => [
                [
                    'name' => 'Cadastrar show',
                    'short_name' => 'Novo show',
                    'description' => 'Colocar uma data nova na agenda',
                    'url' => '/painel/shows/criar',
                ],
                [
                    'name' => 'Pedidos de contratação',
                    'short_name' => 'Pedidos',
                    'description' => 'Quem pediu orçamento e ainda espera resposta',
                    'url' => '/painel/contratacoes',
                ],
            ],

            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-maskable-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => '/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ])->withHeaders([
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }

    public function inicio(Request $request): RedirectResponse
    {
        return redirect()->route($request->user() === null ? 'login' : 'painel.inicio');
    }

    public function offline(): Response
    {
        return response()
            ->view('pwa.offline')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    public function serviceWorker(): Response
    {
        $codigo = str_replace(
            '__VERSAO__',
            $this->versaoDoBuild(),
            (string) file_get_contents(resource_path('pwa/sw.js')),
        );

        return response($codigo, 200, [
            'Content-Type' => 'text/javascript; charset=UTF-8',

            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    private function versaoDoBuild(): string
    {
        $manifesto = public_path('build/manifest.json');

        return is_file($manifesto)
            ? substr(md5_file($manifesto), 0, 12)
            : 'dev';
    }
}
