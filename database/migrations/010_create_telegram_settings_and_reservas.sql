-- Configuracoes da aplicacao (chave/valor) e reservas de itens
-- Suporta a venda pelo Telegram com reserva de estoque.

USE loja_carrinhos;

CREATE TABLE IF NOT EXISTS configuracoes_app (
    chave VARCHAR(80) NOT NULL,
    valor TEXT NULL,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO configuracoes_app (chave, valor) VALUES
    ('telegram_ativo', '0'),
    ('telegram_bot_token', ''),
    ('telegram_group_chat_id', ''),
    ('telegram_webhook_secret', ''),
    ('telegram_reserva_minutos', '30'),
    ('telegram_last_update_id', '0'),
    ('loja_pix_chave', ''),
    ('loja_pix_titular', ''),
    ('loja_mercadopago_link', '');

CREATE TABLE IF NOT EXISTS loja_reservas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    publicacao_id BIGINT UNSIGNED NULL DEFAULT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    canal_id TINYINT UNSIGNED NOT NULL,
    quantidade INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('reservada', 'confirmada', 'expirada', 'cancelada') NOT NULL DEFAULT 'reservada',
    metodo_pagamento VARCHAR(20) NULL DEFAULT NULL,
    telegram_user_id BIGINT NULL DEFAULT NULL,
    telegram_chat_id BIGINT NULL DEFAULT NULL,
    comprador_nome VARCHAR(180) NULL DEFAULT NULL,
    expira_em DATETIME NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_loja_reservas_publicacao (publicacao_id),
    KEY idx_loja_reservas_status (status),
    KEY idx_loja_reservas_expira (expira_em),
    CONSTRAINT fk_loja_reservas_publicacao
        FOREIGN KEY (publicacao_id) REFERENCES loja_itens_publicados (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_loja_reservas_item
        FOREIGN KEY (item_id) REFERENCES loja_itens (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_loja_reservas_canal
        FOREIGN KEY (canal_id) REFERENCES loja_canais (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
