<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servico extends Model
{
    use HasFactory;

    protected $table = 'servicos';
    protected $fillable = [
        'categoria_id', 'prestador_id', 'nome', 'descricao',
        'preco_sugerido', 'status',
    ];
    protected $casts = ['preco_sugerido' => 'decimal:2'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function prestador()
    {
        return $this->belongsTo(User::class, 'prestador_id');
    }

    public function agendamentos()
    {
        return $this->hasMany(Agendamento::class, 'servico_id');
    }
}