<?php

namespace App\Http\Requests\Concerns;

use App\Rules\CpfCnpj;
use App\Rules\DocumentoUnico;
use App\Support\Documento;

/**
 * Pré-processamento comum aos cadastros de cliente e de prestador.
 * Padroniza documento, CEP e UF; o endereço é gravado em campos separados (tabela enderecos).
 */
trait NormalizaCadastro
{
    use RegrasEndereco;

    public const IDADE_MINIMA = 18;

    protected function prepareForValidation(): void
    {
        $documento = $this->input('cpf_cnpj');

        $this->merge([
            'cpf_cnpj' => is_string($documento) ? Documento::normalizar($documento) : $documento,
            ...$this->enderecoNormalizado(),
        ]);
    }

    /**
     * Regras e mensagens iguais nos dois cadastros.
     */
    protected function regrasComuns(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cpf_cnpj' => ['bail', 'required', 'string', new CpfCnpj, new DocumentoUnico], // RN2
            // Maior de idade (18 anos completos) e data plausível.
            'data_nascimento' => ['required', 'date', 'after:1900-01-01', 'before_or_equal:'.now()->subYears(self::IDADE_MINIMA)->toDateString()],
            // Endereço opcional no cadastro, mas, se começar a preencher, precisa estar completo.
            ...$this->regrasEndereco(obrigatorio: false),
            'termos_lgpd' => ['required', 'accepted'], // RN11
        ];
    }

    public function messages(): array
    {
        // Mensagens do cadastro em linguagem simples, dizendo o que corrigir.
        $jaTemConta = 'Se você já tem conta, entre pela tela de login ou use "Esqueci minha senha".';

        return [
            'name.required' => 'Informe seu nome completo.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'E-mail inválido. Confira se está no formato nome@exemplo.com.',
            // RN2: não afirma que o e-mail existe, mas orienta quem já tem conta.
            'email.unique' => 'Não foi possível usar este e-mail. '.$jaTemConta,
            'password.required' => 'Crie uma senha.',
            'password.min' => 'A senha precisa ter pelo menos 8 caracteres.',
            'password.confirmed' => 'As senhas não conferem. Digite a mesma senha nos dois campos.',
            'cpf_cnpj.required' => 'Informe seu CPF ou CNPJ.',
            'cpf_cnpj.string' => 'CPF ou CNPJ inválido. Confira os números digitados.',
            'data_nascimento.required' => 'Informe sua data de nascimento.',
            'data_nascimento.date' => 'Data de nascimento inválida.',
            'data_nascimento.after' => 'Data de nascimento inválida.',
            'data_nascimento.before_or_equal' => 'É preciso ter pelo menos '.self::IDADE_MINIMA.' anos para se cadastrar.',
            ...$this->mensagensEndereco(),
            'termos_lgpd.required' => 'Para criar a conta, aceite os Termos de Uso e a Política de Privacidade.',
            'termos_lgpd.accepted' => 'Para criar a conta, aceite os Termos de Uso e a Política de Privacidade.',
        ];
    }
}
