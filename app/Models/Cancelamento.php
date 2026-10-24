<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cancelamento extends Model
{
    protected $table = 'cancelamentos';
    protected $fillable = ['agendamento_id', 'cancelado_por', 'multa'];
    protected $casts = ['multa' => 'decimal:2'];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }
}
