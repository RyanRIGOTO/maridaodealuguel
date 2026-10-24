<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Endereco extends Model
{
    use HasFactory;

    protected $table = 'enderecos';
    protected $fillable = ['user_id', 'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf', 'principal'];
    protected $casts = ['principal' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Endereço em uma linha, para exibir: "Rua X, 10 - Apto 2 - Centro - Cidade/UF - CEP 87500-000".
     */
    public function getCompletoAttribute(): string
    {
        $cep = $this->cep ? 'CEP '.substr($this->cep, 0, 5).'-'.substr($this->cep, 5) : null;

        return implode(' - ', array_filter([
            "{$this->logradouro}, {$this->numero}",
            $this->complemento,
            $this->bairro,
            "{$this->cidade}/{$this->uf}",
            $cep,
        ]));
    }
}
