<?php

namespace App\Support;

class Documento
{
    public static function normalizar(string $documento): string
    {
        $documento = strtoupper(trim($documento));

        // tirando a pontuação e espaços,caso tenha digitado com máscara.
        if (preg_match('/\A(?:[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2}|[A-Z0-9]{2}\.[A-Z0-9]{3}\.[A-Z0-9]{3}\/[A-Z0-9]{4}-[0-9]{2})\z/', $documento)) {
            return str_replace(['.', '-', '/'], '', $documento);
        }

        return $documento;
    }

    public static function valido(string $documento): bool
    {
        $documento = self::normalizar($documento);

        if (preg_match('/\A([0-9])\1+\z/', $documento)) {
            return false;
        }

        if (preg_match('/\A[0-9]{11}\z/', $documento)) {
            $base = substr($documento, 0, 9);
            $base .= self::digito($base, range(10, 2));
            $base .= self::digito($base, range(11, 2));

            return $base === $documento;
        }

        if (preg_match('/\A[A-Z0-9]{12}[0-9]{2}\z/', $documento)) {
            $base = substr($documento, 0, 12);
            $base .= self::digito($base, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
            $base .= self::digito($base, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

            return $base === $documento;
        }

        return false;
    }

    private static function digito(string $base, array $pesos): int
    {
        $soma = 0;
        foreach ($pesos as $indice => $peso) {
            // ASCII - 48 atende tanto aos números quanto às letras do CNPJ.
            $soma += (ord($base[$indice]) - 48) * $peso;
        }

        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    }
}
