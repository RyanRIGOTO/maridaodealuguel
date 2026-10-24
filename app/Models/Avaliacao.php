<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avaliacao extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes';
    protected $fillable = ['agendamento_id', 'nota', 'comentario', 'moderada'];
    protected $casts = ['nota' => 'integer', 'moderada' => 'boolean'];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    // Cliente e prestador vêm do agendamento (3FN).
    public function cliente()
    {
        return $this->hasOneThrough(User::class, Agendamento::class, 'id', 'id', 'agendamento_id', 'cliente_id');
    }

    public function getPrestadorAttribute(): ?User
    {
        return $this->agendamento?->servico?->prestador;
    }

    public function getPrestadorIdAttribute(): ?int
    {
        return $this->agendamento?->servico?->prestador_id;
    }

    public function scopeDoPrestador(Builder $query, int $prestadorId): Builder
    {
        return $query->whereHas('agendamento.servico', fn ($q) => $q->where('prestador_id', $prestadorId));
    }
}
