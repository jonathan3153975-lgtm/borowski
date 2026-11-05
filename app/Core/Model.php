<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $table = '';

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare("SELECT * FROM {$this->tableName()} WHERE id = :id LIMIT 1");
        $statement->execute(['id' => $id]);

        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public function all(): array
    {
        return $this->db->query("SELECT * FROM {$this->tableName()}")->fetchAll();
    }

    protected function tableName(): string
    {
        if (preg_match('/^[a-zA-Z0-9_]+$/', $this->table) !== 1) {
            throw new \RuntimeException('Nome de tabela inválido.');
        }

        return $this->table;
    }
}
