<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Serviços que um cliente pode contratar agora: serviço, categoria e prestador ativos,
     * e prestador fora de revisão por baixa reputação (RN10).
     */
    public function scopeContratavel(Builder $query): Builder
    {
        return $query->where('status', 'ativo')
            ->whereHas('categoria', fn ($q) => $q->where('status', 'ativo'))
            ->whereHas('prestador', fn ($q) => $q->where('status', 'ativo'))
            ->whereDoesntHave('prestador.prestadorProfile', fn ($q) => $q->where('em_revisao', true));
    }

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