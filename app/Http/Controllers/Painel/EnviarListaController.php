<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Mail\ListaDoPainel;
use App\Support\Lote\ListasEmLote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EnviarListaController extends Controller
{
    public const TETO = 30;

    public function __invoke(Request $request, string $lista): RedirectResponse
    {
        $definicao = ListasEmLote::de($lista);
        abort_if($definicao['publicos'] === null, 404);

        [$um, $varios, $genero] = $definicao['nome'];

        $dados = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:'.self::TETO],
            'ids.*' => [ListasEmLote::regraDaChave($definicao)],
            'email' => ['required', 'email:rfc', 'max:255'],
            'mensagem' => ['nullable', 'string', 'max:500'],
        ], [
            'ids.max' => 'Dá para enviar até '.self::TETO." {$varios} por vez.",
        ]);

        $consulta = $definicao['modelo']::query()->whereIn(ListasEmLote::chave($definicao), $dados['ids']);
        $definicao['publicos']($consulta);
        $definicao['ordem']($consulta);

        $itens = $consulta->get()->map(fn (Model $item) => $definicao['linha']($item))->all();

        if ($itens === []) {
            throw ValidationException::withMessages([
                'ids' => $genero === 'a'
                    ? "Nenhuma das {$varios} marcadas está no site, e só o que está no site pode ser enviado."
                    : "Nenhum dos {$varios} marcados está no site, e só o que está no site pode ser enviado.",
            ]);
        }

        Mail::to($dados['email'])->send(
            new ListaDoPainel($definicao['nome'], $itens, $request->user(), $dados['mensagem'] ?? null)
        );

        $fora = count($dados['ids']) - count($itens);
        $enviado = $genero === 'a' ? 'enviada' : 'enviado';
        $frase = count($itens) === 1 ? "1 {$um} {$enviado}" : count($itens)." {$varios} {$enviado}s";

        return back()->with('sucesso', $frase." para {$dados['email']}."
            .($fora > 0 ? " {$fora} ficou(aram) de fora por não estar(em) no site." : ''));
    }
}
