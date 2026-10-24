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
            'area_atuacao' => $this->area_atuacao,
            'disponibilidades' => $this->whenLoaded('disponibilidades', fn () => $this->disponibilidades->map(fn ($d) => [
                'dia_semana' => $d->dia_semana,
                'hora_inicio' => substr($d->hora_inicio, 0, 5),
                'hora_fim' => substr($d->hora_fim, 0, 5),
            ])),
            'reputacao_media' => $this->reputacao_media, // calculada (RN10)
            'em_revisao' => $this->em_revisao,
        ];
    }
}
