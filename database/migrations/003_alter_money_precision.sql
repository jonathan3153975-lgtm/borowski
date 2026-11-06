-- Ajuste de precisao monetaria para 3 casas decimais
-- Aplica DECIMAL(12,3) nas colunas financeiras do modulo Loja
-- Idempotente para execucao controlada pelo runner de migrations

USE loja_carrinhos;

ALTER TABLE loja_entrada_custos
    MODIFY COLUMN valor DECIMAL(12,3) NOT NULL;

ALTER TABLE loja_itens
    MODIFY COLUMN custo_unitario_base DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    MODIFY COLUMN custo_adicional_rateado DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    MODIFY COLUMN custo_total_real DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    MODIFY COLUMN custo_unitario_real DECIMAL(12,3) NOT NULL DEFAULT 0.000;

ALTER TABLE loja_item_fornecedores
    MODIFY COLUMN preco_custo DECIMAL(12,3) NOT NULL;
