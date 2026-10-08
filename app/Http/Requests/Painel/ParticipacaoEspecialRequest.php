<?php

declare(strict_types=1);

namespace App\Http\Requests\Painel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParticipacaoEspecialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'funcao' => ['nullable', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'instagram' => ['nullable', 'url', 'max:500'],
            'facebook' => ['nullable', 'url', 'max:500'],
            'tiktok' => ['nullable', 'url', 'max:500'],
            'youtube' => ['nullable', 'url', 'max:500'],
            'site_url' => ['nullable', 'url', 'max:500'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:999'],
            'publicada' => ['boolean'],

            'shows' => ['nullable', 'array'],
            'shows.*' => ['integer', Rule::exists('shows', 'id')->whereNull('deleted_at')],

            'autorizacao_imagem_em' => ['nullable', 'date', 'before_or_equal:today'],
            'autorizacao_documento' => ['nullable', 'file', 'max:8192', 'mimes:jpg,jpeg,png,pdf'],

            'foto' => ['nullable', 'image', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'autorizacao_imagem_em.before_or_equal' => 'A data da autorização não pode estar no futuro.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['publicada' => $this->boolean('publicada')]);
    }
}
