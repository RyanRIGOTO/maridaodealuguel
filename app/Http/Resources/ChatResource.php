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
            'data_hora' => $this->created_at,
            'lido' => $this->lido,
            'remetente' => new UserResource($this->whenLoaded('remetente')),
            'proposta_horario' => $this->whenLoaded('proposta', fn () => $this->proposta ? [
                'id' => $this->proposta->id,
                'data_hora' => $this->proposta->data_hora,
                'status' => $this->proposta->status,
            ] : null),
        ];
    }
}
