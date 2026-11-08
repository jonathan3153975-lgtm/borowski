-- Loja virtual publica: pedidos online, itens e vinculo com reservas

USE loja_carrinhos;

CREATE TABLE IF NOT EXISTS loja_pedidos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(24) NOT NULL,
    cliente_nome VARCHAR(180) NOT NULL,
    cliente_email VARCHAR(180) NULL,
    cliente_telefone VARCHAR(30) NULL,
    entrega_modo ENUM('retirada', 'envio') NOT NULL DEFAULT 'retirada',
    cep VARCHAR(9) NULL,
    logradouro VARCHAR(200) NULL,
    numero VARCHAR(20) NULL,
    complemento VARCHAR(120) NULL,
    bairro VARCHAR(120) NULL,
    cidade VARCHAR(120) NULL,
    estado VARCHAR(2) NULL,
    observacoes VARCHAR(2000) NULL,
    subtotal DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    frete DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    total DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    status ENUM('pendente', 'pago', 'cancelado', 'expirado') NOT NULL DEFAULT 'pendente',
    mp_preference_id VARCHAR(120) NULL,
    mp_link VARCHAR(500) NULL,
    mp_payment_id VARCHAR(64) NULL,
    mp_status VARCHAR(30) NULL,
    pago_em DATETIME NULL,
    expira_em DATETIME NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL,
    UNIQUE KEY uk_loja_pedidos_codigo (codigo),
    KEY idx_loja_pedidos_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loja_pedido_itens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id BIGINT UNSIGNED NOT NULL,
    publicacao_id BIGINT UNSIGNED NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    quantidade INT UNSIGNED NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    subtotal DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    item_nome VARCHAR(180) NOT NULL,
    item_codigo VARCHAR(80) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_loja_pedido_itens_pedido (pedido_id),
    CONSTRAINT fk_loja_pedido_itens_pedido
        FOREIGN KEY (pedido_id) REFERENCES loja_pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE loja_reservas
    ADD COLUMN pedido_id BIGINT UNSIGNED NULL AFTER entrega_id,
    ADD INDEX idx_loja_reservas_pedido (pedido_id);
