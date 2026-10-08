<?php

declare(strict_types=1);

namespace App\Http\Requests\Painel;

use App\Models\TipoEspaco;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],

            'tipo_id' => ['nullable', 'integer', Rule::exists('tipos', 'id')->where('grupo', TipoEspaco::GRUPO)->whereNull('deleted_at')],
            'cidade' => ['nullable', 'string', 'max:255'],
            'uf' => ['nullable', 'string', 'size:2'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'cep' => ['nullable', 'string', 'max:9'],
            'mapa_url' => ['nullable', 'url', 'max:500'],
            'site_url' => ['nullable', 'url', 'max:500'],
            'instagram' => ['nullable', 'url', 'max:500'],
            'contato_nome' => ['nullable', 'string', 'max:255'],
            'contato_telefone' => ['nullable', 'string', 'max:20'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'ativa' => ['boolean'],
            'logo' => ['nullable', 'image', 'max:4096'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ativa' => $this->boolean('ativa'),
            'uf' => $this->filled('uf') ? mb_strtoupper((string) $this->input('uf')) : null,
        ]);
    }
}
