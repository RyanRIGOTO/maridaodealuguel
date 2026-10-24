<?php

namespace App\Models;

use App\Services\AvaliacaoService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrestadorProfile extends Model
{
    use HasFactory;

    protected $table = 'prestadores_profiles';
    protected $fillable = ['user_id', 'area_atuacao', 'em_revisao', 'abaixo_media_desde'];
    protected $casts = ['em_revisao' => 'boolean', 'abaixo_media_desde' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function disponibilidades()
    {
        return $this->hasMany(Disponibilidade::class, 'prestador_profile_id')->orderBy('dia_semana')->orderBy('hora_inicio');
    }

    /**
     * RN10: reputação = média das avaliações não moderadas. Não é gravada (é derivada);
     * sem avaliações, vale 5.
     */
    public function getReputacaoMediaAttribute(): float
    {
        return AvaliacaoService::media($this->user_id) ?? 5.00;
    }
}
