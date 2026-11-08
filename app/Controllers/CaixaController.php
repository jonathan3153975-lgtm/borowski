<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Loja\PedidoOnline;
use App\Models\Loja\Reserva;
use App\Services\Loja\CaixaService;

class CaixaController extends Controller
{
    public function index(): void
    {
        (new Reserva())->expirarVencidas();
        (new PedidoOnline())->expirarVencidos();

        $filters = [
            'categoria' => trim((string) ($_GET['categoria'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'busca' => trim((string) ($_GET['busca'] ?? '')),
            'mes' => (int) ($_GET['mes'] ?? 0),
            'ano' => (int) ($_GET['ano'] ?? 0),
        ];

        $service = new CaixaService();
        $registros = $service->listar($filters);
        $totais = [
            'registros' => count($registros),
            'total' => array_sum(array_column($registros, 'total')),
            'pago' => array_sum(array_column($registros, 'valor_pago')),
            'receber' => array_sum(array_column($registros, 'saldo')),
        ];

        $this->view('caixa.index', [
            'title' => 'Caixa',
            'breadcrumb' => [
                ['label' => 'Caixa', 'url' => null],
            ],
            'registros' => $registros,
            'filters' => $filters,
            'totais' => $totais,
            'categorias' => $service->categorias(),
            'statusOpcoes' => $service->statusOpcoes(),
            'meses' => [
                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
            ],
            'anos' => range((int) date('Y'), (int) date('Y') - 4),
            'extraStyles' => [asset('css/loja.css')],
            'extraScripts' => [asset('js/loja.js')],
        ], 'admin');
    }
}
