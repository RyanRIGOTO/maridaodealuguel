<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrestadorProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'endereco_completo' => $this->endereco_completo,
            'data_nascimento' => $this->data_nascimento,
            'area_atuacao' => $this->area_atuacao,
            'disponibilidade_horarios' => $this->disponibilidade_horarios,
            'reputacao_media' => $this->reputacao_media,
            'em_revisao' => $this->em_revisao,
        ];
    }
}
