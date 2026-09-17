<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'categoria_id' => $this->categoria_id,
            'prestador_id' => $this->prestador_id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'preco_sugerido' => $this->preco_sugerido,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'categoria' => new CategoriaResource($this->whenLoaded('categoria')),
            'prestador' => new UserResource($this->whenLoaded('prestador')),
        ];
    }
}
