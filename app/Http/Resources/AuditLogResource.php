<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'acao' => $this->acao,
            'entidade' => $this->entidade,
            'entidade_id' => $this->entidade_id,
            'ip_address' => $this->ip_address,
            'detalhes' => $this->detalhes,
            'created_at' => $this->created_at,
            'usuario' => new UserResource($this->whenLoaded('usuario')),
        ];
    }
}
