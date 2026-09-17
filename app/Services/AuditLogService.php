<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * RN14: Toda operacao critica deve gravar em audit_logs
     */
    public static function log(string $acao, string $entidade, ?int $entidadeId = null, ?array $detalhes = null): AuditLog
    {
        return AuditLog::create([
            'usuario_id' => auth()->id(),
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_id' => $entidadeId,
            'ip_address' => request()->ip(),
            'detalhes' => $detalhes,
            'created_at' => now(),
        ]);
    }

    /**
     * Filtra logs de auditoria por intervalo de datas, usuário, ação e entidade opcionais.
     */
    public static function list(array $filters = [])
    {
        $query = AuditLog::with('usuario:id,name,email');

        if (isset($filters['acao'])) {
            $query->where('acao', $filters['acao']);
        }
        if (isset($filters['entidade'])) {
            $query->where('entidade', $filters['entidade']);
        }
        if (isset($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 50);
    }
}
