-- Campos de apoio para venda direta, caixa e controle de entrega

USE loja_carrinhos;

ALTER TABLE loja_pedidos
    ADD COLUMN pagamento_na_entrega TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN metodo_pagamento VARCHAR(30) NULL AFTER pagamento_na_entrega,
    ADD COLUMN origem VARCHAR(20) NOT NULL DEFAULT 'loja' AFTER metodo_pagamento,
    ADD COLUMN entregue_em DATETIME NULL AFTER pago_em;
