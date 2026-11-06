-- Integracao entre itens de entrada/estoque e o cadastro de produtos
-- Adiciona produto_id em loja_itens sem remover ou recriar a tabela

USE loja_carrinhos;

ALTER TABLE loja_itens
    ADD COLUMN produto_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER entrada_id,
    ADD KEY idx_loja_itens_produto (produto_id),
    ADD CONSTRAINT fk_loja_itens_produto
        FOREIGN KEY (produto_id) REFERENCES produtos (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
