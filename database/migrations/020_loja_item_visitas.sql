-- Contabilizacao de visitas por item (loja, Telegram e link de oferta)

USE loja_carrinhos;

CREATE TABLE IF NOT EXISTS loja_item_visitas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id BIGINT UNSIGNED NOT NULL,
    publicacao_id BIGINT UNSIGNED NULL,
    origem ENUM('loja', 'telegram', 'oferta') NOT NULL DEFAULT 'loja',
    telegram_user_id BIGINT NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_loja_item_visitas_item (item_id),
    KEY idx_loja_item_visitas_publicacao (publicacao_id),
    KEY idx_loja_item_visitas_origem (origem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
