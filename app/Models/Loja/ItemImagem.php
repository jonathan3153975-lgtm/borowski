<?php

declare(strict_types=1);

namespace App\Models\Loja;

use App\Core\Model;

class ItemImagem extends Model
{
    protected string $table = 'loja_item_imagens';

    public function listByItem(int $itemId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM loja_item_imagens
             WHERE item_id = :item_id
             ORDER BY imagem_principal DESC, ordem_exibicao ASC, id ASC'
        );
        $statement->execute(['item_id' => $itemId]);

        return $statement->fetchAll();
    }

    /**
     * Imagens de varios itens de uma vez, agrupadas por item_id.
     */
    public function listByItems(array $itemIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $itemIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($ids === []) {
            return [];
        }

        $placeholders = [];
        $params = [];

        foreach ($ids as $index => $id) {
            $key = ':item_' . $index;
            $placeholders[] = $key;
            $params[$key] = $id;
        }

        $statement = $this->db->prepare(
            'SELECT * FROM loja_item_imagens
             WHERE item_id IN (' . implode(', ', $placeholders) . ')
             ORDER BY imagem_principal DESC, ordem_exibicao ASC, id ASC'
        );
        $statement->execute($params);

        $map = [];

        foreach ($statement->fetchAll() as $row) {
            $map[(int) $row['item_id']][] = $row;
        }

        return $map;
    }

    public function listByEntrada(int $entradaId): array
    {
        $statement = $this->db->prepare(
            'SELECT li.* FROM loja_item_imagens li
             INNER JOIN loja_itens i ON i.id = li.item_id
             WHERE i.entrada_id = :entrada_id'
        );
        $statement->execute(['entrada_id' => $entradaId]);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        return parent::find($id);
    }

    public function create(
        int $itemId,
        string $caminho,
        string $nomeOriginal,
        int $ordem,
        bool $principal
    ): int {
        $statement = $this->db->prepare(
            'INSERT INTO loja_item_imagens
                (item_id, caminho_imagem, nome_original, imagem_principal, ordem_exibicao)
             VALUES
                (:item_id, :caminho, :nome_original, :principal, :ordem)'
        );

        $statement->execute([
            'item_id' => $itemId,
            'caminho' => $caminho,
            'nome_original' => $nomeOriginal,
            'principal' => $principal ? 1 : 0,
            'ordem' => $ordem,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function countByItem(int $itemId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM loja_item_imagens WHERE item_id = :item_id'
        );
        $statement->execute(['item_id' => $itemId]);

        return (int) $statement->fetchColumn();
    }

    public function proximaOrdem(int $itemId): int
    {
        $statement = $this->db->prepare(
            'SELECT COALESCE(MAX(ordem_exibicao), 0) + 1 FROM loja_item_imagens WHERE item_id = :item_id'
        );
        $statement->execute(['item_id' => $itemId]);

        return (int) $statement->fetchColumn();
    }
}
