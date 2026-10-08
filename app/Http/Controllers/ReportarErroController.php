<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Central\CentralPresenceClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportarErroController extends Controller
{
    public function __invoke(Request $request, CentralPresenceClient $central): RedirectResponse
    {
        $dados = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'severity' => ['nullable', 'in:low,medium,high,critical'],
            'page_url' => ['nullable', 'string', 'max:1024'],
        ]);

        $usuario = $request->user();

        $enviado = $central->reportarErro([
            ...$dados,

            'reporter_name' => $usuario?->name,
            'reporter_email' => $usuario?->email,
        ]);

        return $enviado
            ? back()->with('sucesso', 'Relato enviado. Obrigado — a equipe já recebeu.')
            : back()->with('erro', 'Não consegui enviar agora. Tente de novo em instantes, ou avise a equipe por outro caminho.');
    }
}
