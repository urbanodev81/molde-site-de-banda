<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Foto;
use App\Support\Arquivos;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ImprimirFotosController extends Controller
{
    public function __invoke(Request $request): View
    {
        $dados = $request->validate([
            'ids' => ['nullable', 'array', 'max:100'],
            'ids.*' => ['uuid'],
        ]);

        $fotos = Foto::withTrashed()->with(['show.local', 'integrantes', 'tipoGaleria'])
            ->whereIn('uuid', $dados['ids'] ?? [])
            ->orderBy('ordem')->orderByDesc('created_at')
            ->get()
            ->map(fn (Foto $f) => [
                'titulo' => $f->legenda ?: 'Sem título',
                'alt' => $f->textoAlternativo(),
                'url' => Arquivos::url($f->arquivo_path),
                'credito' => $f->credito,
                'contexto' => $f->contexto(),
                'quando' => $f->quando()?->format('d/m/Y'),
                'integrantes' => $f->integrantes->map->comoAparece()->implode(', '),
                'situacao' => $f->trashed() ? 'Arquivada' : ($f->publicada ? 'Publicada' : 'Fora do site'),
            ]);

        return view('painel.imprimir-fotos', ['fotos' => $fotos]);
    }
}
