<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Configuracao extends Model
{
    private const TABLES = ['categorias', 'marcas', 'fabricantes', 'colecoes', 'escalas'];
    private const ITEM_FOREIGN_KEYS = [
        'categoria_id',
        'marca_id',
        'fabricante_id',
        'colecao_id',
        'escala_id',
    ];

    public function listar(string $table): array
    {
        $this->assertTable($table);

        return $this->db
            ->query("SELECT * FROM {$table} ORDER BY nome ASC")
            ->fetchAll();
    }

    public function buscar(string $table, int $id): ?array
    {
        $this->assertTable($table);

        $statement = $this->db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $statement->execute(['id' => $id]);

        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public function create(string $table, array $data): int
    {
        $this->assertTable($table);

        $statement = $this->db->prepare(
            "INSERT INTO {$table} (nome, descricao, status)
             VALUES (:nome, :descricao, :status)"
        );

        $statement->execute([
            'nome' => $data['nome'],
            'descricao' => $data['descricao'] ?? null,
            'status' => $data['status'] ?? 'ativo',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(string $table, int $id, array $data): bool
    {
        $this->assertTable($table);

        $statement = $this->db->prepare(
            "UPDATE {$table}
             SET nome = :nome, descricao = :descricao, status = :status
             WHERE id = :id"
        );

        return $statement->execute([
            'nome' => $data['nome'],
            'descricao' => $data['descricao'] ?? null,
            'status' => $data['status'] ?? 'ativo',
            'id' => $id,
        ]);
    }

    public function updateStatus(string $table, int $id, string $status): bool
    {
        $this->assertTable($table);

        $statement = $this->db->prepare(
            "UPDATE {$table} SET status = :status WHERE id = :id"
        );

        return $statement->execute(['status' => $status, 'id' => $id]);
    }

    public function delete(string $table, int $id): bool
    {
        $this->assertTable($table);

        $statement = $this->db->prepare("DELETE FROM {$table} WHERE id = :id");

        return $statement->execute(['id' => $id]);
    }

    public function nomeExists(string $table, string $nome, ?int $ignoreId = null): bool
    {
        $this->assertTable($table);

        if ($ignoreId === null) {
            $statement = $this->db->prepare(
                "SELECT 1 FROM {$table} WHERE nome = :nome LIMIT 1"
            );
            $statement->execute(['nome' => $nome]);
        } else {
            $statement = $this->db->prepare(
                "SELECT 1 FROM {$table} WHERE nome = :nome AND id <> :id LIMIT 1"
            );
            $statement->execute(['nome' => $nome, 'id' => $ignoreId]);
        }

        return $statement->fetchColumn() !== false;
    }

    public function countItens(string $table, int $id, string $foreignKey): int
    {
        $this->assertTable($table);

        if (!in_array($foreignKey, self::ITEM_FOREIGN_KEYS, true)) {
            throw new \RuntimeException('Chave estrangeira inválida.');
        }

        $statement = $this->db->prepare(
            "SELECT COUNT(*) FROM loja_itens WHERE {$foreignKey} = :id"
        );
        $statement->execute(['id' => $id]);

        return (int) $statement->fetchColumn();
    }

    private function assertTable(string $table): void
    {
        if (!in_array($table, self::TABLES, true)) {
            throw new \RuntimeException('Tabela de configuração inválida.');
        }
    }
}
