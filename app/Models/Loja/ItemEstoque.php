<?php

declare(strict_types=1);

namespace App\Models\Loja;

use App\Core\Model;
use PDO;

class ItemEstoque extends Model
{
    protected string $table = 'loja_itens';

    public function listByEntrada(int $entradaId): array
    {
        $statement = $this->db->prepare(
            'SELECT i.*,
                cat.nome AS categoria_nome, mar.nome AS marca_nome,
                fab.nome AS fabricante_nome, col.nome AS colecao_nome,
                esc.nome AS escala_nome,
                (SELECT li.id FROM loja_item_imagens li
                    WHERE li.item_id = i.id
                    ORDER BY li.imagem_principal DESC, li.ordem_exibicao ASC, li.id ASC
                    LIMIT 1) AS imagem_id,
                (SELECT COUNT(*) FROM loja_item_imagens li
                    WHERE li.item_id = i.id) AS imagens_count,
                (SELECT f.fornecedor FROM loja_item_fornecedores f
                    WHERE f.item_id = i.id AND f.selecionado = 1 LIMIT 1) AS fornecedor_selecionado,
                (SELECT MIN(f.preco_custo) FROM loja_item_fornecedores f
                    WHERE f.item_id = i.id) AS menor_oferta,
                (SELECT COUNT(*) FROM loja_item_fornecedores f
                    WHERE f.item_id = i.id) AS ofertas_count
             FROM loja_itens i
             LEFT JOIN categorias cat ON cat.id = i.categoria_id
             LEFT JOIN marcas mar ON mar.id = i.marca_id
             LEFT JOIN fabricantes fab ON fab.id = i.fabricante_id
             LEFT JOIN colecoes col ON col.id = i.colecao_id
             LEFT JOIN escalas esc ON esc.id = i.escala_id
             WHERE i.entrada_id = :entrada_id
             ORDER BY i.id ASC'
        );
        $statement->execute(['entrada_id' => $entradaId]);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT i.*, e.modo AS entrada_modo, e.recebido AS entrada_recebido,
                e.descricao_origem AS entrada_descricao,
                cat.nome AS categoria_nome, mar.nome AS marca_nome,
                fab.nome AS fabricante_nome, col.nome AS colecao_nome,
                esc.nome AS escala_nome
             FROM loja_itens i
             INNER JOIN loja_entradas e ON e.id = i.entrada_id
             LEFT JOIN categorias cat ON cat.id = i.categoria_id
             LEFT JOIN marcas mar ON mar.id = i.marca_id
             LEFT JOIN fabricantes fab ON fab.id = i.fabricante_id
             LEFT JOIN colecoes col ON col.id = i.colecao_id
             LEFT JOIN escalas esc ON esc.id = i.escala_id
             WHERE i.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO loja_itens
                (entrada_id, categoria_id, marca_id, fabricante_id, colecao_id, escala_id, ano_fabricacao,
                 nome, descricao, codigo, quantidade, custo_unitario_base,
                 custo_adicional_rateado, custo_total_real, custo_unitario_real, status, observacoes)
             VALUES
                (:entrada_id, :categoria_id, :marca_id, :fabricante_id, :colecao_id, :escala_id, :ano_fabricacao,
                 :nome, :descricao, :codigo, :quantidade, :custo_unitario_base,
                 :custo_adicional_rateado, :custo_total_real, :custo_unitario_real, :status, :observacoes)'
        );

        $statement->execute([
            'entrada_id' => $data['entrada_id'],
            'categoria_id' => $data['categoria_id'] ?? null,
            'marca_id' => $data['marca_id'] ?? null,
            'fabricante_id' => $data['fabricante_id'] ?? null,
            'colecao_id' => $data['colecao_id'] ?? null,
            'escala_id' => $data['escala_id'] ?? null,
            'ano_fabricacao' => $data['ano_fabricacao'] ?? null,
            'nome' => $data['nome'],
            'descricao' => $data['descricao'] ?? null,
            'codigo' => $data['codigo'] ?? null,
            'quantidade' => $data['quantidade'],
            'custo_unitario_base' => $data['custo_unitario_base'] ?? '0.000',
            'custo_adicional_rateado' => $data['custo_adicional_rateado'] ?? '0.000',
            'custo_total_real' => $data['custo_total_real'] ?? '0.000',
            'custo_unitario_real' => $data['custo_unitario_real'] ?? '0.000',
            'status' => $data['status'],
            'observacoes' => $data['observacoes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'categoria_id', 'marca_id', 'fabricante_id', 'colecao_id', 'escala_id', 'ano_fabricacao',
            'nome', 'descricao', 'codigo', 'quantidade', 'custo_unitario_base',
            'custo_adicional_rateado', 'custo_total_real', 'custo_unitario_real',
            'status', 'observacoes',
        ];
        $data = array_intersect_key($data, array_flip($allowed));

        if ($data === []) {
            return false;
        }

        $assignments = implode(', ', array_map(
            static fn (string $column): string => "{$column} = :{$column}",
            array_keys($data)
        ));

        $data['id'] = $id;

        $statement = $this->db->prepare(
            "UPDATE loja_itens SET {$assignments} WHERE id = :id"
        );

        return $statement->execute($data);
    }

    public function updateCosts(int $id, array $costs): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_itens
             SET custo_adicional_rateado = :rateado,
                 custo_total_real = :total_real,
                 custo_unitario_real = :unitario_real
             WHERE id = :id'
        );

        return $statement->execute([
            'rateado' => $costs['custo_adicional_rateado'],
            'total_real' => $costs['custo_total_real'],
            'unitario_real' => $costs['custo_unitario_real'],
            'id' => $id,
        ]);
    }

    public function updateStatusByEntrada(int $entradaId, string $status): bool
    {
        $statement = $this->db->prepare(
            "UPDATE loja_itens SET status = :status
             WHERE entrada_id = :entrada_id AND status IN ('disponivel', 'em_transporte')"
        );

        return $statement->execute([
            'status' => $status,
            'entrada_id' => $entradaId,
        ]);
    }

    public function delete(int $id): bool
    {
        $statement = $this->db->prepare('DELETE FROM loja_itens WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }

    public function listarEstoque(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $statement = $this->db->prepare(
            "SELECT i.*, e.descricao_origem AS entrada_descricao, e.modo AS entrada_modo,
                cat.nome AS categoria_nome, mar.nome AS marca_nome,
                fab.nome AS fabricante_nome, col.nome AS colecao_nome,
                esc.nome AS escala_nome,
                (SELECT li.id FROM loja_item_imagens li
                    WHERE li.item_id = i.id
                    ORDER BY li.imagem_principal DESC, li.ordem_exibicao ASC, li.id ASC
                    LIMIT 1) AS imagem_id,
                (SELECT COUNT(*) FROM loja_item_imagens li
                    WHERE li.item_id = i.id) AS imagens_count,
                (SELECT f.fornecedor FROM loja_item_fornecedores f
                    WHERE f.item_id = i.id AND f.selecionado = 1 LIMIT 1) AS fornecedor_selecionado,
                (SELECT MIN(f.preco_custo) FROM loja_item_fornecedores f
                    WHERE f.item_id = i.id) AS menor_oferta
             FROM loja_itens i
             INNER JOIN loja_entradas e ON e.id = i.entrada_id
             LEFT JOIN categorias cat ON cat.id = i.categoria_id
             LEFT JOIN marcas mar ON mar.id = i.marca_id
             LEFT JOIN fabricantes fab ON fab.id = i.fabricante_id
             LEFT JOIN colecoes col ON col.id = i.colecao_id
             LEFT JOIN escalas esc ON esc.id = i.escala_id
             WHERE {$where}
             ORDER BY i.criado_em DESC, i.id DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value, PDO::PARAM_STR);
        }

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function contarEstoque(array $filters): int
    {
        [$where, $params] = $this->buildFilters($filters);

        $statement = $this->db->prepare(
            "SELECT COUNT(*) FROM loja_itens i
             INNER JOIN loja_entradas e ON e.id = i.entrada_id
             WHERE {$where}"
        );

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value, PDO::PARAM_STR);
        }

        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function resumoEstoque(): array
    {
        $record = $this->db->query(
            "SELECT COUNT(*) AS total_itens,
                COALESCE(SUM(quantidade), 0) AS total_unidades,
                COALESCE(SUM(CASE WHEN status = 'disponivel' THEN quantidade ELSE 0 END), 0) AS unidades_disponivel,
                COALESCE(SUM(CASE WHEN status = 'em_transporte' THEN quantidade ELSE 0 END), 0) AS unidades_em_transporte,
                COALESCE(SUM(CASE WHEN status = 'pre_venda' THEN quantidade ELSE 0 END), 0) AS unidades_pre_venda,
                COALESCE(SUM(custo_total_real), 0) AS valor_total_real
             FROM loja_itens"
        )->fetch();

        return $record === false ? [] : $record;
    }

    private function buildFilters(array $filters): array
    {
        $conditions = ['1 = 1'];
        $params = [];

        $status = (string) ($filters['status'] ?? '');

        if (in_array($status, ['disponivel', 'em_transporte', 'pre_venda'], true)) {
            $conditions[] = 'i.status = :status';
            $params[':status'] = $status;
        }

        $busca = trim((string) ($filters['busca'] ?? ''));

        if ($busca !== '') {
            $conditions[] = '(i.nome LIKE :busca_nome OR i.codigo LIKE :busca_codigo OR e.descricao_origem LIKE :busca_entrada)';
            $params[':busca_nome'] = '%' . $busca . '%';
            $params[':busca_codigo'] = '%' . $busca . '%';
            $params[':busca_entrada'] = '%' . $busca . '%';
        }

        return [implode(' AND ', $conditions), $params];
    }
}
