<?php

namespace Tests\Unit;

use App\Support\Documento;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DocumentoTest extends TestCase
{
    #[DataProvider('documentos')]
    public function test_confere_formato_e_digitos_verificadores(string $documento, bool $valido): void
    {
        $this->assertSame($valido, Documento::valido($documento));
    }

    public static function documentos(): array
    {
        return [
            ['529.982.247-25', true],
            ['52998224725', true],
            ['012.345.678-90', true],
            ['11.222.333/0001-81', true],
            ['11222333000181', true],
            ['04.252.011/0001-10', true],
            ['12.ABC.345/01DE-35', true],
            [' 12.abc.345/01de-35 ', true],
            ['12ABC34501DE35', true],
            ['52998224724', false],
            ['52998224715', false],
            ['11222333000180', false],
            ['11222333000171', false],
            ['12ABC34501DE34', false],
            ['12ABC34501DE25', false],
            ['12ABC34501DE3A', false],
            ['', false],
            ['123', false],
            ['529982247250', false],
            ['112223330001810', false],
            ['529.982247-25', false],
            ['52998224725abc', false],
            ['529 98224725', false],
            ['52998224725!', false],
            ['５2998224725', false],
            ...array_map(fn ($digito) => [str_repeat((string) $digito, 11), false], range(0, 9)),
            ...array_map(fn ($digito) => [str_repeat((string) $digito, 14), false], range(0, 9)),
        ];
    }
}
