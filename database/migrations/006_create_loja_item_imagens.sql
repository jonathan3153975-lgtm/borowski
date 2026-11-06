-- Imagens dos itens de entrada/estoque
-- Arquivos ficam em storage/uploads/loja_itens e sao servidos por rota autenticada

USE loja_carrinhos;

CREATE TABLE IF NOT EXISTS loja_item_imagens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    item_id BIGINT UNSIGNED NOT NULL,
    caminho_imagem VARCHAR(255) NOT NULL,
    nome_original VARCHAR(180) NOT NULL,
    imagem_principal TINYINT(1) NOT NULL DEFAULT 0,
    ordem_exibicao INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_loja_item_imagens_item (item_id),
    KEY idx_loja_item_imagens_principal (item_id, imagem_principal),
    KEY idx_loja_item_imagens_ordem (item_id, ordem_exibicao),
    CONSTRAINT fk_loja_item_imagens_item
        FOREIGN KEY (item_id) REFERENCES loja_itens (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
