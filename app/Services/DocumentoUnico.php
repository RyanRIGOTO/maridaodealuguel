<?php

namespace App\Services;

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
            $fail('Informe um CPF ou CNPJ válido, ou tente outro CPF/CNPJ.');
        }
    }
}
