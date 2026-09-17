<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteProfile extends Model
{
    use HasFactory;

    protected $table = 'clientes_profiles';
    protected $fillable = ['user_id', 'endereco_completo', 'data_nascimento'];
    protected $casts = ['data_nascimento' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}