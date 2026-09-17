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

    public static function log(string $acao, string $entidade, ?int $entidadeId = null, ?array $detalhes = null, ?int $usuarioId = null): self
    {
        return static::create([
            'usuario_id' => $usuarioId ?? auth()->id(),
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_id' => $entidadeId,
            'ip_address' => request()->ip(),
            'detalhes' => $detalhes,
            'created_at' => now(),
        ]);
    }
}