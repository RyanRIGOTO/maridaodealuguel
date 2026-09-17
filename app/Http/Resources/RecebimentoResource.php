<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecebimentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agendamento_id' => $this->agendamento_id,
            'valor_total' => $this->valor_total,
            'taxa_admin' => $this->taxa_admin,
            'valor_liquido_prestador' => $this->valor_liquido_prestador,
            'status_recebimento' => $this->status_recebimento,
            'data_liberacao' => $this->data_liberacao,
            'created_at' => $this->created_at,
            'agendamento' => new AgendamentoResource($this->whenLoaded('agendamento')),
        ];
    }
}
