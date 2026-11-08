<?php

declare(strict_types=1);

namespace App\Models\Loja;

use App\Core\Model;

class PedidoOnline extends Model
{
    protected string $table = 'loja_pedidos';

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO loja_pedidos
                (codigo, cliente_nome, cliente_email, cliente_telefone, entrega_modo,
                 cep, logradouro, numero, complemento, bairro, cidade, estado, observacoes,
                 subtotal, frete, total, valor_pago, saldo, status, origem, metodo_pagamento, expira_em)
             VALUES
                (:codigo, :cliente_nome, :cliente_email, :cliente_telefone, :entrega_modo,
                 :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado, :observacoes,
                 :subtotal, :frete, :total, :valor_pago, :saldo, :status, :origem, :metodo_pagamento, :expira_em)'
        );

        $statement->execute([
            'codigo' => $data['codigo'],
            'cliente_nome' => $data['cliente_nome'],
            'cliente_email' => $data['cliente_email'] ?? null,
            'cliente_telefone' => $data['cliente_telefone'] ?? null,
            'entrega_modo' => $data['entrega_modo'] ?? 'retirada',
            'cep' => $data['cep'] ?? null,
            'logradouro' => $data['logradouro'] ?? null,
            'numero' => $data['numero'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cidade' => $data['cidade'] ?? null,
            'estado' => $data['estado'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
            'subtotal' => $data['subtotal'] ?? 0,
            'frete' => $data['frete'] ?? 0,
            'total' => $data['total'] ?? 0,
            'valor_pago' => $data['valor_pago'] ?? 0,
            'saldo' => $data['saldo'] ?? 0,
            'status' => $data['status'] ?? 'pendente',
            'origem' => $data['origem'] ?? 'loja',
            'metodo_pagamento' => $data['metodo_pagamento'] ?? null,
            'expira_em' => $data['expira_em'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function gerarCodigo(): string
    {
        do {
            $codigo = 'LC' . date('ymd') . strtoupper(bin2hex(random_bytes(2)));
        } while ($this->findByCodigo($codigo) !== null);

        return $codigo;
    }

    public function findByCodigo(string $codigo): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM loja_pedidos WHERE codigo = :codigo LIMIT 1'
        );
        $statement->execute(['codigo' => $codigo]);
        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public function updatePreference(int $id, string $preferenceId, string $link): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_pedidos
             SET mp_preference_id = :preference, mp_link = :link, atualizado_em = NOW()
             WHERE id = :id'
        );

        return $statement->execute([
            'preference' => $preferenceId,
            'link' => $link,
            'id' => $id,
        ]);
    }

    public function updatePagamento(
        int $id,
        string $status,
        ?string $paymentId,
        ?string $mpStatus,
        ?string $pagoEm
    ): bool {
        $statement = $this->db->prepare(
            "UPDATE loja_pedidos
             SET status = :status,
                 mp_payment_id = :payment_id,
                 mp_status = :mp_status,
                 pago_em = :pago_em,
                 pagamento_na_entrega = CASE WHEN :is_pago_1 = 'pago' THEN 0 ELSE pagamento_na_entrega END,
                 valor_pago = CASE WHEN :is_pago_2 = 'pago' THEN total ELSE valor_pago END,
                 atualizado_em = NOW()
             WHERE id = :id"
        );

        return $statement->execute([
            'status' => $status,
            'is_pago_1' => $status,
            'is_pago_2' => $status,
            'payment_id' => $paymentId,
            'mp_status' => $mpStatus,
            'pago_em' => $pagoEm,
            'id' => $id,
        ]);
    }

    public function updateCliente(int $id, array $data): bool
    {
        $statement = $this->db->prepare(
            "UPDATE loja_pedidos
             SET cliente_nome = :nome,
                 cliente_email = :email,
                 cliente_telefone = :telefone,
                 entrega_modo = :modo,
                 cep = :cep,
                 logradouro = :logradouro,
                 numero = :numero,
                 complemento = :complemento,
                 bairro = :bairro,
                 cidade = :cidade,
                 estado = :estado,
                 observacoes = :observacoes,
                 atualizado_em = NOW()
             WHERE id = :id AND status = 'pendente'"
        );

        return $statement->execute([
            'nome' => $data['nome'],
            'email' => $data['email'] ?? null,
            'telefone' => $data['telefone'] ?? null,
            'modo' => $data['modo'] ?? 'retirada',
            'cep' => $data['cep'] ?? null,
            'logradouro' => $data['logradouro'] ?? null,
            'numero' => $data['numero'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cidade' => $data['cidade'] ?? null,
            'estado' => $data['estado'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
            'id' => $id,
        ]);
    }

    public function updateValores(int $id, float $valorPago, float $saldo): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_pedidos
             SET valor_pago = :valor_pago, saldo = :saldo, atualizado_em = NOW()
             WHERE id = :id'
        );

        return $statement->execute([
            'valor_pago' => number_format($valorPago, 3, '.', ''),
            'saldo' => number_format($saldo, 3, '.', ''),
            'id' => $id,
        ]);
    }

    public function marcarPagamentoNaEntrega(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_pedidos
             SET pagamento_na_entrega = 1, atualizado_em = NOW()
             WHERE id = :id AND status = :status'
        );

        return $statement->execute(['id' => $id, 'status' => 'pendente']);
    }

    public function marcarPago(int $id, string $pagoEm): bool
    {
        $statement = $this->db->prepare(
            "UPDATE loja_pedidos
             SET status = 'pago', pago_em = :pago_em, pagamento_na_entrega = 0,
                 valor_pago = subtotal, saldo = 0, atualizado_em = NOW()
             WHERE id = :id"
        );

        return $statement->execute(['pago_em' => $pagoEm, 'id' => $id]);
    }

    public function marcarEntregue(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_pedidos
             SET entregue_em = COALESCE(entregue_em, NOW()), atualizado_em = NOW()
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_pedidos SET status = :status, atualizado_em = NOW() WHERE id = :id'
        );

        return $statement->execute(['status' => $status, 'id' => $id]);
    }

    public function adicionarItem(int $pedidoId, array $item): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO loja_pedido_itens
                (pedido_id, publicacao_id, item_id, quantidade, preco_unitario, valor_reserva, subtotal, item_nome, item_codigo)
             VALUES
                (:pedido_id, :publicacao_id, :item_id, :quantidade, :preco_unitario, :valor_reserva, :subtotal, :item_nome, :item_codigo)'
        );

        $statement->execute([
            'pedido_id' => $pedidoId,
            'publicacao_id' => $item['publicacao_id'] ?? null,
            'item_id' => $item['item_id'],
            'quantidade' => $item['quantidade'] ?? 1,
            'preco_unitario' => $item['preco_unitario'] ?? 0,
            'valor_reserva' => $item['valor_reserva'] ?? null,
            'subtotal' => $item['subtotal'] ?? 0,
            'item_nome' => $item['item_nome'],
            'item_codigo' => $item['item_codigo'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function itens(int $pedidoId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM loja_pedido_itens WHERE pedido_id = :pedido_id ORDER BY id ASC'
        );
        $statement->execute(['pedido_id' => $pedidoId]);

        return $statement->fetchAll();
    }

    public function expirarVencidos(): int
    {
        $statement = $this->db->prepare(
            "UPDATE loja_pedidos
             SET status = 'expirado', atualizado_em = NOW()
             WHERE status = 'pendente' AND expira_em <= NOW()"
        );
        $statement->execute();

        return $statement->rowCount();
    }
}
