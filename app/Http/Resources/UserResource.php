<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // LGPD: dados pessoais só para o próprio usuário ou para o administrador.
        $dadosPessoais = static::podeVerDadosPessoais($request, $this->id);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($dadosPessoais, $this->email),
            'phone' => $this->when($dadosPessoais, $this->phone),
            'role' => $this->role,
            'status' => $this->status,
            'cpf_cnpj' => $this->when($dadosPessoais, $this->cpf_cnpj),
            'data_nascimento' => $this->when($dadosPessoais, fn () => $this->data_nascimento?->toDateString()),
            'termos_aceitos_em' => $this->when($dadosPessoais, $this->termos_aceitos_em),
            'email_verified_at' => $this->when($dadosPessoais, $this->email_verified_at),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'prestador_profile' => new PrestadorProfileResource($this->whenLoaded('prestadorProfile')),
            'endereco_principal' => $this->when($dadosPessoais, fn () => new EnderecoResource($this->whenLoaded('enderecoPrincipal'))),
        ];
    }

    public static function podeVerDadosPessoais(Request $request, ?int $donoId): bool
    {
        $usuario = $request->user();

        return $usuario !== null && ($usuario->id === $donoId || $usuario->isAdmin());
    }
}
