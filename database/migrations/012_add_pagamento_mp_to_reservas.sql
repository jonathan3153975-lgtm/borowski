-- Dados de pagamento do Mercado Pago nas reservas/pedidos

USE loja_carrinhos;

ALTER TABLE loja_reservas
    ADD COLUMN mp_payment_id VARCHAR(64) NULL DEFAULT NULL AFTER metodo_pagamento,
    ADD COLUMN mp_status VARCHAR(30) NULL DEFAULT NULL AFTER mp_payment_id,
    ADD COLUMN pago_em DATETIME NULL DEFAULT NULL AFTER mp_status;
