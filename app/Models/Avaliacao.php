<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avaliacao extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes';
    protected $fillable = [
        'agendamento_id', 'cliente_id', 'prestador_id',
        'nota', 'comentario', 'data_avaliacao', 'moderada',
    ];
    protected $casts = ['nota' => 'integer', 'data_avaliacao' => 'datetime', 'moderada' => 'boolean'];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function prestador()
    {
        return $this->belongsTo(User::class, 'prestador_id');
    }
}