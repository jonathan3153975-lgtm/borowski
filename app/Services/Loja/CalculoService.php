<?php

declare(strict_types=1);

namespace App\Services\Loja;

use InvalidArgumentException;

class CalculoService
{
    public const MAX_MONEY_MILESIMOS = 999999999999;

    public function paraMilesimos(mixed $valor): int
    {
        $texto = trim((string) $valor);

        if ($texto === '') {
            return 0;
        }

        $texto = str_replace(['R$', ' '], '', $texto);
        $negative = str_starts_with($texto, '-');
        $texto = ltrim($texto, '-');

        if (str_contains($texto, ',') && str_contains($texto, '.')) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        } elseif (str_contains($texto, ',')) {
            $texto = str_replace(',', '.', $texto);
        }

        if (preg_match('/^\d+(\.\d{1,3})?$/', $texto) !== 1) {
            throw new InvalidArgumentException('Valor monetário inválido.');
        }

        [$inteiro, $decimal] = array_pad(explode('.', $texto, 2), 2, '');
        $decimal = str_pad(substr($decimal, 0, 3), 3, '0');

        $milesimos = ((int) $inteiro) * 1000 + (int) $decimal;

        return $negative ? -$milesimos : $milesimos;
    }

    public function milesimosParaDecimal(int $milesimos): string
    {
        $negative = $milesimos < 0;
        $milesimos = abs($milesimos);

        $value = intdiv($milesimos, 1000) . '.' . str_pad(
            (string) ($milesimos % 1000),
            3,
            '0',
            STR_PAD_LEFT
        );

        return ($negative ? '-' : '') . $value;
    }

    public function calcularRateio(int $totalAdicionaisMilesimos, array $itens): array
    {
        $totalAdicionaisMilesimos = max(0, $totalAdicionaisMilesimos);

        $preparados = [];

        foreach ($itens as $item) {
            $id = (int) $item['id'];
            $quantidade = max(1, (int) $item['quantidade']);
            $base = max(0, $this->paraMilesimos($item['custo_unitario_base'] ?? 0));
            $subtotal = $base * $quantidade;

            $preparados[] = [
                'id' => $id,
                'quantidade' => $quantidade,
                'base' => $base,
                'subtotal' => $subtotal,
            ];
        }

        if ($preparados === []) {
            return [];
        }

        $totalSubtotal = array_sum(array_column($preparados, 'subtotal'));
        $totalQuantidade = array_sum(array_column($preparados, 'quantidade'));
        $parcelas = [];

        if ($totalSubtotal > 0) {
            foreach ($preparados as $preparado) {
                $parcelas[$preparado['id']] = $this->mulDiv(
                    $totalAdicionaisMilesimos,
                    $preparado['subtotal'],
                    $totalSubtotal
                );
            }

            $ordenados = $preparados;
            usort($ordenados, static fn (array $a, array $b): int =>
                ($b['subtotal'] <=> $a['subtotal']) ?: ($a['id'] <=> $b['id']));
        } else {
            foreach ($preparados as $preparado) {
                $parcelas[$preparado['id']] = $this->mulDiv(
                    $totalAdicionaisMilesimos,
                    $preparado['quantidade'],
                    $totalQuantidade
                );
            }

            $ordenados = $preparados;
            usort($ordenados, static fn (array $a, array $b): int =>
                ($b['quantidade'] <=> $a['quantidade']) ?: ($a['id'] <=> $b['id']));
        }

        $residuo = $totalAdicionaisMilesimos - array_sum($parcelas);
        $indice = 0;
        $quantidadeItens = count($ordenados);

        while ($residuo > 0 && $quantidadeItens > 0) {
            $parcelas[$ordenados[$indice % $quantidadeItens]['id']]++;
            $residuo--;
            $indice++;
        }

        $resultado = [];

        foreach ($preparados as $preparado) {
            $parcela = $parcelas[$preparado['id']];
            $totalReal = $preparado['subtotal'] + $parcela;
            $unitarioReal = (int) round($totalReal / $preparado['quantidade']);

            $resultado[$preparado['id']] = [
                'quantidade' => $preparado['quantidade'],
                'custo_unitario_base' => $this->milesimosParaDecimal($preparado['base']),
                'custo_adicional_rateado' => $this->milesimosParaDecimal($parcela),
                'custo_total_real' => $this->milesimosParaDecimal($totalReal),
                'custo_unitario_real' => $this->milesimosParaDecimal($unitarioReal),
                'subtotal_base_milesimos' => $preparado['subtotal'],
                'rateado_milesimos' => $parcela,
                'total_real_milesimos' => $totalReal,
            ];
        }

        return $resultado;
    }

    public function calcularItemPrevisao(int $quantidade, int $baseMilesimos): array
    {
        $quantidade = max(1, $quantidade);
        $baseMilesimos = max(0, $baseMilesimos);

        return [
            'quantidade' => $quantidade,
            'custo_unitario_base' => $this->milesimosParaDecimal($baseMilesimos),
            'custo_adicional_rateado' => '0.000',
            'custo_total_real' => $this->milesimosParaDecimal($baseMilesimos * $quantidade),
            'custo_unitario_real' => $this->milesimosParaDecimal($baseMilesimos),
        ];
    }

    private function mulDiv(int $a, int $b, int $c): int
    {
        if ($c === 0) {
            return 0;
        }

        if (extension_loaded('bcmath')) {
            return (int) bcdiv(bcmul((string) $a, (string) $b, 0), (string) $c, 0);
        }

        return intdiv($a * $b, $c);
    }
}
