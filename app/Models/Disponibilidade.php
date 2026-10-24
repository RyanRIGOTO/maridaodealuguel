<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disponibilidade extends Model
{
    public const DIAS = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sab'];

    protected $table = 'disponibilidades';
    protected $fillable = ['prestador_profile_id', 'dia_semana', 'hora_inicio', 'hora_fim'];
    protected $casts = ['dia_semana' => 'integer'];

    public function prestadorProfile()
    {
        return $this->belongsTo(PrestadorProfile::class, 'prestador_profile_id');
    }
}
