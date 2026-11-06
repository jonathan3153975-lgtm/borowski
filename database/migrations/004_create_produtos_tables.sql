-- Modulo de catalogo de produtos
-- Tabelas auxiliares, produtos e imagens
-- Idempotente com CREATE TABLE IF NOT EXISTS

USE loja_carrinhos;

CREATE TABLE IF NOT EXISTS categorias (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    descricao VARCHAR(255) NULL DEFAULT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_categorias_nome (nome),
    KEY idx_categorias_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marcas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    descricao VARCHAR(255) NULL DEFAULT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_marcas_nome (nome),
    KEY idx_marcas_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fabricantes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    descricao VARCHAR(255) NULL DEFAULT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_fabricantes_nome (nome),
    KEY idx_fabricantes_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS colecoes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    descricao VARCHAR(255) NULL DEFAULT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_colecoes_nome (nome),
    KEY idx_colecoes_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS escalas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    descricao VARCHAR(255) NULL DEFAULT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_escalas_nome (nome),
    KEY idx_escalas_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produtos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(180) NOT NULL,
    sku VARCHAR(80) NULL DEFAULT NULL,
    descricao_resumida VARCHAR(255) NULL DEFAULT NULL,
    descricao_completa TEXT NULL,
    categoria_id INT UNSIGNED NOT NULL,
    marca_id INT UNSIGNED NULL DEFAULT NULL,
    fabricante_id INT UNSIGNED NULL DEFAULT NULL,
    colecao_id INT UNSIGNED NULL DEFAULT NULL,
    escala_id INT UNSIGNED NULL DEFAULT NULL,
    modelo VARCHAR(120) NULL DEFAULT NULL,
    cor_predominante VARCHAR(60) NULL DEFAULT NULL,
    ano_fabricacao SMALLINT UNSIGNED NULL DEFAULT NULL,
    material_predominante VARCHAR(80) NULL DEFAULT NULL,
    codigo_referencia_fabricante VARCHAR(80) NULL DEFAULT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    fabricante_veiculo VARCHAR(120) NULL DEFAULT NULL,
    modelo_veiculo VARCHAR(120) NULL DEFAULT NULL,
    ano_veiculo SMALLINT UNSIGNED NULL DEFAULT NULL,
    tipo_veiculo VARCHAR(60) NULL DEFAULT NULL,
    edicao_especial TINYINT(1) NOT NULL DEFAULT 0,
    unidades_edicao INT UNSIGNED NULL DEFAULT NULL,
    lacrado ENUM('lacrado', 'aberto') NULL DEFAULT NULL,
    embalagem_original TINYINT(1) NULL DEFAULT NULL,
    nivel_conservacao VARCHAR(80) NULL DEFAULT NULL,
    observacoes TEXT NULL,
    preco_venda DECIMAL(12,3) NULL DEFAULT NULL,
    preco_visivel TINYINT(1) NOT NULL DEFAULT 0,
    destaque TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_produtos_sku (sku),
    KEY idx_produtos_nome (nome),
    KEY idx_produtos_status (status),
    KEY idx_produtos_categoria (categoria_id),
    KEY idx_produtos_marca (marca_id),
    KEY idx_produtos_colecao (colecao_id),
    KEY idx_produtos_escala (escala_id),
    KEY idx_produtos_destaque (destaque),
    CONSTRAINT fk_produtos_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_produtos_marca
        FOREIGN KEY (marca_id) REFERENCES marcas (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_produtos_fabricante
        FOREIGN KEY (fabricante_id) REFERENCES fabricantes (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_produtos_colecao
        FOREIGN KEY (colecao_id) REFERENCES colecoes (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_produtos_escala
        FOREIGN KEY (escala_id) REFERENCES escalas (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produtos_imagens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    produto_id BIGINT UNSIGNED NOT NULL,
    caminho_imagem VARCHAR(255) NOT NULL,
    nome_original VARCHAR(180) NOT NULL,
    imagem_principal TINYINT(1) NOT NULL DEFAULT 0,
    ordem_exibicao INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_produtos_imagens_produto (produto_id),
    KEY idx_produtos_imagens_principal (produto_id, imagem_principal),
    KEY idx_produtos_imagens_ordem (produto_id, ordem_exibicao),
    CONSTRAINT fk_produtos_imagens_produto
        FOREIGN KEY (produto_id) REFERENCES produtos (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
