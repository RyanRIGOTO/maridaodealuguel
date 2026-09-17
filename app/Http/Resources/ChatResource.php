<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agendamento_id' => $this->agendamento_id,
            'remetente_id' => $this->remetente_id,
            'destinatario_id' => $this->destinatario_id,
            'mensagem' => $this->mensagem,
            'data_hora' => $this->data_hora,
            'lido' => $this->lido,
            'remetente' => new UserResource($this->whenLoaded('remetente')),
            'destinatario' => new UserResource($this->whenLoaded('destinatario')),
        ];
    }
}
