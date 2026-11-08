-- Publicacao de itens de estoque em canais (loja, WhatsApp, Telegram)
-- Estrutura multiplataforma: um item pode ter publicacao por canal.

USE loja_carrinhos;

CREATE TABLE IF NOT EXISTS loja_canais (
    id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(30) NOT NULL,
    nome VARCHAR(60) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_loja_canais_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO loja_canais (slug, nome) VALUES
    ('loja', 'Loja virtual'),
    ('whatsapp', 'WhatsApp'),
    ('telegram', 'Telegram');

CREATE TABLE IF NOT EXISTS loja_itens_publicados (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    item_id BIGINT UNSIGNED NOT NULL,
    canal_id TINYINT UNSIGNED NOT NULL,
    preco_venda DECIMAL(12,3) NOT NULL,
    status ENUM('publicado', 'pausado', 'encerrado') NOT NULL DEFAULT 'publicado',
    titulo VARCHAR(180) NULL DEFAULT NULL,
    descricao TEXT NULL,
    publicado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    encerrado_em DATETIME NULL DEFAULT NULL,
    criado_por INT UNSIGNED NULL DEFAULT NULL,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_loja_publicados_item_canal (item_id, canal_id),
    KEY idx_loja_publicados_item (item_id),
    KEY idx_loja_publicados_canal (canal_id),
    KEY idx_loja_publicados_status (status),
    KEY idx_loja_publicados_criado_por (criado_por),
    CONSTRAINT fk_loja_publicados_item
        FOREIGN KEY (item_id) REFERENCES loja_itens (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_loja_publicados_canal
        FOREIGN KEY (canal_id) REFERENCES loja_canais (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_loja_publicados_usuario
        FOREIGN KEY (criado_por) REFERENCES usuarios (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
