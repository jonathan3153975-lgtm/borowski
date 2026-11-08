<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Core\Database;
use App\Models\Loja\ItemEstoque;
use PDO;

class DashboardService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function resumo(): array
    {
        $mesInicio = date('Y-m-01 00:00:00');
        $mesFim = date('Y-m-t 23:59:59');

        $pedidosPendentes = (int) $this->db->query(
            "SELECT COUNT(*) FROM loja_pedidos WHERE status = 'pendente'"
        )->fetchColumn() + (int) $this->db->query(
            "SELECT COUNT(*) FROM loja_reservas
             WHERE pedido_id IS NULL AND status = 'reservada' AND expira_em > NOW()"
        )->fetchColumn();

        $vendasMes = (float) $this->db->query(
            "SELECT COALESCE(SUM(valor_pago), 0) FROM loja_pedidos
             WHERE pago_em BETWEEN '{$mesInicio}' AND '{$mesFim}'"
        )->fetchColumn() + (float) $this->db->query(
            "SELECT COALESCE(SUM(
                CASE WHEN valor_reserva IS NOT NULL THEN valor_reserva ELSE preco_venda END * quantidade
             ), 0)
             FROM loja_reservas
             WHERE pedido_id IS NULL AND status = 'confirmada'
               AND pago_em BETWEEN '{$mesInicio}' AND '{$mesFim}'"
        )->fetchColumn();

        $aReceber = (float) $this->db->query(
            "SELECT COALESCE(SUM(GREATEST(0, subtotal - valor_pago)), 0) FROM loja_pedidos
             WHERE status IN ('pendente', 'pago') AND entregue_em IS NULL"
        )->fetchColumn() + (float) $this->db->query(
            "SELECT COALESCE(SUM(GREATEST(0, (preco_venda - COALESCE(valor_reserva, 0)) * quantidade)), 0)
             FROM loja_reservas
             WHERE pedido_id IS NULL AND status = 'confirmada' AND saldo_pago_em IS NULL"
        )->fetchColumn();

        $aguardandoEntrega = (int) $this->db->query(
            "SELECT COUNT(*) FROM loja_pedidos WHERE status = 'pago' AND entregue_em IS NULL"
        )->fetchColumn() + (int) $this->db->query(
            "SELECT COUNT(*) FROM loja_reservas
             WHERE pedido_id IS NULL AND status = 'confirmada' AND entrega_id IS NULL"
        )->fetchColumn();

        $entregasPendentes = (int) $this->db->query(
            "SELECT COUNT(*) FROM loja_entregas WHERE status IN ('pendente', 'enviada')"
        )->fetchColumn();

        $publicacoesAtivas = (int) $this->db->query(
            "SELECT COUNT(*) FROM loja_itens_publicados WHERE status = 'publicado'"
        )->fetchColumn();

        $ultimosPedidos = $this->db->query(
            "SELECT id, codigo, cliente_nome, subtotal, total, valor_pago, saldo, status,
                pagamento_na_entrega, entregue_em, criado_em
             FROM loja_pedidos
             ORDER BY criado_em DESC, id DESC
             LIMIT 5"
        )->fetchAll();

        $entregasRecentes = $this->db->query(
            "SELECT e.id, e.status, e.criado_em, c.nome AS cliente_nome
             FROM loja_entregas e
             INNER JOIN loja_clientes_entrega c ON c.id = e.cliente_id
             WHERE e.status IN ('pendente', 'enviada')
             ORDER BY e.criado_em DESC, e.id DESC
             LIMIT 5"
        )->fetchAll();

        return [
            'pedidos_pendentes' => $pedidosPendentes,
            'vendas_mes' => $vendasMes,
            'a_receber' => $aReceber,
            'aguardando_entrega' => $aguardandoEntrega,
            'entregas_pendentes' => $entregasPendentes,
            'publicacoes_ativas' => $publicacoesAtivas,
            'estoque' => (new ItemEstoque())->resumoEstoque(),
            'ultimos_pedidos' => $ultimosPedidos,
            'entregas_recentes' => $entregasRecentes,
        ];
    }
}
