<?php

namespace App\Http\Requests;

use App\Rules\CpfCnpj;
use App\Rules\DocumentoUnico;
use App\Support\Documento;
use Illuminate\Foundation\Http\FormRequest;

class RegisterPrestadorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /**
     * mesma lógica do RegisterClienteRequest, mas para prestadores.
     * aqui estou fazendo a conversão dos campos de endereço para um formato mais padronizado antes da validação.
     */
    protected function prepareForValidation(): void
    {
        $logradouro = trim((string) $this->input('logradouro', ''));
        $numero = trim((string) $this->input('numero', ''));
        $complemento = trim((string) $this->input('complemento', ''));
        $bairro = trim((string) $this->input('bairro', ''));
        $cidade = trim((string) $this->input('cidade', ''));
        $uf = strtoupper(trim((string) $this->input('uf', '')));
        $cep = preg_replace('/\D/', '', (string) $this->input('cep', ''));

        $linhaPrincipal = implode(', ', array_filter([$logradouro, $numero], fn ($valor) => $valor !== ''));
        $cidadeUf = implode('/', array_filter([$cidade, $uf], fn ($valor) => $valor !== ''));
        $endereco = array_filter([
            $linhaPrincipal,
            $complemento,
            $bairro,
            $cidadeUf,
            $cep ? 'CEP '.$cep : '',
        ], fn ($valor) => $valor !== '');

        $documento = $this->input('cpf_cnpj');

        $this->merge([
            'cpf_cnpj' => is_string($documento) ? Documento::normalizar($documento) : $documento,
            'cep' => $cep,
            'uf' => $uf,
            'endereco_completo' => $endereco ? implode(' - ', $endereco) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cpf_cnpj' => ['bail', 'required', 'string', new CpfCnpj, new DocumentoUnico], // RN2
            'data_nascimento' => ['required', 'date'],
            'cep' => ['nullable', 'regex:/^\d{8}$/'],
            'logradouro' => ['nullable', 'string', 'max:75'],
            'numero' => ['nullable', 'string', 'max:10'],
            'complemento' => ['nullable', 'string', 'max:35'],
            'bairro' => ['nullable', 'string', 'max:45'],
            'cidade' => ['nullable', 'string', 'max:45'],
            'uf' => ['nullable', 'string', 'size:2'],
            'endereco_completo' => ['nullable', 'string', 'max:255'],
            'area_atuacao' => ['nullable', 'string', 'max:100'],
            'disponibilidade_horarios' => ['nullable', 'array'],
            'termos_lgpd' => ['required', 'accepted'], // RN11
        ];
    }

    public function messages(): array
    {
        return [
            'termos_lgpd.accepted' => 'Você deve aceitar os termos de uso e política de privacidade (LGPD).',
            'cpf_cnpj.required' => 'Informe seu CPF ou CNPJ.',
            'cpf_cnpj.string' => 'Informe um CPF ou CNPJ válido.',
            'email.unique' => 'Este e-mail já está cadastrado na plataforma.',
        ];
    }
}
