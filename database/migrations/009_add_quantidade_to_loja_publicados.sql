-- Quantidade publicada por item/canal (limitada ao disponivel em estoque)

USE loja_carrinhos;

ALTER TABLE loja_itens_publicados
    ADD COLUMN quantidade INT UNSIGNED NOT NULL DEFAULT 1 AFTER preco_venda;
