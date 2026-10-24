<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';
    public $timestamps = false;
    protected $fillable = [
        'usuario_id', 'acao', 'entidade', 'entidade_id', 'ip_address', 'detalhes', 'created_at',
    ];
    protected $casts = ['entidade_id' => 'integer', 'detalhes' => 'array'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
