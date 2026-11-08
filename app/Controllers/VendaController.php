<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Loja\ItemPublicado;
use App\Models\Loja\PedidoOnline;
use App\Models\Loja\Reserva;
use App\Services\Loja\CobrancaService;
use App\Services\Loja\EntregaService;
use App\Services\Loja\MercadoPagoService;
use App\Services\Loja\PedidoAdminService;
use App\Services\Loja\TelegramService;
use InvalidArgumentException;
use Throwable;

class VendaController extends Controller
{
    public function index(): void
    {
        $reservas = new Reserva();
        $reservas->expirarVencidas();

        $filters = [
            'canal' => trim((string) ($_GET['canal'] ?? '')),
            'tipo' => trim((string) ($_GET['tipo'] ?? '')),
            'status' => 'confirmada',
            'sem_entrega' => true,
            'busca' => trim((string) ($_GET['busca'] ?? '')),
        ];

        $agrupado = (new PedidoAdminService())->agrupar($reservas->listar($filters, 200), false);

        $this->view('vendas.index', [
            'title' => 'Vendas',
            'breadcrumb' => [
                ['label' => 'Vendas', 'url' => null],
            ],
            'reservas' => $agrupado['reservas'],
            'pedidosAgrupados' => $agrupado['pedidos'],
            'canais' => (new ItemPublicado())->canais(),
            'filters' => $filters,
            'extraStyles' => [asset('css/loja.css')],
            'extraScripts' => [asset('js/loja.js')],
        ], 'admin');
    }

    public function cliente(): void
    {
        $telegramId = (int) ($_GET['telegram'] ?? 0);
        $cliente = (new EntregaService())->clientePorTelegram($telegramId > 0 ? $telegramId : null);

        $this->json($cliente ?? []);
    }

    public function storeEntrega(): void
    {
        try {
            $selecionados = (array) ($_POST['itens'] ?? []);
            $reservaIds = [];
            $reservas = new Reserva();

            foreach ($selecionados as $valor) {
                $valor = (string) $valor;

                if (str_starts_with($valor, 'pedido:')) {
                    $pedidoId = (int) substr($valor, 7);

                    if ($pedidoId > 0) {
                        $reservaIds = array_merge($reservaIds, $reservas->idsPorPedido($pedidoId));
                    }

                    continue;
                }

                $reservaIds[] = (int) $valor;
            }

            $telegramId = (int) ($_POST['telegram_user_id'] ?? 0);
            $entregaId = (new EntregaService())->criar($telegramId, $reservaIds, $_POST, (array) auth_user());

            flash('success', 'Entrega #' . $entregaId . ' criada com sucesso.');
            redirect('/admin/entregas');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            flash_input($_POST);
        } catch (Throwable $exception) {
            flash('error', 'Falha ao criar a entrega: ' . $exception->getMessage());
        }

        redirect('/admin/vendas');
    }

    public function verificar(string $id): void
    {
        $reservaId = (int) $id;

        try {
            $mercadoPago = new MercadoPagoService();

            if (!$mercadoPago->ativo()) {
                throw new InvalidArgumentException('Mercado Pago não está ativo nas configurações.');
            }

            $primeiro = $mercadoPago->processarPagamentoDaReserva($reservaId);
            $saldo = $mercadoPago->processarSaldoDaReserva($reservaId);

            $resultados = [];

            if ($primeiro !== null) {
                $resultados[] = 'pagamento: ' . (string) ($primeiro['status'] ?? '?');
            }

            if ($saldo !== null) {
                $resultados[] = 'saldo: ' . (string) ($saldo['status'] ?? '?');
            }

            if ($resultados === []) {
                flash('warning', 'Nenhum pagamento encontrado no Mercado Pago para esta venda.');
            } else {
                flash('success', 'Verificação concluída (' . implode(', ', $resultados) . ').');
            }
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Falha ao verificar o pagamento: ' . $exception->getMessage());
        }

        redirect('/admin/vendas');
    }

    public function marcarPagoPedido(string $id): void
    {
        try {
            $pedido = (new PedidoOnline())->find((int) $id);

            if ($pedido === null) {
                throw new InvalidArgumentException('Pedido não encontrado.');
            }

            $pagoEm = date('Y-m-d H:i:s');
            (new PedidoOnline())->marcarPago((int) $id, $pagoEm);
            (new Reserva())->marcarPagoPorPedido((int) $id, $pagoEm);

            try {
                $idsReservas = (new Reserva())->idsPorPedido((int) $id, false);

                if (($idsReservas[0] ?? 0) > 0) {
                    (new TelegramService())->notificarSaldoConfirmado((int) $idsReservas[0]);
                }
            } catch (Throwable $exception) {
                error_log('[vendas] falha ao notificar pedido pago: ' . $exception->getMessage());
            }

            flash('success', 'Pedido marcado como pago (recebimento manual).');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Falha ao marcar o pedido como pago: ' . $exception->getMessage());
        }

        redirect('/admin/vendas');
    }

    public function marcarPago(string $id): void
    {
        try {
            $reserva = (new Reserva())->findReserva((int) $id);

            if ($reserva === null) {
                throw new InvalidArgumentException('Venda não encontrada.');
            }

            (new Reserva())->marcarPagoTotal((int) $id, date('Y-m-d H:i:s'));

            try {
                (new TelegramService())->notificarSaldoConfirmado((int) $id);
            } catch (Throwable $exception) {
                error_log('[vendas] falha ao notificar pagamento manual: ' . $exception->getMessage());
            }

            flash('success', 'Venda marcada como paga (recebimento manual).');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Falha ao marcar como pago: ' . $exception->getMessage());
        }

        redirect('/admin/vendas');
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

        redirect('/admin/vendas');
    }
}
