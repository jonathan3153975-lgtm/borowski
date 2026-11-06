-- Informacoes de catalogo diretamente nos itens de entrada/estoque
-- Substitui a necessidade de vincular o item a um produto cadastrado

USE loja_carrinhos;

ALTER TABLE loja_itens
    ADD COLUMN categoria_id INT UNSIGNED NULL DEFAULT NULL AFTER produto_id,
    ADD COLUMN marca_id INT UNSIGNED NULL DEFAULT NULL AFTER categoria_id,
    ADD COLUMN fabricante_id INT UNSIGNED NULL DEFAULT NULL AFTER marca_id,
    ADD COLUMN colecao_id INT UNSIGNED NULL DEFAULT NULL AFTER fabricante_id,
    ADD COLUMN escala_id INT UNSIGNED NULL DEFAULT NULL AFTER colecao_id,
    ADD COLUMN ano_fabricacao SMALLINT UNSIGNED NULL DEFAULT NULL AFTER escala_id,
    ADD KEY idx_loja_itens_categoria (categoria_id),
    ADD KEY idx_loja_itens_marca (marca_id),
    ADD KEY idx_loja_itens_fabricante (fabricante_id),
    ADD KEY idx_loja_itens_colecao (colecao_id),
    ADD KEY idx_loja_itens_escala (escala_id),
    ADD CONSTRAINT fk_loja_itens_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT fk_loja_itens_marca
        FOREIGN KEY (marca_id) REFERENCES marcas (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT fk_loja_itens_fabricante
        FOREIGN KEY (fabricante_id) REFERENCES fabricantes (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT fk_loja_itens_colecao
        FOREIGN KEY (colecao_id) REFERENCES colecoes (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    ADD CONSTRAINT fk_loja_itens_escala
        FOREIGN KEY (escala_id) REFERENCES escalas (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
