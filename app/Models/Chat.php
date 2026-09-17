<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $table = 'chats';
    protected $fillable = [
        'agendamento_id', 'remetente_id', 'destinatario_id',
        'mensagem', 'data_hora', 'lido',
    ];
    protected $casts = ['data_hora' => 'datetime', 'lido' => 'boolean'];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function remetente()
    {
        return $this->belongsTo(User::class, 'remetente_id');
    }

    public function destinatario()
    {
        return $this->belongsTo(User::class, 'destinatario_id');
    }
}