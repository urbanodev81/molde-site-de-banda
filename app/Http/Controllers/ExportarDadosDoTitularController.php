<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarDadosDoTitularController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $usuario = $request->user();

        $usuario->loadMissing('roles');

        $dados = [
            'gerado_em' => now()->toIso8601String(),
            'sistema' => 'A melhor banda',

            'usuario' => [
                'nome' => $usuario->name,
                'email' => $usuario->email,
                'telefone' => $usuario->telefone,
                'criado_em' => $usuario->created_at,
                'ultimo_acesso_em' => $usuario->ultimo_acesso_em,
            ],

            'perfis' => $usuario->roles->pluck('name')->all(),

            'pedidos_sob_minha_responsabilidade' => $usuario->contratacoes()
                ->get()
                ->map(fn ($pedido) => [
                    'referencia' => $pedido->uuid,
                    'status' => $pedido->status->value,
                    'criado_em' => $pedido->created_at,
                    'materiais' => $pedido->materiais()->pluck('titulo')->all(),
                ])
                ->all(),

            'minhas_alteracoes' => Auditoria::query()
                ->where('user_id', $usuario->getKey())
                ->orderByDesc('ocorreu_em')
                ->limit(500)
                ->get()
                ->map(fn ($linha) => [
                    'o_que' => $linha->tipoLegivel(),
                    'evento' => $linha->evento,
                    'campo' => $linha->campo,
                    'quando' => $linha->ocorreu_em,
                ])
                ->all(),
        ];

        return response()->streamDownload(
            function () use ($dados) {
                echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            },
            'site-de-banda-meus-dados.json',
            ['Content-Type' => 'application/json'],
        );
    }
}
