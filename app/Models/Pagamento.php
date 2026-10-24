<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    public const METODOS = ['pix' => 'Pix', 'cartao' => 'Cartão de crédito', 'boleto' => 'Boleto'];

    protected $table = 'pagamentos';
    protected $fillable = [
        'agendamento_id', 'tipo', 'valor', 'metodo',
        'status', 'gateway', 'transacao_id', 'pago_em',
    ];
    protected $casts = ['valor' => 'decimal:2', 'pago_em' => 'datetime'];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    // O pagador é o cliente do agendamento.
    public function pagador()
    {
        return $this->hasOneThrough(User::class, Agendamento::class, 'id', 'id', 'agendamento_id', 'cliente_id');
    }
}
