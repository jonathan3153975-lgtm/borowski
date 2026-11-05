<?php

declare(strict_types=1);

namespace App\Models\Loja;

use App\Core\Model;

class ItemFornecedor extends Model
{
    protected string $table = 'loja_item_fornecedores';

    public function listByItem(int $itemId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM loja_item_fornecedores
             WHERE item_id = :item_id
             ORDER BY selecionado DESC, preco_custo ASC, id ASC'
        );
        $statement->execute(['item_id' => $itemId]);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM loja_item_fornecedores WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO loja_item_fornecedores
                (item_id, fornecedor, url, preco_custo, selecionado, observacoes)
             VALUES
                (:item_id, :fornecedor, :url, :preco_custo, :selecionado, :observacoes)'
        );

        $statement->execute([
            'item_id' => $data['item_id'],
            'fornecedor' => $data['fornecedor'],
            'url' => $data['url'],
            'preco_custo' => $data['preco_custo'],
            'selecionado' => $data['selecionado'] ?? 0,
            'observacoes' => $data['observacoes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $statement = $this->db->prepare('DELETE FROM loja_item_fornecedores WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }

    public function deselectAllForItem(int $itemId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_item_fornecedores SET selecionado = 0 WHERE item_id = :item_id'
        );

        return $statement->execute(['item_id' => $itemId]);
    }

    public function select(int $id): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loja_item_fornecedores SET selecionado = 1 WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    public function selectedByItem(int $itemId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM loja_item_fornecedores
             WHERE item_id = :item_id AND selecionado = 1
             LIMIT 1'
        );
        $statement->execute(['item_id' => $itemId]);

        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public function lowestByItem(int $itemId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM loja_item_fornecedores
             WHERE item_id = :item_id
             ORDER BY preco_custo ASC, id ASC
             LIMIT 1'
        );
        $statement->execute(['item_id' => $itemId]);

        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public function existsByUrl(int $itemId, string $url, ?int $ignoreId = null): bool
    {
        if ($ignoreId === null) {
            $statement = $this->db->prepare(
                'SELECT 1 FROM loja_item_fornecedores WHERE item_id = :item_id AND url = :url LIMIT 1'
            );
            $statement->execute(['item_id' => $itemId, 'url' => $url]);
        } else {
            $statement = $this->db->prepare(
                'SELECT 1 FROM loja_item_fornecedores
                 WHERE item_id = :item_id AND url = :url AND id <> :id LIMIT 1'
            );
            $statement->execute(['item_id' => $itemId, 'url' => $url, 'id' => $ignoreId]);
        }

        return $statement->fetchColumn() !== false;
    }

    public function countByItem(int $itemId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM loja_item_fornecedores WHERE item_id = :item_id'
        );
        $statement->execute(['item_id' => $itemId]);

        return (int) $statement->fetchColumn();
    }
}
