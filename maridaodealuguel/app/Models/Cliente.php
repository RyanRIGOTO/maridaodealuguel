<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'id',
        'name',
        'telefone',
        'email',
        'endereco',
        'data_nascimento',
        'status',    
        'password',
    ];

    
}
