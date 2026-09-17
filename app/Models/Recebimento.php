<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recebimento extends Model
{
    use HasFactory;

    protected $table = 'recebimentos';
    protected $fillable = [
        'agendamento_id', 'valor_total', 'taxa_admin',
        'valor_liquido_prestador', 'status_recebimento', 'data_liberacao',
    ];
    protected $casts = [
        'valor_total' => 'decimal:2', 'taxa_admin' => 'decimal:2',
        'valor_liquido_prestador' => 'decimal:2', 'data_liberacao' => 'datetime',
    ];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }
}