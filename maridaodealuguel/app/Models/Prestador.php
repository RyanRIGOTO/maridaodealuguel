<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestador extends Model
{
    protected $table = 'prestadores';

    protected $fillable = [
        'id',
        'name',
        'telefone',
        'email',
        'endereco',
        'data_nascimento',
        'area_atuacao',
        'disponibilidade',
        'status',    
        'password',
    ];
}
