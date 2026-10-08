<?php

namespace App\Http\Middleware;

use App\Support\Captcha\CaptchaVerifier;
use App\Support\Perfis;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $usuario = $request->user();

        return [
            ...parent::share($request),

            'captcha' => app(CaptchaVerifier::class)->frontendConfig(),

            'push' => [
                'chave_publica' => config('webpush.vapid.public_key'),
            ],
            'auth' => [
                'user' => $usuario,

                'perfil' => Perfis::rotulo($usuario?->getRoleNames()->first()),

                'permissoes' => [
                    'shows' => (bool) $usuario?->can('shows.ver'),
                    'locais' => (bool) $usuario?->can('locais.ver'),
                    'integrantes' => (bool) $usuario?->can('integrantes.ver'),
                    'videos' => (bool) $usuario?->can('videos.ver'),
                    'fotos' => (bool) $usuario?->can('fotos.ver'),
                    'musicas' => (bool) $usuario?->can('musicas.ver'),
                    'materiais' => (bool) $usuario?->can('materiais.ver'),
                    'contratacoes' => (bool) $usuario?->can('contratacoes.ver'),

                    'contratacoesValores' => (bool) $usuario?->can('contratacoes.ver_valores'),

                    'estatisticas' => (bool) $usuario?->can('estatisticas.ver'),

                    'perguntas' => (bool) $usuario?->can('perguntas.gerenciar'),
                    'depoimentos' => (bool) $usuario?->can('depoimentos.gerenciar'),
                    'publicacoes' => (bool) $usuario?->can('publicacoes.gerenciar'),
                    'configuracoes' => (bool) $usuario?->can('configuracoes.gerenciar'),
                    'usuarios' => (bool) $usuario?->can('usuarios.ver'),
                    'auditoria' => (bool) $usuario?->can('auditoria.ver'),
                ],
            ],

            'flash' => [
                'sucesso' => fn () => $request->session()->get('sucesso'),

                'erro' => fn () => $request->session()->get('erro'),
            ],
        ];
    }
}
