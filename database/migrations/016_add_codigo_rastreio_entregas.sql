-- Codigo de rastreio opcional nas entregas

USE loja_carrinhos;

ALTER TABLE loja_entregas
    ADD COLUMN codigo_rastreio VARCHAR(120) NULL AFTER status;
