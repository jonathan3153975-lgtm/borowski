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

// 1. Rateio proporcional ao subtotal.
$resultado = $calculo->calcularRateio(5000, [
    ['id' => 1, 'quantidade' => 2, 'custo_unitario_base' => '100.00'],
    ['id' => 2, 'quantidade' => 1, 'custo_unitario_base' => '50.00'],
]);

verificar('rateio item A', '40.00', $resultado[1]['custo_adicional_rateado']);
verificar('rateio item B', '10.00', $resultado[2]['custo_adicional_rateado']);
verificar('total real item A', '240.00', $resultado[1]['custo_total_real']);
verificar('total real item B', '60.00', $resultado[2]['custo_total_real']);
verificar('unitario real item A', '120.00', $resultado[1]['custo_unitario_real']);
verificar('unitario real item B', '60.00', $resultado[2]['custo_unitario_real']);
verificar(
    'soma do rateio igual ao total',
    5000,
    $resultado[1]['rateado_centavos'] + $resultado[2]['rateado_centavos']
);

// 2. Subtotal zero: rateio pela quantidade.
$resultado = $calculo->calcularRateio(4000, [
    ['id' => 1, 'quantidade' => 1, 'custo_unitario_base' => '0.00'],
    ['id' => 2, 'quantidade' => 3, 'custo_unitario_base' => '0.00'],
]);

verificar('rateio por quantidade item A', '10.00', $resultado[1]['custo_adicional_rateado']);
verificar('rateio por quantidade item B', '30.00', $resultado[2]['custo_adicional_rateado']);
verificar('unitario por quantidade item B', '10.00', $resultado[2]['custo_unitario_real']);

// 3. Residuo de arredondamento.
$resultado = $calculo->calcularRateio(100, [
    ['id' => 1, 'quantidade' => 1, 'custo_unitario_base' => '10.00'],
    ['id' => 2, 'quantidade' => 1, 'custo_unitario_base' => '10.00'],
    ['id' => 3, 'quantidade' => 1, 'custo_unitario_base' => '10.00'],
]);

verificar('residuo distribuido no primeiro item', '0.34', $resultado[1]['custo_adicional_rateado']);
verificar('residuo distribuido no segundo item', '0.33', $resultado[2]['custo_adicional_rateado']);
verificar('residuo distribuido no terceiro item', '0.33', $resultado[3]['custo_adicional_rateado']);
verificar(
    'soma do residuo igual ao total',
    100,
    $resultado[1]['rateado_centavos'] + $resultado[2]['rateado_centavos'] + $resultado[3]['rateado_centavos']
);

// 4. Sem custos adicionais.
$resultado = $calculo->calcularRateio(0, [
    ['id' => 1, 'quantidade' => 2, 'custo_unitario_base' => '25.50'],
]);

verificar('sem adicionais: rateio zero', '0.00', $resultado[1]['custo_adicional_rateado']);
verificar('sem adicionais: total real', '51.00', $resultado[1]['custo_total_real']);
verificar('sem adicionais: unitario real', '25.50', $resultado[1]['custo_unitario_real']);

// 5. Sem itens.
verificar('sem itens retorna vazio', [], $calculo->calcularRateio(1000, []));

// 6. Conversao de valores monetarios.
verificar('converte 1.234,56', 123456, $calculo->paraCentavos('1.234,56'));
verificar('converte 1234.56', 123456, $calculo->paraCentavos('1234.56'));
verificar('converte R$ 10,00', 1000, $calculo->paraCentavos('R$ 10,00'));
verificar('converte zero', 0, $calculo->paraCentavos('0'));
verificar('formata 1234.56', '1234.56', $calculo->centavosParaDecimal(123456));
verificar('formata 0.05', '0.05', $calculo->centavosParaDecimal(5));

// 7. Item de previsao.
$resultado = $calculo->calcularItemPrevisao(3, 2500);
verificar('previsao: base', '25.00', $resultado['custo_unitario_base']);
verificar('previsao: total', '75.00', $resultado['custo_total_real']);
verificar('previsao: unitario', '25.00', $resultado['custo_unitario_real']);

echo "\n";
echo "Testes executados: {$testes}\n";
echo "Falhas: {$falhas}\n";

if ($falhas > 0) {
    echo "RESULTADO: FALHOU\n";
    exit(1);
}

echo "RESULTADO: TODOS OS TESTES PASSARAM\n";
exit(0);
