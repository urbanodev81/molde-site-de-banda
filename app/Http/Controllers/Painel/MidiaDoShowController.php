<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Foto;
use App\Models\Show;
use App\Models\Video;
use App\Support\EnvioDeVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MidiaDoShowController extends Controller
{
    public function enviarVideo(Request $request, Show $show): RedirectResponse
    {
        $validador = Validator::make($request->all(), [
            'titulo' => ['required', 'string', 'max:255'],
            'modo' => ['required', Rule::in(['link', 'arquivo'])],
            'link' => ['nullable', 'required_if:modo,link', 'string', 'max:500'],
            'arquivo' => [...EnvioDeVideo::regrasDoArquivo(), 'required_if:modo,arquivo'],
            'capa' => ['nullable', 'image', 'max:4096'],
        ], [
            ...EnvioDeVideo::mensagens('arquivo'),
            'link.required_if' => 'Cole o link do vídeo.',
            'arquivo.required_if' => 'Escolha o arquivo do vídeo.',
        ]);

        $validador->after(function ($v) use ($request): void {
            if ($request->input('modo') === 'link' && filled($request->input('link')) && EnvioDeVideo::doLink($request->input('link')) === null) {
                $v->errors()->add('link', EnvioDeVideo::mensagemDeLinkDesconhecido());
            }

            if ($request->input('modo') === 'arquivo') {
                EnvioDeVideo::conferirDuracao($v, $request->file('arquivo'), 'arquivo');
            }
        });

        $dados = $validador->validate();

        $video = EnvioDeVideo::criarNaBiblioteca(
            [
                'titulo' => $dados['titulo'],
                'show_id' => $show->id,
                'local_id' => $show->local_id,
                'gravado_em' => $show->comeca_em->toDateString(),
                'publicado' => true,
            ],
            $dados['modo'] === 'arquivo' ? $request->file('arquivo') : null,
            $dados['modo'] === 'link' ? $dados['link'] : null,
            $request->file('capa'),
        );

        if ($video->show_id === null) {
            $video->update(['show_id' => $show->id, 'local_id' => $video->local_id ?? $show->local_id]);
        }

        if ($video->show_id !== $show->id) {
            return back()->with('erro', "Este vídeo já está cadastrado em outra noite (\"{$video->titulo}\"). Tire-o de lá primeiro, na página daquele show.");
        }

        return back()->with('sucesso', 'Vídeo adicionado a esta noite.');
    }

    public function vincularVideo(Request $request, Show $show): RedirectResponse
    {
        $dados = $request->validate([

            'video_id' => ['required', Rule::exists('videos', 'id')->whereNull('deleted_at')->whereNull('show_id')],
        ], [
            'video_id.required' => 'Escolha o vídeo.',
            'video_id.exists' => 'Este vídeo já é de outra noite. Tire-o de lá primeiro.',
        ]);

        Video::query()->whereKey($dados['video_id'])->firstOrFail()
            ->update(['show_id' => $show->id]);

        return back()->with('sucesso', 'Vídeo ligado a esta noite.');
    }

    public function desligarVideo(Show $show, Video $video): RedirectResponse
    {
        abort_unless($video->show_id === $show->id, 404);

        $video->update(['show_id' => null]);

        return back()->with('sucesso', 'Vídeo tirado desta noite. Ele continua na tela de vídeos.');
    }

    public function desligarFoto(Show $show, Foto $foto): RedirectResponse
    {
        abort_unless($foto->show_id === $show->id, 404);

        $foto->update(['show_id' => null]);

        return back()->with('sucesso', 'Foto tirada desta noite. Ela continua na tela de fotos.');
    }
}
