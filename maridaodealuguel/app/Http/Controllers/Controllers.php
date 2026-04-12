<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientePrestador extends Model
{
    use HasFactory;

    protected $table = 'clientes_prestadores';   // nome da tabela no banco

    protected $fillable = [
        'name', 'email', 'password', // adicione outros campos que você tem
    ];
}