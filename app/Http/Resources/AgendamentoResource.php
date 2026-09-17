<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgendamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'prestador_id' => $this->prestador_id,
            'servico_id' => $this->servico_id,
            'data_hora' => $this->data_hora,
            'endereco_servico' => $this->endereco_servico,
            'status' => $this->status,
            'preco_acordado' => $this->preco_acordado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'cliente' => new UserResource($this->whenLoaded('cliente')),
            'prestador' => new UserResource($this->whenLoaded('prestador')),
            'servico' => new ServicoResource($this->whenLoaded('servico')),
            'avaliacao' => new AvaliacaoResource($this->whenLoaded('avaliacao')),
            'recebimento' => new RecebimentoResource($this->whenLoaded('recebimento')),
            'pendente_avaliacao' => $this->when(
                $this->status === 'concluido' && $this->resource->relationLoaded('avaliacao'),
                fn() => $this->avaliacao === null
            ),
        ];
    }
}
