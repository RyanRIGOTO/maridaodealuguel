<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servico extends Model
{
    protected $table = 'servicos';

    protected $fillable = [
        'id',
        'nome',
        'descricao',
        'preco_sugerido',
        'categoria_id',
    ];
    public function categoria()
    {
        if ($this->categoria_id) {
            return $this->belongsTo(Categoria::class, 'categoria_id');
        }
        return null;
    }
}