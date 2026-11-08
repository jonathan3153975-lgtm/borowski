-- Pagamento na entrega (pedido habilitado para entrega, ainda nao pago)
-- e status "finalizada" para entregas concluidas

USE loja_carrinhos;

ALTER TABLE loja_reservas
    ADD COLUMN pagamento_na_entrega TINYINT(1) NOT NULL DEFAULT 0 AFTER saldo_mp_payment_id;

ALTER TABLE loja_entregas
    MODIFY COLUMN status ENUM('pendente', 'enviada', 'entregue', 'cancelada', 'finalizada')
    NOT NULL DEFAULT 'pendente';
