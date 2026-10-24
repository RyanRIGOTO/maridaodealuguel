<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agendamento extends Model
{
    use HasFactory;

    protected $table = 'agendamentos';
    protected $fillable = ['cliente_id', 'servico_id', 'endereco_id', 'data_hora', 'status', 'preco_acordado'];
    protected $casts = ['data_hora' => 'datetime', 'preco_acordado' => 'decimal:2'];

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class, 'servico_id');
    }

    // O prestador é o dono do serviço (não há prestador_id no agendamento — 3FN).
    public function prestador()
    {
        return $this->hasOneThrough(User::class, Servico::class, 'id', 'id', 'servico_id', 'prestador_id');
    }

    public function getPrestadorIdAttribute(): ?int
    {
        return $this->servico?->prestador_id;
    }

    public function endereco()
    {
        return $this->belongsTo(Endereco::class, 'endereco_id');
    }

    public function cancelamento()
    {
        return $this->hasOne(Cancelamento::class, 'agendamento_id');
    }

    public function propostasHorario()
    {
        return $this->hasMany(PropostaHorario::class, 'agendamento_id');
    }

    public function avaliacao()
    {
        return $this->hasOne(Avaliacao::class, 'agendamento_id');
    }

    // Pagamento do serviço concluído (a multa de cancelamento é outro recebimento).
    public function recebimento()
    {
        return $this->hasOne(Recebimento::class, 'agendamento_id')->where('tipo', 'servico');
    }

    public function recebimentos()
    {
        return $this->hasMany(Recebimento::class, 'agendamento_id');
    }

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class, 'agendamento_id');
    }

    public function chat()
    {
        return $this->hasMany(Chat::class, 'agendamento_id');
    }

    /** Agendamentos dos serviços de um prestador. */
    public function scopeDoPrestador(Builder $query, int $prestadorId): Builder
    {
        return $query->whereHas('servico', fn ($q) => $q->where('prestador_id', $prestadorId));
    }

    /** Agendamentos em que o usuário é o cliente ou o prestador. */
    public function scopeDoParticipante(Builder $query, int $userId): Builder
    {
        return $query->where(fn ($q) => $q->where('cliente_id', $userId)
            ->orWhereHas('servico', fn ($qq) => $qq->where('prestador_id', $userId)));
    }

    public function participa(int $userId): bool
    {
        return $this->cliente_id === $userId || $this->prestador_id === $userId;
    }

    /** A outra parte do agendamento, vista por quem está logado. */
    public function outraParteId(int $userId): int
    {
        return $this->cliente_id === $userId ? $this->prestador_id : $this->cliente_id;
    }
}
