<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'cpf_cnpj' => $this->cpf_cnpj,
            'termos_lgpd' => $this->termos_lgpd,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'prestador_profile' => new PrestadorProfileResource($this->whenLoaded('prestadorProfile')),
            'cliente_profile' => new ClienteProfileResource($this->whenLoaded('clienteProfile')),
        ];
    }
}
