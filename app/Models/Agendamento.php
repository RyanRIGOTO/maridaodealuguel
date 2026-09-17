<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agendamento extends Model
{
    use HasFactory;

    protected $table = 'agendamentos';
    protected $fillable = [
        'cliente_id', 'prestador_id', 'servico_id', 'data_hora',
        'endereco_servico', 'status', 'preco_acordado',
    ];
    protected $casts = ['data_hora' => 'datetime', 'preco_acordado' => 'decimal:2'];

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function prestador()
    {
        return $this->belongsTo(User::class, 'prestador_id');
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class, 'servico_id');
    }

    public function avaliacao()
    {
        return $this->hasOne(Avaliacao::class, 'agendamento_id');
    }

    public function recebimento()
    {
        return $this->hasOne(Recebimento::class, 'agendamento_id');
    }

    public function chat()
    {
        return $this->hasMany(Chat::class, 'agendamento_id');
    }
}