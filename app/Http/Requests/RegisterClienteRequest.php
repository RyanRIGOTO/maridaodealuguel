<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaCadastro;
use Illuminate\Foundation\Http\FormRequest;

class RegisterClienteRequest extends FormRequest
{
    use NormalizaCadastro;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return $this->regrasComuns();
    }
}
