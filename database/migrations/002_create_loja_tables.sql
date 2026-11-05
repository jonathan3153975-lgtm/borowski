-- Migration do modulo Loja - Etapa 02
-- Tabelas: loja_entradas, loja_entrada_custos, loja_itens, loja_item_fornecedores
-- Engine InnoDB, charset utf8mb4 e collation utf8mb4_unicode_ci
-- Execucao idempotente com CREATE TABLE IF NOT EXISTS
-- Alteracoes futuras devem ser feitas em novas migrations

CREATE DATABASE IF NOT EXISTS loja_carrinhos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE loja_carrinhos;

CREATE TABLE IF NOT EXISTS loja_entradas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    modo ENUM('direta', 'previsao') NOT NULL,
    descricao_origem VARCHAR(180) NOT NULL,
    link_compra VARCHAR(500) NULL DEFAULT NULL,
    recebido TINYINT(1) NOT NULL DEFAULT 0,
    codigo_rastreio VARCHAR(120) NULL DEFAULT NULL,
    observacoes TEXT NULL,
    criado_por INT UNSIGNED NULL DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_loja_entradas_modo (modo),
    KEY idx_loja_entradas_recebido (recebido),
    KEY idx_loja_entradas_criado_em (criado_em),
    KEY idx_loja_entradas_criado_por (criado_por),
    CONSTRAINT fk_loja_entradas_usuario
        FOREIGN KEY (criado_por) REFERENCES usuarios (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loja_entrada_custos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entrada_id BIGINT UNSIGNED NOT NULL,
    descricao VARCHAR(150) NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_loja_custos_entrada (entrada_id),
    CONSTRAINT fk_loja_custos_entrada
        FOREIGN KEY (entrada_id) REFERENCES loja_entradas (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loja_itens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entrada_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(180) NOT NULL,
    descricao TEXT NULL,
    codigo VARCHAR(80) NULL DEFAULT NULL,
    quantidade INT UNSIGNED NOT NULL DEFAULT 1,
    custo_unitario_base DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    custo_adicional_rateado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    custo_total_real DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    custo_unitario_real DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('disponivel', 'em_transporte', 'pre_venda') NOT NULL,
    observacoes TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_loja_itens_entrada (entrada_id),
    KEY idx_loja_itens_status (status),
    KEY idx_loja_itens_nome (nome),
    KEY idx_loja_itens_codigo (codigo),
    CONSTRAINT fk_loja_itens_entrada
        FOREIGN KEY (entrada_id) REFERENCES loja_entradas (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loja_item_fornecedores (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    item_id BIGINT UNSIGNED NOT NULL,
    fornecedor VARCHAR(150) NOT NULL,
    url VARCHAR(500) NOT NULL,
    preco_custo DECIMAL(12,2) NOT NULL,
    selecionado TINYINT(1) NOT NULL DEFAULT 0,
    observacoes VARCHAR(255) NULL DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_loja_ofertas_item (item_id),
    KEY idx_loja_ofertas_fornecedor (fornecedor),
    CONSTRAINT fk_loja_ofertas_item
        FOREIGN KEY (item_id) REFERENCES loja_itens (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
