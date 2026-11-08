-- Cobranca do saldo restante de pre-venda

USE loja_carrinhos;

ALTER TABLE loja_reservas
    ADD COLUMN saldo_link VARCHAR(500) NULL DEFAULT NULL AFTER pago_em,
    ADD COLUMN saldo_cobranca_em DATETIME NULL DEFAULT NULL AFTER saldo_link,
    ADD COLUMN saldo_pago_em DATETIME NULL DEFAULT NULL AFTER saldo_cobranca_em,
    ADD COLUMN saldo_mp_status VARCHAR(30) NULL DEFAULT NULL AFTER saldo_pago_em,
    ADD COLUMN saldo_mp_payment_id VARCHAR(64) NULL DEFAULT NULL AFTER saldo_mp_status;
