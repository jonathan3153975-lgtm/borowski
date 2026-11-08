-- Entregas: cliente/endereco reutilizavel, entrega e itens da entrega
-- Reservas encaminhadas para entrega recebem entrega_id e saem da tela de Vendas.

USE loja_carrinhos;

CREATE TABLE IF NOT EXISTS loja_clientes_entrega (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    telegram_user_id BIGINT NULL DEFAULT NULL,
    nome VARCHAR(180) NOT NULL,
    cpf VARCHAR(20) NULL DEFAULT NULL,
    telefone VARCHAR(30) NULL DEFAULT NULL,
    cep VARCHAR(10) NULL DEFAULT NULL,
    logradouro VARCHAR(200) NULL DEFAULT NULL,
    numero VARCHAR(20) NULL DEFAULT NULL,
    complemento VARCHAR(120) NULL DEFAULT NULL,
    bairro VARCHAR(120) NULL DEFAULT NULL,
    cidade VARCHAR(120) NULL DEFAULT NULL,
    estado CHAR(2) NULL DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_clientes_entrega_telegram (telegram_user_id),
    KEY idx_clientes_entrega_cpf (cpf),
    KEY idx_clientes_entrega_nome_tel (nome, telefone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loja_entregas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cliente_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pendente', 'enviada', 'entregue', 'cancelada') NOT NULL DEFAULT 'pendente',
    observacoes TEXT NULL,
    criado_por INT UNSIGNED NULL DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_entregas_cliente (cliente_id),
    KEY idx_entregas_status (status),
    CONSTRAINT fk_entregas_cliente
        FOREIGN KEY (cliente_id) REFERENCES loja_clientes_entrega (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_entregas_usuario
        FOREIGN KEY (criado_por) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loja_entrega_itens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entrega_id BIGINT UNSIGNED NOT NULL,
    reserva_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    quantidade INT UNSIGNED NOT NULL DEFAULT 1,
    preco_venda DECIMAL(12,3) NULL DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_entrega_itens_reserva (reserva_id),
    KEY idx_entrega_itens_entrega (entrega_id),
    CONSTRAINT fk_entrega_itens_entrega
        FOREIGN KEY (entrega_id) REFERENCES loja_entregas (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_entrega_itens_reserva
        FOREIGN KEY (reserva_id) REFERENCES loja_reservas (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_entrega_itens_item
        FOREIGN KEY (item_id) REFERENCES loja_itens (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE loja_reservas
    ADD COLUMN entrega_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER saldo_mp_payment_id,
    ADD KEY idx_reservas_entrega (entrega_id),
    ADD CONSTRAINT fk_reservas_entrega
        FOREIGN KEY (entrega_id) REFERENCES loja_entregas (id)
        ON DELETE SET NULL ON UPDATE CASCADE;
