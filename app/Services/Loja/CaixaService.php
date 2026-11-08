<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Core\Database;
use App\Models\Loja\PedidoOnline;
use PDO;

class CaixaService
{
    private PDO $db;
    private PedidoOnline $pedidos;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->pedidos = new PedidoOnline();
    }

    public function categorias(): array
    {
        return [
            'loja' => 'Loja virtual',
            'presencial' => 'Venda direta',
            'telegram' => 'Telegram',
            'whatsapp' => 'WhatsApp',
        ];
    }

    public function statusOpcoes(): array
    {
        return [
            'pendente' => 'Pendente',
            'pagamento_entrega' => 'Pagamento na entrega',
            'saldo_pendente' => 'Saldo pendente',
            'pago' => 'Pago',
            'entregue' => 'Entregue',
            'cancelado' => 'Cancelado',
            'expirado' => 'Expirado',
        ];
    }

    public function listar(array $filters = []): array
    {
        $registros = [];

        $orders = $this->db->query(
            'SELECT * FROM loja_pedidos ORDER BY criado_em DESC, id DESC'
        )->fetchAll();

        foreach ($orders as $pedido) {
            $itens = $this->pedidos->itens((int) $pedido['id']);
            $categoria = (string) ($pedido['origem'] ?? 'loja');
            $detalhes = [];

            foreach ($itens as $item) {
                $detalhes[] = [
                    'nome' => (string) $item['item_nome'],
                    'codigo' => $item['item_codigo'] ?? null,
                    'quantidade' => (int) $item['quantidade'],
                    'preco' => (float) $item['preco_unitario'],
                    'subtotal' => (float) $item['subtotal'],
                ];
            }

            $totalBruto = (float) ($pedido['subtotal'] ?? $pedido['total'] ?? 0);
            $valorPago = (float) ($pedido['valor_pago'] ?? 0);
            $saldo = max(0, round($totalBruto - $valorPago, 3));
            $status = $this->statusPedido($pedido, $saldo);

            if (in_array($status, ['cancelado', 'expirado'], true) && $valorPago <= 0) {
                continue;
            }

            if (in_array($status, ['cancelado', 'expirado'], true)) {
                $saldo = 0.0;
            }

            $registros[] = [
                'tipo' => 'pedido',
                'id' => (int) $pedido['id'],
                'codigo' => (string) $pedido['codigo'],
                'categoria' => $categoria,
                'categoria_label' => $this->categorias()[$categoria] ?? ucfirst($categoria),
                'cliente' => (string) $pedido['cliente_nome'],
                'itens' => count($itens),
                'total' => round($totalBruto, 3),
                'valor_pago' => $valorPago,
                'saldo' => $saldo,
                'status' => $status,
                'status_label' => $this->statusOpcoes()[$status] ?? ucfirst($status),
                'metodo_pagamento' => $pedido['metodo_pagamento'] ?? null,
                'criado_em' => $pedido['criado_em'] ?? null,
                'detalhes' => $detalhes,
            ];
        }

        $reservas = $this->db->query(
            "SELECT r.*, i.nome AS item_nome, i.codigo AS item_codigo,
                c.slug AS canal_slug, c.nome AS canal_nome
             FROM loja_reservas r
             INNER JOIN loja_itens i ON i.id = r.item_id
             INNER JOIN loja_canais c ON c.id = r.canal_id
             WHERE r.pedido_id IS NULL
             ORDER BY r.criado_em DESC, r.id DESC"
        )->fetchAll();

        foreach ($reservas as $reserva) {
            $quantidade = (int) $reserva['quantidade'];
            $preco = (float) $reserva['preco_venda'];
            $total = round($preco * $quantidade, 3);
            $valorReserva = $reserva['valor_reserva'] !== null ? (float) $reserva['valor_reserva'] : null;
            $pagoEm = !empty($reserva['pago_em']);
            $valorPago = $pagoEm
                ? round(($valorReserva ?? $preco) * $quantidade, 3)
                : 0.0;
            $saldo = max(0, round($total - $valorPago, 3));
            $categoria = (string) $reserva['canal_slug'];
            $status = $this->statusReserva($reserva, $saldo);

            if (in_array((string) $reserva['status'], ['expirada', 'cancelada'], true) && empty($reserva['pago_em'])) {
                continue;
            }

            if (in_array($status, ['cancelado', 'expirado'], true)) {
                $saldo = 0.0;
            }

            $registros[] = [
                'tipo' => 'reserva',
                'id' => (int) $reserva['id'],
                'codigo' => '#' . $reserva['id'],
                'categoria' => $categoria,
                'categoria_label' => (string) $reserva['canal_nome'],
                'cliente' => (string) ($reserva['comprador_nome'] ?? ''),
                'itens' => 1,
                'total' => $total,
                'valor_pago' => $valorPago,
                'saldo' => $saldo,
                'status' => $status,
                'status_label' => $this->statusOpcoes()[$status] ?? ucfirst($status),
                'metodo_pagamento' => $reserva['metodo_pagamento'] ?? null,
                'criado_em' => $reserva['criado_em'] ?? null,
                'detalhes' => [[
                    'nome' => (string) $reserva['item_nome'],
                    'codigo' => $reserva['item_codigo'] ?? null,
                    'quantidade' => $quantidade,
                    'preco' => $preco,
                    'subtotal' => $total,
                ]],
            ];
        }

        $categoriaFiltro = trim((string) ($filters['categoria'] ?? ''));
        $statusFiltro = trim((string) ($filters['status'] ?? ''));
        $busca = mb_strtolower(trim((string) ($filters['busca'] ?? '')));
        $mes = (int) ($filters['mes'] ?? 0);
        $ano = (int) ($filters['ano'] ?? 0);

        $registros = array_values(array_filter(
            $registros,
            static function (array $registro) use ($categoriaFiltro, $statusFiltro, $busca, $mes, $ano): bool {
                if ($categoriaFiltro !== '' && $registro['categoria'] !== $categoriaFiltro) {
                    return false;
                }

                if ($statusFiltro !== '' && $registro['status'] !== $statusFiltro) {
                    return false;
                }

                if ($busca !== '') {
                    $nomes = implode(' ', array_map(
                        static fn (array $detalhe): string => (string) $detalhe['nome'],
                        $registro['detalhes']
                    ));
                    $texto = mb_strtolower($registro['codigo'] . ' ' . $registro['cliente'] . ' ' . $nomes);

                    if (mb_strpos($texto, $busca) === false) {
                        return false;
                    }
                }

                if ($mes > 0 || $ano > 0) {
                    $timestamp = strtotime((string) ($registro['criado_em'] ?? ''));

                    if ($timestamp === false) {
                        return false;
                    }

                    if ($ano > 0 && (int) date('Y', $timestamp) !== $ano) {
                        return false;
                    }

                    if ($mes > 0 && (int) date('n', $timestamp) !== $mes) {
                        return false;
                    }
                }

                return true;
            }
        ));

        usort(
            $registros,
            static fn (array $a, array $b): int => strcmp((string) $b['criado_em'], (string) $a['criado_em'])
        );

        return $registros;
    }

    private function statusPedido(array $pedido, float $saldo): string
    {
        if (!empty($pedido['entregue_em'])) {
            return 'entregue';
        }

        if ((string) $pedido['status'] === 'pago') {
            return $saldo > 0.0001 ? 'saldo_pendente' : 'pago';
        }

        if (!empty($pedido['pagamento_na_entrega'])) {
            return 'pagamento_entrega';
        }

        return (string) $pedido['status'];
    }

    private function statusReserva(array $reserva, float $saldo): string
    {
        if (!empty($reserva['entrega_id'])) {
            return 'entregue';
        }

        if (!empty($reserva['pago_em'])) {
            return $saldo > 0.0001 ? 'saldo_pendente' : 'pago';
        }

        if (!empty($reserva['pagamento_na_entrega'])) {
            return 'pagamento_entrega';
        }

        return match ((string) $reserva['status']) {
            'reservada' => 'pendente',
            'confirmada' => 'pago',
            'expirada' => 'expirado',
            'cancelada' => 'cancelado',
            default => (string) $reserva['status'],
        };
    }
}
