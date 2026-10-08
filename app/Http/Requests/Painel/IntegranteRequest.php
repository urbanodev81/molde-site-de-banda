<?php

declare(strict_types=1);

namespace App\Http\Requests\Painel;

use Illuminate\Foundation\Http\FormRequest;

class IntegranteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'nome_artistico' => ['nullable', 'string', 'max:255'],
            'instrumento' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'instagram' => ['nullable', 'url', 'max:500'],
            'facebook' => ['nullable', 'url', 'max:500'],
            'tiktok' => ['nullable', 'url', 'max:500'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:999'],
            'ativa' => ['boolean'],

            'palco_esquerda' => ['nullable', 'integer', 'min:0', 'max:100'],
            'palco_largura' => ['nullable', 'integer', 'min:1', 'max:100'],
            'palco_base' => ['nullable', 'integer', 'min:0', 'max:100'],

            'autorizacao_imagem_em' => ['nullable', 'date', 'before_or_equal:today'],
            'autorizacao_documento' => ['nullable', 'file', 'max:8192', 'mimes:jpg,jpeg,png,pdf'],

            'foto' => ['nullable', 'image', 'max:8192'],
            'recorte' => ['nullable', 'image', 'max:8192', 'mimes:png,webp'],
        ];
    }

    public function messages(): array
    {
        return [
            'recorte.mimes' => 'O recorte precisa ser PNG ou WebP — é ele que entra no palco da home sem fundo, '
                .'e JPG não guarda transparência.',
            'autorizacao_imagem_em.before_or_equal' => 'A data da autorização não pode estar no futuro.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['ativa' => $this->boolean('ativa')]);
    }
}
