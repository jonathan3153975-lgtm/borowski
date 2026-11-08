<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Loja\ItemPublicado;
use App\Models\Loja\PedidoOnline;
use App\Models\Loja\Reserva;
use App\Services\Loja\CobrancaService;
use App\Services\Loja\MercadoPagoService;
use App\Services\Loja\PedidoAdminService;
use InvalidArgumentException;
use Throwable;

class PedidoController extends Controller
{
    public function index(): void
    {
        $reservas = new Reserva();
        $reservas->expirarVencidas();

        $filters = [
            'canal' => trim((string) ($_GET['canal'] ?? '')),
            'tipo' => trim((string) ($_GET['tipo'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'busca' => trim((string) ($_GET['busca'] ?? '')),
            'pendente_pagamento' => true,
        ];

        $agrupado = (new PedidoAdminService())->agrupar($reservas->listar($filters, 200), false);

        $this->view('pedidos.index', [
            'title' => 'Pedidos',
            'breadcrumb' => [
                ['label' => 'Pedidos', 'url' => null],
            ],
            'reservas' => $agrupado['reservas'],
            'pedidosAgrupados' => $agrupado['pedidos'],
            'canais' => (new ItemPublicado())->canais(),
            'filters' => $filters,
            'extraStyles' => [asset('css/loja.css')],
            'extraScripts' => [asset('js/loja.js')],
        ], 'admin');
    }

    public function cobrarRestante(string $id): void
    {
        try {
            $resultado = (new CobrancaService())->cobrarSaldo((int) $id);

            if ($resultado['enviado']) {
                flash('success', 'Cobrança do saldo gerada e enviada ao comprador no Telegram.');
            } else {
                flash('warning', 'Cobrança gerada, mas não foi possível enviar ao comprador. Link: ' . $resultado['link']);
            }
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Falha ao gerar a cobrança: ' . $exception->getMessage());
        }

        redirect('/admin/pedidos');
    }

    public function pagamentoEntrega(string $id): void
    {
        try {
            $reserva = (new Reserva())->findReserva((int) $id);

            if ($reserva === null) {
                throw new InvalidArgumentException('Pedido não encontrado.');
            }

            $pago = !empty($reserva['pago_em'])
                && ($reserva['valor_reserva'] === null || !empty($reserva['saldo_pago_em']));

            if ($pago) {
                throw new InvalidArgumentException('Este pedido já está totalmente pago.');
            }

            (new Reserva())->marcarPagamentoNaEntrega((int) $id);
            flash('success', 'Pedido habilitado para entrega como pagamento na entrega (ainda não pago).');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Falha ao habilitar o pagamento na entrega: ' . $exception->getMessage());
        }

        redirect('/admin/pedidos');
    }

    public function pagamentoEntregaPedido(string $id): void
    {
        try {
            $pedido = (new PedidoOnline())->find((int) $id);

            if ($pedido === null) {
                throw new InvalidArgumentException('Pedido não encontrado.');
            }

            if ((string) $pedido['status'] !== 'pendente') {
                throw new InvalidArgumentException('Este pedido não está aguardando pagamento.');
            }

            (new PedidoOnline())->marcarPagamentoNaEntrega((int) $id);
            (new Reserva())->marcarPagamentoNaEntregaPorPedido((int) $id);

            flash('success', 'Pedido habilitado para entrega como pagamento na entrega (ainda não pago).');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Falha ao habilitar o pagamento na entrega: ' . $exception->getMessage());
        }

        redirect('/admin/pedidos');
    }

    public function verificarPedido(string $id): void
    {
        try {
            $mercadoPago = new MercadoPagoService();

            if (!$mercadoPago->ativo()) {
                throw new InvalidArgumentException('Mercado Pago não está ativo nas configurações.');
            }

            $pagamento = $mercadoPago->processarPedidoOnline((int) $id);

            if ($pagamento === null) {
                flash('warning', 'Nenhum pagamento encontrado no Mercado Pago para este pedido.');
            } else {
                $status = (string) ($pagamento['status'] ?? 'desconhecido');
                flash(
                    $status === 'approved' ? 'success' : 'info',
                    'Pagamento encontrado com status: ' . $status . '.'
                );
            }
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Falha ao verificar o pagamento: ' . $exception->getMessage());
        }

        redirect('/admin/pedidos');
    }

    public function verificar(string $id): void
    {
        $reservaId = (int) $id;

        try {
            $mercadoPago = new MercadoPagoService();

            if (!$mercadoPago->ativo()) {
                throw new InvalidArgumentException('Mercado Pago não está ativo nas configurações.');
            }

            $pagamento = $mercadoPago->processarPagamentoDaReserva($reservaId);

            if ($pagamento === null) {
                flash('warning', 'Nenhum pagamento encontrado no Mercado Pago para esta reserva.');
            } else {
                $status = (string) ($pagamento['status'] ?? 'desconhecido');

                if ($status === 'approved') {
                    flash('success', 'Pagamento aprovado! Pedido confirmado.');
                } else {
                    flash('info', 'Pagamento encontrado com status: ' . $status . '.');
                }
            }
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Falha ao verificar o pagamento: ' . $exception->getMessage());
        }

        redirect('/admin/pedidos');
    }
}
