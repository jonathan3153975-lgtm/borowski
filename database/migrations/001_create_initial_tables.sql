-- Migration inicial da Etapa 01
-- Banco: loja_carrinhos
-- Charset: utf8mb4 / Collation: utf8mb4_unicode_ci
-- Engine: InnoDB
-- A execucao e idempotente para as tabelas (CREATE TABLE IF NOT EXISTS).
-- Para alteracoes futuras, criar novas migrations. Nao editar esta em producao.

CREATE DATABASE IF NOT EXISTS loja_carrinhos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE loja_carrinhos;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('super_admin', 'admin') NOT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    ultimo_acesso DATETIME NULL DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_usuarios_email (email),
    KEY idx_usuarios_tipo (tipo),
    KEY idx_usuarios_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_auditoria (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NULL DEFAULT NULL,
    acao VARCHAR(100) NOT NULL,
    descricao TEXT NULL,
    ip VARCHAR(45) NULL DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_logs_auditoria_usuario (usuario_id),
    KEY idx_logs_auditoria_criado (criado_em),
    CONSTRAINT fk_logs_auditoria_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
