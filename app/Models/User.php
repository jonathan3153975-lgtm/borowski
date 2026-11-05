<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'usuarios';

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM usuarios WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);

        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public function search(string $term = ''): array
    {
        if ($term === '') {
            return $this->db
                ->query('SELECT * FROM usuarios ORDER BY nome ASC')
                ->fetchAll();
        }

        $statement = $this->db->prepare(
            'SELECT * FROM usuarios
             WHERE nome LIKE :term OR email LIKE :term
             ORDER BY nome ASC'
        );
        $statement->execute(['term' => '%' . $term . '%']);

        return $statement->fetchAll();
    }

    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        if ($ignoreId === null) {
            $statement = $this->db->prepare(
                'SELECT 1 FROM usuarios WHERE email = :email LIMIT 1'
            );
            $statement->execute(['email' => $email]);
        } else {
            $statement = $this->db->prepare(
                'SELECT 1 FROM usuarios WHERE email = :email AND id <> :id LIMIT 1'
            );
            $statement->execute(['email' => $email, 'id' => $ignoreId]);
        }

        return $statement->fetchColumn() !== false;
    }

    public function countSuperAdmins(): int
    {
        return (int) $this->db
            ->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'super_admin'")
            ->fetchColumn();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO usuarios (nome, email, senha, tipo, status)
             VALUES (:nome, :email, :senha, :tipo, :status)'
        );

        $statement->execute([
            'nome' => $data['nome'],
            'email' => $data['email'],
            'senha' => $data['senha'],
            'tipo' => $data['tipo'],
            'status' => $data['status'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['nome', 'email', 'senha'];
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
            "UPDATE usuarios SET {$assignments} WHERE id = :id"
        );

        return $statement->execute($data);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $statement = $this->db->prepare(
            'UPDATE usuarios SET status = :status WHERE id = :id'
        );

        return $statement->execute(['status' => $status, 'id' => $id]);
    }

    public function updateLastAccess(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    public function delete(int $id): bool
    {
        $statement = $this->db->prepare('DELETE FROM usuarios WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }
}
