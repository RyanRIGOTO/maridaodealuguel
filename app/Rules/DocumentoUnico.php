<?php

namespace App\Rules;

use App\Models\User;
use App\Support\Documento;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DocumentoUnico implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Também compara os cadastros antigos, que podem ter sido salvos com máscara.
        $existe = User::whereRaw(
            "UPPER(REPLACE(REPLACE(REPLACE(TRIM(cpf_cnpj), '.', ''), '-', ''), '/', '')) = ?",
            [Documento::normalizar($value)]
        )->exists();

        if ($existe) {
            // RN2: não afirma que o documento existe, mas orienta quem já tem conta.
            $fail('Não foi possível usar este CPF/CNPJ. Se você já tem conta, entre pela tela de login ou use "Esqueci minha senha".');
        }
    }
}
