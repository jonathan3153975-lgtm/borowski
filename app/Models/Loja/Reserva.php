<?php

declare(strict_types=1);

namespace App\Models\Loja;

use App\Core\Model;
use PDO;

class Reserva extends Model
{
    protected string $table = 'loja_reservas';

    /**
     * Indica se o pedido esta integralmente pago (sem pagamento pendente na entrega).
     */
    public static function pagamentoTotalConfirmado(array $reserva): bool
    {
        if ((string) ($reserva['status'] ?? '') !== 'confirmada') {
            return false;
        }

        if (!empty($reserva['pagamento_na_entrega'])) {
            return false;
        }

        if (empty($reserva['pago_em'])) {
            return false;
        }

        if ($reserva['valor_reserva'] !== null && empty($reserva['saldo_pago_em'])) {
            return false;
        }

        return true;
    }

    public function reservadasAtivas(int $publicacaoId): int
    {
        $statement = $this->db->prepare(
            "SELECT COALESCE(SUM(quantidade), 0) FROM loja_reservas
             WHERE publicacao_id = :publicacao_id
               AND status = 'reservada'
               AND expira_em > NOW()"
        );
        $statement->execute(['publicacao_id' => $publicacaoId]);

        return (int) $statement->fetchColumn();
    }

    public function reservasAbertasPorPublicacao(int $publicacaoId): int
    {
        $statement = $this->db->prepare(
            "SELECT COUNT(*) FROM loja_reservas
             WHERE publicacao_id = :publicacao_id
               AND status = 'reservada'
               AND expira_em > NOW()"
        );
        $statement->execute(['publicacao_id' => $publicacaoId]);

        return (int) $statement->fetchColumn();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO loja_reservas
                (publicacao_id, item_id, canal_id, quantidade, preco_venda, valor_reserva,
                 status, metodo_pagamento, telegram_user_id, telegram_chat_id, comprador_nome,
                 pedido_id, expira_em)
             VALUES
                (:publicacao_id, :item_id, :canal_id, :quantidade, :preco_venda, :valor_reserva,
                 :status, :metodo_pagamento, :telegram_user_id, :telegram_chat_id, :comprador_nome,
                 :pedido_id, :expira_em)'
        );

        $statement->execute([
            'publicacao_id' => $data['publicacao_id'] ?? null,
            'item_id' => $data['item_id'],
            'canal_id' => $data['canal_id'],
            'quantidade' => $data['quantidade'] ?? 1,
            'preco_venda' => $data['preco_venda'] ?? null,
            'valor_reserva' => $data['valor_reserva'] ?? null,
            'status' => $data['status'] ?? 'reservada',
            'metodo_pagamento' => $data['metodo_pagamento'] ?? null,
            'telegram_user_id' => $data['telegram_user_id'] ?? null,
            'telegram_chat_id' => $data['telegram_chat_id'] ?? null,
            'comprador_nome' => $data['comprador_nome'] ?? null,
            'pedido_id' => $data['pedido_id'] ?? null,
            'expira_em' => $data['expira_em'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findReserva(int $id): ?array
    {
        $statement = $this->db->prepare(
            "SELECT r.*,
                i.nome AS item_nome, i.codigo AS item_codigo, i.descricao AS item_descricao,
                i.custo_unitario_real,
                p.preco_venda AS publicacao_preco, p.preco_reserva AS publicacao_reserva,
                p.quantidade AS publicado_quantidade,
                c.nome AS canal_nome, c.slug AS canal_slug
             FROM loja_reservas r
             INNER JOIN loja_itens i ON i.id = r.item_id
             INNER JOIN loja_canais c ON c.id = r.canal_id
             LEFT JOIN loja_itens_publicados p ON p.id = r.publicacao_id
             WHERE r.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $id]);

        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_reservas SET status = :status WHERE id = :id'
        );

        return $statement->execute(['status' => $status, 'id' => $id]);
    }

    public function updatePagamento(int $id, string $status, ?string $mpPaymentId, ?string $mpStatus, ?string $pagoEm): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_reservas
             SET status = :status,
                 mp_payment_id = :mp_payment_id,
                 mp_status = :mp_status,
                 pago_em = :pago_em
             WHERE id = :id'
        );

        return $statement->execute([
            'status' => $status,
            'mp_payment_id' => $mpPaymentId,
            'mp_status' => $mpStatus,
            'pago_em' => $pagoEm,
            'id' => $id,
        ]);
    }

    public function updateSaldoLink(int $id, string $link, string $data): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_reservas SET saldo_link = :link, saldo_cobranca_em = :data WHERE id = :id'
        );

        return $statement->execute(['link' => $link, 'data' => $data, 'id' => $id]);
    }

    public function updateSaldo(int $id, string $mpStatus, ?string $mpPaymentId, ?string $pagoEm): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_reservas
             SET saldo_mp_status = :mp_status, saldo_mp_payment_id = :mp_payment_id, saldo_pago_em = :pago_em
             WHERE id = :id'
        );

        return $statement->execute([
            'mp_status' => $mpStatus,
            'mp_payment_id' => $mpPaymentId,
            'pago_em' => $pagoEm,
            'id' => $id,
        ]);
    }

    /**
     * Habilita o pedido para entrega mantendo-o identificado como "pagamento na entrega".
     */
    public function marcarPagamentoNaEntrega(int $id): bool
    {
        $statement = $this->db->prepare(
            "UPDATE loja_reservas
             SET status = 'confirmada', pagamento_na_entrega = 1
             WHERE id = :id"
        );

        return $statement->execute(['id' => $id]);
    }

    /**
     * Marca o pedido como totalmente pago (ex.: recebimento em dinheiro na entrega).
     */
    public function marcarPagoTotal(int $id, string $pagoEm): bool
    {
        $statement = $this->db->prepare(
            "UPDATE loja_reservas
             SET status = 'confirmada',
                 pagamento_na_entrega = 0,
                 pago_em = COALESCE(pago_em, :pago_em),
                 saldo_pago_em = COALESCE(saldo_pago_em, :saldo_pago_em)
             WHERE id = :id"
        );

        return $statement->execute([
            'pago_em' => $pagoEm,
            'saldo_pago_em' => $pagoEm,
            'id' => $id,
        ]);
    }

    public function comprometidoPorItem(int $itemId): int
    {
        $statement = $this->db->prepare(
            "SELECT COALESCE(SUM(quantidade), 0) FROM loja_reservas
             WHERE item_id = :item_id
               AND (status = 'confirmada'
                 OR (status = 'reservada' AND expira_em > NOW()))"
        );
        $statement->execute(['item_id' => $itemId]);

        return (int) $statement->fetchColumn();
    }

    public function confirmarPorPedido(int $pedidoId, string $pagoEm): int
    {
        $statement = $this->db->prepare(
            "UPDATE loja_reservas
             SET status = 'confirmada', pago_em = :pago_em, mp_status = 'approved'
             WHERE pedido_id = :pedido_id AND status = 'reservada'"
        );
        $statement->execute(['pago_em' => $pagoEm, 'pedido_id' => $pedidoId]);

        return $statement->rowCount();
    }

    public function idsPorPedido(int $pedidoId, bool $semEntrega = true): array
    {
        $sql = "SELECT id FROM loja_reservas
                WHERE pedido_id = :pedido_id
                  AND status IN ('reservada', 'confirmada')";

        if ($semEntrega) {
            $sql .= ' AND entrega_id IS NULL';
        }

        $statement = $this->db->prepare($sql);
        $statement->execute(['pedido_id' => $pedidoId]);

        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function marcarPagamentoNaEntregaPorPedido(int $pedidoId): int
    {
        $statement = $this->db->prepare(
            "UPDATE loja_reservas
             SET status = 'confirmada', pagamento_na_entrega = 1
             WHERE pedido_id = :pedido_id AND status = 'reservada'"
        );
        $statement->execute(['pedido_id' => $pedidoId]);

        return $statement->rowCount();
    }

    public function marcarPagoPorPedido(int $pedidoId, string $pagoEm): int
    {
        $statement = $this->db->prepare(
            "UPDATE loja_reservas
             SET status = 'confirmada',
                 pago_em = COALESCE(pago_em, :pago_em),
                 saldo_pago_em = COALESCE(saldo_pago_em, :saldo_pago_em),
                 pagamento_na_entrega = 0,
                 mp_status = 'approved'
             WHERE pedido_id = :pedido_id AND status IN ('reservada', 'confirmada')"
        );
        $statement->execute([
            'pago_em' => $pagoEm,
            'saldo_pago_em' => $pagoEm,
            'pedido_id' => $pedidoId,
        ]);

        return $statement->rowCount();
    }

    public function cancelarPorPedido(int $pedidoId): int
    {
        $statement = $this->db->prepare(
            "DELETE FROM loja_reservas WHERE pedido_id = :pedido_id AND status = 'reservada'"
        );
        $statement->execute(['pedido_id' => $pedidoId]);

        return $statement->rowCount();
    }

    public function updateMetodo(int $id, string $metodo): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_reservas SET metodo_pagamento = :metodo WHERE id = :id'
        );

        return $statement->execute(['metodo' => $metodo, 'id' => $id]);
    }

    /**
     * Resumo por item: unidades reservadas, vendidas e valores.
     */
    public function resumoPorItem(): array
    {
        $rows = $this->db->query(
            "SELECT item_id,
                SUM(CASE WHEN status = 'reservada' AND expira_em > NOW() THEN quantidade ELSE 0 END) AS reservada,
                SUM(CASE WHEN status = 'confirmada' THEN quantidade ELSE 0 END) AS vendida,
                SUM(CASE WHEN status = 'reservada' AND expira_em > NOW() THEN COALESCE(valor_reserva, 0) ELSE 0 END) AS valor_reservado,
                SUM(CASE WHEN status = 'confirmada' THEN COALESCE(valor_reserva, 0) ELSE 0 END) AS arrecadado
             FROM loja_reservas
             GROUP BY item_id"
        )->fetchAll();

        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row['item_id']] = [
                'reservada' => (int) $row['reservada'],
                'vendida' => (int) $row['vendida'],
                'valor_reservado' => (float) $row['valor_reservado'],
                'arrecadado' => (float) $row['arrecadado'],
            ];
        }

        return $map;
    }

    public function listarRecentes(int $limit = 100): array
    {
        return $this->listar([], $limit);
    }

    public function listar(array $filters = [], int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $conditions = [];
        $params = [];

        $canal = trim((string) ($filters['canal'] ?? ''));

        if ($canal !== '') {
            $conditions[] = 'c.slug = :canal';
            $params[':canal'] = $canal;
        }

        $tipo = (string) ($filters['tipo'] ?? '');

        if ($tipo === 'pre_venda') {
            $conditions[] = 'r.valor_reserva IS NOT NULL';
        } elseif ($tipo === 'normal') {
            $conditions[] = 'r.valor_reserva IS NULL';
        }

        $status = (string) ($filters['status'] ?? '');

        if (in_array($status, ['reservada', 'confirmada', 'expirada', 'cancelada'], true)) {
            $conditions[] = 'r.status = :status';
            $params[':status'] = $status;
        }

        if (!empty($filters['sem_entrega'])) {
            $conditions[] = 'r.entrega_id IS NULL';
        }

        if (!empty($filters['pendente_pagamento'])) {
            $conditions[] = 'r.entrega_id IS NULL'
                . ' AND r.pagamento_na_entrega = 0'
                . " AND NOT (r.status = 'confirmada'"
                . ' AND (r.valor_reserva IS NULL OR r.saldo_pago_em IS NOT NULL))';
        }

        $busca = trim((string) ($filters['busca'] ?? ''));

        if ($busca !== '') {
            $conditions[] = '(i.nome LIKE :busca_nome OR i.codigo LIKE :busca_codigo'
                . ' OR r.comprador_nome LIKE :busca_comprador'
                . ' OR CAST(r.telegram_user_id AS CHAR) LIKE :busca_telegram'
                . ' OR c.nome LIKE :busca_canal)';
            $termo = '%' . $busca . '%';
            $params[':busca_nome'] = $termo;
            $params[':busca_codigo'] = $termo;
            $params[':busca_comprador'] = $termo;
            $params[':busca_telegram'] = $termo;
            $params[':busca_canal'] = $termo;
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $statement = $this->db->prepare(
            "SELECT r.*, i.nome AS item_nome, i.codigo AS item_codigo,
                c.nome AS canal_nome, c.slug AS canal_slug,
                p.preco_venda AS publicacao_preco, p.preco_reserva AS publicacao_reserva
             FROM loja_reservas r
             INNER JOIN loja_itens i ON i.id = r.item_id
             INNER JOIN loja_canais c ON c.id = r.canal_id
             LEFT JOIN loja_itens_publicados p ON p.id = r.publicacao_id
             {$where}
             ORDER BY r.criado_em DESC, r.id DESC
             LIMIT :limite"
        );

        foreach ($params as $chave => $valor) {
            $statement->bindValue($chave, $valor);
        }

        $statement->bindValue(':limite', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Remove reservas vencidas, liberando o item novamente.
     */
    public function expirarVencidas(): int
    {
        $statement = $this->db->prepare(
            "DELETE FROM loja_reservas
             WHERE status = 'expirada'
                OR (status = 'reservada' AND expira_em <= NOW())"
        );
        $statement->execute();

        return $statement->rowCount();
    }
}
