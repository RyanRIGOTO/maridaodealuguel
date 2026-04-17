<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agendamento extends Model
{
    protected $table = 'agendamentos';

    protected $fillable = [
        'id',
        'prestador_id',
        'cliente_id',
        'servico_id',
        'data_hora',
        'descricao',
    ];


    public function servico()
    {
        if ($this->servico_id) {
            return $this->belongsTo(Servico::class, 'servico_id');
        }
        return null; // Retorna null se não houver servico_id
    }
}
