<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropostaHorario extends Model
{
    protected $table = 'propostas_horario';
    protected $fillable = ['agendamento_id', 'data_hora', 'status'];
    protected $casts = ['data_hora' => 'datetime'];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function mensagem()
    {
        return $this->hasOne(Chat::class, 'proposta_horario_id');
    }
}
