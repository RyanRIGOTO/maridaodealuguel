<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestador extends Model
{
    protected $table = 'prestadors';

    protected $fillable = [
        'id',
        'name',
        'telefone',
        'email',
        'endereco',
        'data_nascimento',
        'area_atuacao',
        'disponibilidade',
        'status',    
        'password',
    ];

    // Getters
    public function getId()
    {
        return $this->attributes['id'] ?? null;
    }

    public function getName()
    {
        return $this->attributes['name'] ?? null;
    }

    public function getTelefone()
    {
        return $this->attributes['telefone'] ?? null;
    }

    public function getEmail()
    {
        return $this->attributes['email'] ?? null;
    }

    public function getEndereco()
    {
        return $this->attributes['endereco'] ?? null;
    }

    public function getDataNascimento()
    {
        return $this->attributes['data_nascimento'] ?? null;
    }

    public function getAreaAtuacao()
    {
        return $this->attributes['area_atuacao'] ?? null;
    }

    public function getDisponibilidade()
    {
        return $this->attributes['disponibilidade'] ?? null;
    }

    public function getStatus()
    {
        return $this->attributes['status'] ?? null;
    }

    public function getPassword()
    {
        return $this->attributes['password'] ?? null;
    }

    // Setters
    public function setId($id)
    {
        $this->attributes['id'] = $id;
        return $this;
    }

    public function setName($name)
    {
        $this->attributes['name'] = $name;
        return $this;
    }

    public function setTelefone($telefone)
    {
        $this->attributes['telefone'] = $telefone;
        return $this;
    }

    public function setEmail($email)
    {
        $this->attributes['email'] = $email;
        return $this;
    }

    public function setEndereco($endereco)
    {
        $this->attributes['endereco'] = $endereco;
        return $this;
    }

    public function setDataNascimento($data_nascimento)
    {
        $this->attributes['data_nascimento'] = $data_nascimento;
        return $this;
    }

    public function setAreaAtuacao($area_atuacao)
    {
        $this->attributes['area_atuacao'] = $area_atuacao;
        return $this;
    }

    public function setDisponibilidade($disponibilidade)
    {
        $this->attributes['disponibilidade'] = $disponibilidade;
        return $this;
    }

    public function setStatus($status)
    {
        $this->attributes['status'] = $status;
        return $this;
    }

    public function setPassword($password)
    {
        $this->attributes['password'] = $password;
        return $this;
    }
}
