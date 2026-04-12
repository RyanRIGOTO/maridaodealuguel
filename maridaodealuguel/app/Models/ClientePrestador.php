<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientePrestador extends Model
{
    use HasFactory;

    // 🔥 NOME CORRETO DA TABELA
    protected $table = 'clientes_prestadores';

    protected $fillable = [
        'name', 
        'email', 
        'password',
        'email_verified_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}