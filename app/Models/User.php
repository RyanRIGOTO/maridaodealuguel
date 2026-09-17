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
        'cpf_cnpj', 'termos_lgpd',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'termos_lgpd' => 'boolean',
        ];
    }

    public function clienteProfile()
    {
        return $this->hasOne(ClienteProfile::class, 'user_id');
    }

    public function prestadorProfile()
    {
        return $this->hasOne(PrestadorProfile::class, 'user_id');
    }

    public function servicos()
    {
        return $this->hasMany(Servico::class, 'prestador_id');
    }

    public function agendamentosComoCliente()
    {
        return $this->hasMany(Agendamento::class, 'cliente_id');
    }

    public function agendamentosComoPrestador()
    {
        return $this->hasMany(Agendamento::class, 'prestador_id');
    }

    public function avaliacoesComoCliente()
    {
        return $this->hasMany(Avaliacao::class, 'cliente_id');
    }

    public function avaliacoesComoPrestador()
    {
        return $this->hasMany(Avaliacao::class, 'prestador_id');
    }

    public function mensagensEnviadas()
    {
        return $this->hasMany(Chat::class, 'remetente_id');
    }

    public function mensagensRecebidas()
    {
        return $this->hasMany(Chat::class, 'destinatario_id');
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