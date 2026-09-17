<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrestadorProfile extends Model
{
    use HasFactory;

    protected $table = 'prestadores_profiles';
    protected $fillable = [
        'user_id', 'endereco_completo', 'data_nascimento',
        'area_atuacao', 'disponibilidade_horarios',
        'reputacao_media', 'em_revisao',
    ];
    protected $casts = [
        'data_nascimento' => 'date', 'disponibilidade_horarios' => 'array',
        'reputacao_media' => 'decimal:2', 'em_revisao' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}