<?php

declare(strict_types=1);

namespace App\Models\Loja;

use App\Core\Model;
use PDO;

class Entrada extends Model
{
    protected string $table = 'loja_entradas';

    public function findDetalhada(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT e.*, u.nome AS criado_por_nome,
                (SELECT COUNT(*) FROM loja_itens i WHERE i.entrada_id = e.id) AS itens_count,
                (SELECT COALESCE(SUM(c.valor), 0) FROM loja_entrada_custos c WHERE c.entrada_id = e.id) AS custos_total,
                (SELECT COALESCE(SUM(i.custo_total_real), 0) FROM loja_itens i WHERE i.entrada_id = e.id) AS total_real
             FROM loja_entradas e
             LEFT JOIN usuarios u ON u.id = e.criado_por
             WHERE e.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public function listar(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $statement = $this->db->prepare(
            "SELECT e.*, u.nome AS criado_por_nome,
                (SELECT COUNT(*) FROM loja_itens i WHERE i.entrada_id = e.id) AS itens_count,
                (SELECT COALESCE(SUM(c.valor), 0) FROM loja_entrada_custos c WHERE c.entrada_id = e.id) AS custos_total,
                (SELECT COALESCE(SUM(i.custo_total_real), 0) FROM loja_itens i WHERE i.entrada_id = e.id) AS total_real
             FROM loja_entradas e
             LEFT JOIN usuarios u ON u.id = e.criado_por
             WHERE {$where}
             ORDER BY e.criado_em DESC, e.id DESC
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

    public function contar(array $filters): int
    {
        [$where, $params] = $this->buildFilters($filters);

        $statement = $this->db->prepare(
            "SELECT COUNT(*) FROM loja_entradas e WHERE {$where}"
        );

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value, PDO::PARAM_STR);
        }

        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO loja_entradas
                (modo, descricao_origem, link_compra, recebido, codigo_rastreio, observacoes, criado_por)
             VALUES
                (:modo, :descricao_origem, :link_compra, :recebido, :codigo_rastreio, :observacoes, :criado_por)'
        );

        $statement->execute([
            'modo' => $data['modo'],
            'descricao_origem' => $data['descricao_origem'],
            'link_compra' => $data['link_compra'] ?? null,
            'recebido' => $data['recebido'] ?? 0,
            'codigo_rastreio' => $data['codigo_rastreio'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
            'criado_por' => $data['criado_por'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['descricao_origem', 'link_compra', 'recebido', 'codigo_rastreio', 'observacoes'];
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
            "UPDATE loja_entradas SET {$assignments} WHERE id = :id"
        );

        return $statement->execute($data);
    }

    public function updateRecebido(int $id, bool $recebido): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_entradas SET recebido = :recebido WHERE id = :id'
        );

        return $statement->execute([
            'recebido' => $recebido ? 1 : 0,
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $statement = $this->db->prepare('DELETE FROM loja_entradas WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }

    private function buildFilters(array $filters): array
    {
        $conditions = ['1 = 1'];
        $params = [];

        $modo = (string) ($filters['modo'] ?? '');

        if (in_array($modo, ['direta', 'previsao'], true)) {
            $conditions[] = 'e.modo = :modo';
            $params[':modo'] = $modo;
        }

        $recebido = (string) ($filters['recebido'] ?? '');

        if ($recebido === '0' || $recebido === '1') {
            $conditions[] = 'e.recebido = :recebido';
            $params[':recebido'] = $recebido;
        }

        $busca = trim((string) ($filters['busca'] ?? ''));

        if ($busca !== '') {
            $conditions[] = '(e.descricao_origem LIKE :busca_descricao OR e.link_compra LIKE :busca_link OR e.codigo_rastreio LIKE :busca_rastreio)';
            $params[':busca_descricao'] = '%' . $busca . '%';
            $params[':busca_link'] = '%' . $busca . '%';
            $params[':busca_rastreio'] = '%' . $busca . '%';
        }

        return [implode(' AND ', $conditions), $params];
    }
}
