<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaCadastro;
use Illuminate\Foundation\Http\FormRequest;

class RegisterPrestadorRequest extends FormRequest
{
    use NormalizaCadastro;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            ...$this->regrasComuns(),
            'area_atuacao' => ['nullable', 'string', 'max:100'],
            'dias_disponiveis' => ['nullable', 'array'],
            'dias_disponiveis.*' => ['string', 'in:'.implode(',', \App\Models\Disponibilidade::DIAS)],
        ];
    }
}
