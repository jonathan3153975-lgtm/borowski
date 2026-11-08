-- Reserva (pre-venda) nos itens do pedido e valores de pago/saldo no pedido

USE loja_carrinhos;

ALTER TABLE loja_pedido_itens
    ADD COLUMN valor_reserva DECIMAL(12,3) NULL AFTER preco_unitario;

ALTER TABLE loja_pedidos
    ADD COLUMN valor_pago DECIMAL(12,3) NOT NULL DEFAULT 0.000 AFTER total,
    ADD COLUMN saldo DECIMAL(12,3) NOT NULL DEFAULT 0.000 AFTER valor_pago;
