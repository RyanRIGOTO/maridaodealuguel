<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\RegrasEndereco;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Agendamento pela Web: o atendimento é no endereço do cadastro (endereco_opcao=cadastro)
 * ou num endereço novo digitado em campos separados (endereco_opcao=outro).
 */
class AgendarRequest extends FormRequest
{
    use RegrasEndereco;

    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if ($this->input('endereco_opcao') === 'outro') {
            $this->merge($this->enderecoNormalizado());
        }
    }

    public function rules(): array
    {
        $regras = [
            'servico_id' => ['required', 'exists:servicos,id'],
            'data' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            'endereco_opcao' => ['required', 'in:cadastro,outro'],
        ];

        if ($this->input('endereco_opcao') === 'outro') {
            return [...$regras, ...$this->regrasEndereco()];
        }

        return [...$regras, 'endereco_id' => ['required', Rule::exists('enderecos', 'id')->where('user_id', $this->user()->id)]];
    }

    public function messages(): array
    {
        return [
            ...$this->mensagensEndereco(),
            'endereco_opcao.required' => 'Escolha onde será o atendimento.',
            'endereco_id.required' => 'Escolha onde será o atendimento.',
            'endereco_id.exists' => 'Escolha um endereço válido para o atendimento.',
        ];
    }
}
