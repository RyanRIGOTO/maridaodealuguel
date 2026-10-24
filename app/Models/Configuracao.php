<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracao extends Model
{
    public const TAXA_PLATAFORMA = 'taxa_plataforma';
    public const MULTA_CANCELAMENTO = 'multa_cancelamento';

    // Usados só se o registro não existir no banco (ex.: migration ainda não rodada).
    public const TAXA_PLATAFORMA_PADRAO = 5.00;
    public const MULTA_CANCELAMENTO_PADRAO = 50.00;

    protected $table = 'configuracoes';
    protected $fillable = ['chave', 'valor'];

    public static function valor(string $chave, ?string $padrao = null): ?string
    {
        return static::where('chave', $chave)->value('valor') ?? $padrao;
    }

    public static function definir(string $chave, string $valor): void
    {
        static::updateOrCreate(['chave' => $chave], ['valor' => $valor]);
    }

    /**
     * RN7: percentual (0 a 100) que a plataforma retém de cada serviço concluído.
     */
    public static function taxaPlataforma(): float
    {
        return (float) static::valor(self::TAXA_PLATAFORMA, (string) self::TAXA_PLATAFORMA_PADRAO);
    }

    /**
     * RN4: percentual (0 a 100) do valor do serviço cobrado do cliente que cancela com menos de 24h.
     */
    public static function multaCancelamento(): float
    {
        return (float) static::valor(self::MULTA_CANCELAMENTO, (string) self::MULTA_CANCELAMENTO_PADRAO);
    }
}
