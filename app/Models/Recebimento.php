<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recebimento extends Model
{
    use HasFactory;

    protected $table = 'recebimentos';
    protected $fillable = [
        'agendamento_id', 'tipo', 'valor_total', 'percentual_taxa',
        'status_recebimento', 'data_liberacao',
    ];
    // Calculados (não gravados), mas incluídos quando o recebimento vira JSON na API.
    protected $appends = ['taxa_admin', 'valor_liquido_prestador'];
    protected $casts = [
        'valor_total' => 'decimal:2', 'percentual_taxa' => 'decimal:2', 'data_liberacao' => 'datetime',
    ];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    // RN7: taxa e líquido são derivados de valor_total x percentual_taxa (não gravados).
    public function getTaxaAdminAttribute(): float
    {
        return round((float) $this->valor_total * (float) $this->percentual_taxa / 100, 2);
    }

    public function getValorLiquidoPrestadorAttribute(): float
    {
        return round((float) $this->valor_total - $this->taxa_admin, 2);
    }

    /** Expressões SQL equivalentes, para somar no banco (relatórios). */
    public const SQL_TAXA = 'ROUND(valor_total * percentual_taxa / 100, 2)';
    public const SQL_LIQUIDO = 'valor_total - ROUND(valor_total * percentual_taxa / 100, 2)';
}