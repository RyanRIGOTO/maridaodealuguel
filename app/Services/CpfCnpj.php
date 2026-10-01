<?php

namespace App\Services;

use App\Support\Documento;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Documento::valido($value)) {
            $fail('Informe um CPF ou CNPJ válido, ou tente outro CPF/CNPJ.');
        }
    }
}
