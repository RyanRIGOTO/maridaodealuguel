<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $table = 'chats';
    protected $fillable = ['agendamento_id', 'remetente_id', 'mensagem', 'proposta_horario_id', 'lido'];
    protected $casts = ['lido' => 'boolean'];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function remetente()
    {
        return $this->belongsTo(User::class, 'remetente_id');
    }

    public function proposta()
    {
        return $this->belongsTo(PropostaHorario::class, 'proposta_horario_id');
    }

    // O destinatário é a outra parte do agendamento (não é gravado — 3FN).
    public function getDestinatarioIdAttribute(): ?int
    {
        return $this->agendamento?->outraParteId($this->remetente_id);
    }
}
