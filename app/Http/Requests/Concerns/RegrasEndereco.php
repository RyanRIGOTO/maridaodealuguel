<?php

namespace App\Http\Requests\Concerns;

/**
 * Validação dos campos de endereço (cadastro e "outro endereço" no agendamento).
 */
trait RegrasEndereco
{
    /** Tira a máscara do CEP e deixa a UF em maiúsculas antes de validar. */
    protected function enderecoNormalizado(): array
    {
        return [
            'cep' => preg_replace('/\D/', '', (string) $this->input('cep', '')),
            'uf' => strtoupper(trim((string) $this->input('uf', ''))),
        ];
    }

    /**
     * @param  bool  $obrigatorio  false = endereço opcional, mas completo se algum campo for preenchido
     */
    protected function regrasEndereco(bool $obrigatorio = true): array
    {
        $exigir = $obrigatorio ? 'required' : 'required_with:logradouro,numero,bairro,cidade,cep';

        return [
            'cep' => ['nullable', 'regex:/^\d{8}$/'],
            'logradouro' => [$exigir, 'nullable', 'string', 'max:75'],
            'numero' => [$exigir, 'nullable', 'string', 'max:10'],
            'complemento' => ['nullable', 'string', 'max:35'],
            'bairro' => [$exigir, 'nullable', 'string', 'max:45'],
            'cidade' => [$exigir, 'nullable', 'string', 'max:45'],
            'uf' => [$exigir, 'nullable', 'string', 'size:2'],
        ];
    }

    protected function mensagensEndereco(): array
    {
        return [
            'cep.regex' => 'O CEP deve ter 8 números (ex.: 87500-000).',
            'uf.size' => 'A UF deve ter 2 letras (ex.: PR).',
            'logradouro.required_with' => 'Informe a rua do endereço.',
            'numero.required_with' => 'Informe o número do endereço.',
            'bairro.required_with' => 'Informe o bairro.',
            'cidade.required_with' => 'Informe a cidade.',
            'uf.required_with' => 'Informe a UF (ex.: PR).',
        ];
    }
}
