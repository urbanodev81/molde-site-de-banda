<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Mail\ListaDeFotos;
use App\Models\Foto;
use App\Support\Arquivos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EnviarListaDeFotosController extends Controller
{
    public const TETO = 30;

    public function __invoke(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:'.self::TETO],
            'ids.*' => ['uuid'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'mensagem' => ['nullable', 'string', 'max:500'],
        ], [
            'ids.max' => 'Dá para enviar até '.self::TETO.' fotos por vez.',
        ]);

        $fotos = Foto::query()->doSite()->with(['show.local', 'tipoGaleria'])
            ->whereIn('uuid', $dados['ids'])
            ->get()
            ->map(fn (Foto $f) => [
                'titulo' => $f->legenda ?: $f->textoAlternativo(),
                'contexto' => $f->contexto(),
                'credito' => $f->credito,
                'url' => Arquivos::url($f->arquivo_path),
            ])
            ->all();

        if ($fotos === []) {
            throw ValidationException::withMessages([
                'ids' => 'Nenhuma das fotos marcadas está no site, e só o que está no site pode ser enviado.',
            ]);
        }

        Mail::to($dados['email'])->send(new ListaDeFotos($fotos, $request->user(), $dados['mensagem'] ?? null));

        $fora = count($dados['ids']) - count($fotos);
        $frase = count($fotos) === 1 ? '1 foto enviada' : count($fotos).' fotos enviadas';

        return back()->with('sucesso', $frase." para {$dados['email']}."
            .($fora > 0 ? " {$fora} ficou(aram) de fora por não estar(em) no site." : ''));
    }
}
