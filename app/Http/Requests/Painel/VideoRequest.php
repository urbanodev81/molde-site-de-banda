<?php

declare(strict_types=1);

namespace App\Http\Requests\Painel;

use App\Enums\TipoVideo;
use App\Models\TipoGaleria;
use App\Support\EnvioDeVideo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'local_id' => ['nullable', Rule::exists('locais', 'id')->whereNull('deleted_at')],
            'local_nome' => ['nullable', 'string', 'max:255'],
            'gravado_em' => ['nullable', 'date'],

            'tipo' => ['required', Rule::in(['arquivo', 'link', 'youtube'])],
            'link' => ['nullable', 'string', 'max:500'],

            'show_id' => ['nullable', 'integer', 'exists:shows,id'],

            'tipo_galeria_id' => ['nullable', 'integer', Rule::exists('tipos', 'id')->where('grupo', TipoGaleria::GRUPO)->whereNull('deleted_at')],

            'integrantes' => ['nullable', 'array'],
            'integrantes.*' => ['integer', Rule::exists('integrantes', 'id')->whereNull('deleted_at')],

            'demonstracao' => ['boolean'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:999'],
            'publicado' => ['boolean'],

            'capa' => ['nullable', 'image', 'max:4096'],

            'mp4' => EnvioDeVideo::regrasDoArquivo(),
        ];
    }

    public function messages(): array
    {
        return EnvioDeVideo::mensagens('mp4');
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($this->input('tipo') !== TipoVideo::Arquivo->value) {
                if (blank($this->input('link'))) {
                    $v->errors()->add('link', 'Cole o link do vídeo.');
                } elseif (EnvioDeVideo::doLink($this->input('link')) === null) {
                    $v->errors()->add('link', EnvioDeVideo::mensagemDeLinkDesconhecido());
                }

                return;
            }

            EnvioDeVideo::conferirDuracao($v, $this->file('mp4'), 'mp4');

            $video = $this->route('video');
            $jaTemArquivo = $video?->tipo === TipoVideo::Arquivo && $video->reproduzivel();

            if (! $jaTemArquivo && ! $this->hasFile('mp4')) {
                $v->errors()->add('mp4', 'Envie o arquivo do vídeo, em mp4.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'demonstracao' => $this->boolean('demonstracao'),
            'publicado' => $this->boolean('publicado'),
        ]);
    }
}
