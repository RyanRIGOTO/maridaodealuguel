<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';
    protected $fillable = ['nome_categoria', 'status'];

    public function servicos()
    {
        return $this->hasMany(Servico::class, 'categoria_id');
    }
}