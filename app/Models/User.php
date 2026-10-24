<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'status',
        'cpf_cnpj', 'data_nascimento', 'termos_aceitos_em',
    ];

    protected $hidden = ['password', 'remember_token'];

    // Mesmos padrões da migration, para o model já nascer com eles antes de ir ao banco.
    protected $attributes = [
        'role' => 'cliente',
        'status' => 'ativo',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'termos_aceitos_em' => 'datetime',
            'data_nascimento' => 'date',
            'password' => 'hashed',
        ];
    }

    public function prestadorProfile()
    {
        return $this->hasOne(PrestadorProfile::class, 'user_id');
    }

    public function enderecos()
    {
        return $this->hasMany(Endereco::class, 'user_id');
    }

    public function enderecoPrincipal()
    {
        return $this->hasOne(Endereco::class, 'user_id')->where('principal', true);
    }

    public function servicos()
    {
        return $this->hasMany(Servico::class, 'prestador_id');
    }

    public function agendamentosComoCliente()
    {
        return $this->hasMany(Agendamento::class, 'cliente_id');
    }

    // O prestador é dono dos serviços; os agendamentos chegam por eles.
    public function agendamentosComoPrestador()
    {
        return $this->hasManyThrough(Agendamento::class, Servico::class, 'prestador_id', 'servico_id');
    }

    public function mensagensEnviadas()
    {
        return $this->hasMany(Chat::class, 'remetente_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPrestador(): bool
    {
        return $this->role === 'prestador';
    }

    public function isCliente(): bool
    {
        return $this->role === 'cliente';
    }

    public function isActive(): bool
    {
        return $this->status === 'ativo';
    }
}
