<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Support\ConfiguracaoDoSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracaoController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Painel/Configuracoes/Editar', [
            'grupos' => ConfiguracaoDoSite::paraTela(),
            'rotulos' => ConfiguracaoDoSite::rotulosDosGrupos(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $dados = $request->validate(ConfiguracaoDoSite::regras());

        ConfiguracaoDoSite::gravar($dados['valores'] ?? []);

        return back()->with('sucesso', 'Configuração salva. O site já está mostrando o novo valor.');
    }
}
