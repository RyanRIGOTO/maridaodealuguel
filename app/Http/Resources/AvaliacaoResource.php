<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvaliacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agendamento_id' => $this->agendamento_id,
            'cliente_id' => $this->cliente_id,
            'prestador_id' => $this->prestador_id,
            'nota' => $this->nota,
            'comentario' => $this->comentario,
            'data_avaliacao' => $this->data_avaliacao,
            'moderada' => $this->moderada,
            'created_at' => $this->created_at,
            'cliente' => new UserResource($this->whenLoaded('cliente')),
        ];
    }
}
