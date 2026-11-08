-- Itens sob demanda (sem entrada), pre-venda com valor de reserva
-- e snapshots de valores nos pedidos/reservas.

USE loja_carrinhos;

ALTER TABLE loja_itens
    MODIFY COLUMN entrada_id BIGINT UNSIGNED NULL DEFAULT NULL;

ALTER TABLE loja_itens_publicados
    ADD COLUMN preco_reserva DECIMAL(12,3) NULL DEFAULT NULL AFTER preco_venda;

ALTER TABLE loja_reservas
    ADD COLUMN preco_venda DECIMAL(12,3) NULL DEFAULT NULL AFTER quantidade,
    ADD COLUMN valor_reserva DECIMAL(12,3) NULL DEFAULT NULL AFTER preco_venda;
