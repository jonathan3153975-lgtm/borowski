<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script deve ser executado via linha de comando.\n");
}

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Services\Loja\CalculoService;

$calculo = new CalculoService();
$falhas = 0;
$testes = 0;

function verificar(string $descricao, mixed $esperado, mixed $obtido): void
{
    global $falhas, $testes;

    $testes++;

    if ($esperado === $obtido) {
        echo "PASS: {$descricao}\n";
        return;
    }

    $falhas++;
    echo "FAIL: {$descricao}\n";
    echo '  Esperado: ' . var_export($esperado, true) . "\n";
    echo '  Obtido:   ' . var_export($obtido, true) . "\n";
}

// 1. Rateio proporcional ao subtotal, com 3 casas decimais.
$resultado = $calculo->calcularRateio(50000, [
    ['id' => 1, 'quantidade' => 2, 'custo_unitario_base' => '100.000'],
    ['id' => 2, 'quantidade' => 1, 'custo_unitario_base' => '50.000'],
]);

verificar('rateio item A', '40.000', $resultado[1]['custo_adicional_rateado']);
verificar('rateio item B', '10.000', $resultado[2]['custo_adicional_rateado']);
verificar('total real item A', '240.000', $resultado[1]['custo_total_real']);
verificar('total real item B', '60.000', $resultado[2]['custo_total_real']);
verificar('unitario real item A', '120.000', $resultado[1]['custo_unitario_real']);
verificar('unitario real item B', '60.000', $resultado[2]['custo_unitario_real']);
verificar(
    'soma do rateio igual ao total',
    50000,
    $resultado[1]['rateado_milesimos'] + $resultado[2]['rateado_milesimos']
);

// 2. Subtotal zero: rateio pela quantidade.
$resultado = $calculo->calcularRateio(4000, [
    ['id' => 1, 'quantidade' => 1, 'custo_unitario_base' => '0.000'],
    ['id' => 2, 'quantidade' => 3, 'custo_unitario_base' => '0.000'],
]);

verificar('rateio por quantidade item A', '1.000', $resultado[1]['custo_adicional_rateado']);
verificar('rateio por quantidade item B', '3.000', $resultado[2]['custo_adicional_rateado']);
verificar('unitario por quantidade item B', '1.000', $resultado[2]['custo_unitario_real']);

// 3. Residuo de arredondamento.
$resultado = $calculo->calcularRateio(100, [
    ['id' => 1, 'quantidade' => 1, 'custo_unitario_base' => '10.000'],
    ['id' => 2, 'quantidade' => 1, 'custo_unitario_base' => '10.000'],
    ['id' => 3, 'quantidade' => 1, 'custo_unitario_base' => '10.000'],
]);

verificar('residuo distribuido no primeiro item', '0.034', $resultado[1]['custo_adicional_rateado']);
verificar('residuo distribuido no segundo item', '0.033', $resultado[2]['custo_adicional_rateado']);
verificar('residuo distribuido no terceiro item', '0.033', $resultado[3]['custo_adicional_rateado']);
verificar(
    'soma do residuo igual ao total',
    100,
    $resultado[1]['rateado_milesimos'] + $resultado[2]['rateado_milesimos'] + $resultado[3]['rateado_milesimos']
);

// 4. Sem custos adicionais.
$resultado = $calculo->calcularRateio(0, [
    ['id' => 1, 'quantidade' => 2, 'custo_unitario_base' => '25.500'],
]);

verificar('sem adicionais: rateio zero', '0.000', $resultado[1]['custo_adicional_rateado']);
verificar('sem adicionais: total real', '51.000', $resultado[1]['custo_total_real']);
verificar('sem adicionais: unitario real', '25.500', $resultado[1]['custo_unitario_real']);

// 5. Sem itens.
verificar('sem itens retorna vazio', [], $calculo->calcularRateio(1000, []));

// 6. Conversao de valores monetarios.
verificar('converte 1.234,567', 1234567, $calculo->paraMilesimos('1.234,567'));
verificar('converte 1.234,56', 1234560, $calculo->paraMilesimos('1.234,56'));
verificar('converte 1234.567', 1234567, $calculo->paraMilesimos('1234.567'));
verificar('converte 100.000', 100000, $calculo->paraMilesimos('100.000'));
verificar('converte R$ 10,00', 10000, $calculo->paraMilesimos('R$ 10,00'));
verificar('converte zero', 0, $calculo->paraMilesimos('0'));
verificar('formata 1234.567', '1234.567', $calculo->milesimosParaDecimal(1234567));
verificar('formata 0.005', '0.005', $calculo->milesimosParaDecimal(5));

// 7. Item de previsao.
$resultado = $calculo->calcularItemPrevisao(3, 2500);
verificar('previsao: base', '2.500', $resultado['custo_unitario_base']);
verificar('previsao: total', '7.500', $resultado['custo_total_real']);
verificar('previsao: unitario', '2.500', $resultado['custo_unitario_real']);

echo "\n";
echo "Testes executados: {$testes}\n";
echo "Falhas: {$falhas}\n";

if ($falhas > 0) {
    echo "RESULTADO: FALHOU\n";
    exit(1);
}

echo "RESULTADO: TODOS OS TESTES PASSARAM\n";
exit(0);
