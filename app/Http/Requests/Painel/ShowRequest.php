<?php

declare(strict_types=1);

namespace App\Http\Requests\Painel;

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class ShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'local_id' => ['nullable', Rule::exists('locais', 'id')->whereNull('deleted_at')],
            'titulo' => ['nullable', 'string', 'max:255'],
            'endereco_livre' => ['nullable', 'string', 'max:255'],
            'mapa_url' => ['nullable', 'url', 'max:500'],
            'comeca_em' => ['required', 'date'],
            'termina_em' => ['nullable', 'date', 'after:comeca_em'],
            'status' => ['required', new Enum(StatusShow::class)],
            'tipo' => ['required', new Enum(TipoShow::class)],
            'entrada' => ['nullable', 'string', 'max:255'],
            'observacoes_publicas' => ['nullable', 'string', 'max:2000'],
            'observacoes_internas' => ['nullable', 'string', 'max:2000'],
            'cache' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'destaque' => ['boolean'],
            'publicado' => ['boolean'],
            'cartaz' => ['nullable', 'image', 'max:8192'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if (blank($this->input('local_id')) && blank($this->input('titulo'))) {
                $v->errors()->add(
                    'titulo',
                    'Escolha um local cadastrado ou escreva um título — senão o show aparece sem nome na agenda.',
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'destaque' => $this->boolean('destaque'),
            'publicado' => $this->boolean('publicado'),
        ]);
    }
}
