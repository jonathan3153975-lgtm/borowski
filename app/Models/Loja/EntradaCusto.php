<?php

declare(strict_types=1);

namespace App\Models\Loja;

use App\Core\Model;

class EntradaCusto extends Model
{
    protected string $table = 'loja_entrada_custos';

    public function listByEntrada(int $entradaId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM loja_entrada_custos WHERE entrada_id = :entrada_id ORDER BY id ASC'
        );
        $statement->execute(['entrada_id' => $entradaId]);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM loja_entrada_custos WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public function create(int $entradaId, string $descricao, string $valor): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO loja_entrada_custos (entrada_id, descricao, valor)
             VALUES (:entrada_id, :descricao, :valor)'
        );

        $statement->execute([
            'entrada_id' => $entradaId,
            'descricao' => $descricao,
            'valor' => $valor,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $statement = $this->db->prepare('DELETE FROM loja_entrada_custos WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }

    public function totalByEntrada(int $entradaId): int
    {
        $statement = $this->db->prepare(
            'SELECT COALESCE(SUM(valor), 0) FROM loja_entrada_custos WHERE entrada_id = :entrada_id'
        );
        $statement->execute(['entrada_id' => $entradaId]);

        return (int) round(((float) $statement->fetchColumn()) * 1000);
    }
}
