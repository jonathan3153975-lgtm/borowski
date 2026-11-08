<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Models\Loja\PedidoOnline;
use App\Models\Loja\Reserva;

class PedidoAdminService
{
    private PedidoOnline $pedidos;

    public function __construct()
    {
        $this->pedidos = new PedidoOnline();
    }

    /**
     * Agrupa reservas de uma mesma compra da loja em um unico registro.
     *
     * @return array{pedidos: array, reservas: array}
     */
    public function agrupar(array $reservas, bool $incluirEntregues = false): array
    {
        $grupos = [];
        $avulsas = [];

        foreach ($reservas as $reserva) {
            $pedidoId = (int) ($reserva['pedido_id'] ?? 0);

            if ($pedidoId > 0) {
                $grupos[$pedidoId][] = $reserva;
            } else {
                $avulsas[] = $reserva;
            }
        }

        $pedidos = [];

        foreach ($grupos as $pedidoId => $itens) {
            $pedido = $this->pedidos->find($pedidoId);

            if ($pedido === null) {
                $avulsas = array_merge($avulsas, $itens);
                continue;
            }

            if (!$incluirEntregues && !empty($pedido['entregue_em'])) {
                continue;
            }

            $total = 0.0;
            $quantidade = 0;
            $detalhes = [];

            foreach ($itens as $item) {
                $qtd = (int) ($item['quantidade'] ?? 0);
                $preco = (float) ($item['preco_venda'] ?? 0);
                $subtotal = round($preco * $qtd, 3);
                $total += $subtotal;
                $quantidade += $qtd;

                $detalhes[] = [
                    'nome' => (string) ($item['item_nome'] ?? 'Item'),
                    'codigo' => $item['item_codigo'] ?? null,
                    'quantidade' => $qtd,
                    'preco' => $preco,
                    'subtotal' => $subtotal,
                    'reserva_id' => (int) ($item['id'] ?? 0),
                ];
            }

            $valorPago = (float) ($pedido['valor_pago'] ?? 0);
            $saldo = max(0, round($total - $valorPago, 3));
            $pagoTotal = $total > 0 && $saldo <= 0.0001;

            $origem = (string) ($pedido['origem'] ?? 'loja');
            $canalNome = $origem === 'presencial' ? 'Venda direta' : 'Loja virtual';
            $canalSlug = $origem === 'presencial' ? 'presencial' : 'loja';

            $pedidos[] = [
                'is_pedido' => true,
                'id' => $pedidoId,
                'pedido_id' => $pedidoId,
                'codigo' => (string) $pedido['codigo'],
                'cliente_nome' => (string) $pedido['cliente_nome'],
                'cliente_email' => $pedido['cliente_email'] ?? null,
                'cliente_telefone' => $pedido['cliente_telefone'] ?? null,
                'entrega_modo' => (string) ($pedido['entrega_modo'] ?? 'retirada'),
                'origem' => $origem,
                'canal_nome' => $canalNome,
                'canal_slug' => $canalSlug,
                'status' => (string) $pedido['status'],
                'pagamento_na_entrega' => (int) ($pedido['pagamento_na_entrega'] ?? 0),
                'pago_total' => $pagoTotal,
                'pago_em' => $pedido['pago_em'] ?? null,
                'entregue_em' => $pedido['entregue_em'] ?? null,
                'mp_status' => $pedido['mp_status'] ?? null,
                'mp_payment_id' => $pedido['mp_payment_id'] ?? null,
                'metodo_pagamento' => $pedido['metodo_pagamento'] ?? null,
                'criado_em' => $pedido['criado_em'] ?? null,
                'expira_em' => $pedido['expira_em'] ?? null,
                'total' => round($total, 3),
                'valor_pago' => $valorPago,
                'saldo' => $saldo,
                'quantidade' => $quantidade,
                'itens' => $detalhes,
                'reserva_ids' => array_map(
                    static fn (array $item): int => (int) ($item['id'] ?? 0),
                    $itens
                ),
                'primeiro_item' => $detalhes[0]['nome'] ?? '',
                'reservas' => $itens,
            ];
        }

        usort(
            $pedidos,
            static fn (array $a, array $b): int => strcmp((string) $b['criado_em'], (string) $a['criado_em'])
        );

        return ['pedidos' => $pedidos, 'reservas' => $avulsas];
    }
}
