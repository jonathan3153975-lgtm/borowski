<?php

declare(strict_types=1);

namespace App\Models\Loja;

use App\Core\Model;
use PDO;

class ItemPublicado extends Model
{
    protected string $table = 'loja_itens_publicados';

    /**
     * Canais de publicacao disponiveis (loja, whatsapp, telegram...).
     */
    public function canais(): array
    {
        $statement = $this->db->query(
            'SELECT id, slug, nome FROM loja_canais WHERE ativo = 1 ORDER BY id ASC'
        );

        return $statement === false ? [] : $statement->fetchAll();
    }

    public function canalPorSlug(string $slug): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, slug, nome FROM loja_canais WHERE slug = :slug LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);

        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public function listar(?int $canalId = null, string $busca = ''): array
    {
        $conditions = [];
        $params = [];

        if ($canalId !== null) {
            $conditions[] = 'p.canal_id = :canal_id';
            $params[':canal_id'] = $canalId;
        }

        $busca = trim($busca);

        if ($busca !== '') {
            $conditions[] = '(i.nome LIKE :busca OR i.codigo LIKE :busca OR e.descricao_origem LIKE :busca OR c.nome LIKE :busca)';
            $params[':busca'] = '%' . $busca . '%';
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $statement = $this->db->prepare(
            "SELECT p.*,
                i.nome AS item_nome, i.codigo AS item_codigo, i.descricao AS item_descricao,
                i.custo_unitario_real, i.quantidade AS item_quantidade, i.status AS item_status,
                e.id AS entrada_id, e.descricao_origem AS entrada_descricao, e.modo AS entrada_modo,
                c.nome AS canal_nome, c.slug AS canal_slug,
                cat.nome AS categoria_nome, mar.nome AS marca_nome, esc.nome AS escala_nome,
                (SELECT li.id FROM loja_item_imagens li
                    WHERE li.item_id = i.id
                    ORDER BY li.imagem_principal DESC, li.ordem_exibicao ASC, li.id ASC
                    LIMIT 1) AS imagem_id,
                (SELECT COUNT(*) FROM loja_item_imagens li
                    WHERE li.item_id = i.id) AS imagens_count,
                (SELECT COUNT(*) FROM loja_reservas r
                    WHERE r.publicacao_id = p.id
                      AND r.status = 'reservada'
                      AND r.expira_em > NOW()) AS reservas_abertas,
                (SELECT COALESCE(SUM(r.quantidade), 0) FROM loja_reservas r
                    WHERE r.publicacao_id = p.id
                      AND r.status = 'confirmada') AS vendidos,
                (SELECT COUNT(*) FROM loja_item_visitas v
                    WHERE v.item_id = i.id) AS visitas
             FROM loja_itens_publicados p
             INNER JOIN loja_itens i ON i.id = p.item_id
             LEFT JOIN loja_entradas e ON e.id = i.entrada_id
             INNER JOIN loja_canais c ON c.id = p.canal_id
             LEFT JOIN categorias cat ON cat.id = i.categoria_id
             LEFT JOIN marcas mar ON mar.id = i.marca_id
             LEFT JOIN escalas esc ON esc.id = i.escala_id
             {$where}
             ORDER BY p.publicado_em DESC, p.id DESC"
        );

        $statement->execute($params);

        return $statement->fetchAll();
    }

    /**
     * Vitrine publica: publicacoes ativas com unidades disponiveis.
     */
    public function listarVitrine(string $busca = ''): array
    {
        $conditions = ["p.status = 'publicado'", "i.status IN ('disponivel', 'pre_venda')"];
        $params = [];

        $busca = trim($busca);

        if ($busca !== '') {
            $conditions[] = '(i.nome LIKE :busca_nome OR i.codigo LIKE :busca_codigo OR p.titulo LIKE :busca_titulo)';
            $termo = '%' . $busca . '%';
            $params[':busca_nome'] = $termo;
            $params[':busca_codigo'] = $termo;
            $params[':busca_titulo'] = $termo;
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $statement = $this->db->prepare(
            "SELECT vitrine.*, GREATEST(0, LEAST(
                vitrine.quantidade - vitrine.reservado - vitrine.vendidos,
                vitrine.item_quantidade - vitrine.item_reservado - vitrine.item_vendido
            )) AS disponivel_calc
             FROM (
                SELECT p.*,
                    i.nome AS item_nome, i.codigo AS item_codigo, i.descricao AS item_descricao,
                    i.quantidade AS item_quantidade, i.status AS item_status,
                    c.slug AS canal_slug, c.nome AS canal_nome,
                    (SELECT li.id FROM loja_item_imagens li
                        WHERE li.item_id = i.id
                        ORDER BY li.imagem_principal DESC, li.ordem_exibicao ASC, li.id ASC
                        LIMIT 1) AS imagem_id,
                    (SELECT COALESCE(SUM(r.quantidade), 0) FROM loja_reservas r
                        WHERE r.publicacao_id = p.id
                          AND r.status = 'reservada'
                          AND r.expira_em > NOW()) AS reservado,
                    (SELECT COALESCE(SUM(r.quantidade), 0) FROM loja_reservas r
                        WHERE r.publicacao_id = p.id
                          AND r.status = 'confirmada') AS vendidos,
                    (SELECT COALESCE(SUM(r.quantidade), 0) FROM loja_reservas r
                        WHERE r.item_id = i.id
                          AND r.status = 'reservada'
                          AND r.expira_em > NOW()) AS item_reservado,
                    (SELECT COALESCE(SUM(r.quantidade), 0) FROM loja_reservas r
                        WHERE r.item_id = i.id
                          AND r.status = 'confirmada') AS item_vendido
                 FROM loja_itens_publicados p
                 INNER JOIN loja_itens i ON i.id = p.item_id
                 INNER JOIN loja_canais c ON c.id = p.canal_id
                 {$where}
            ) AS vitrine
            WHERE LEAST(
                vitrine.quantidade - vitrine.reservado - vitrine.vendidos,
                vitrine.item_quantidade - vitrine.item_reservado - vitrine.item_vendido
            ) > 0
            ORDER BY vitrine.publicado_em DESC, vitrine.id DESC"
        );

        $statement->execute($params);
        $rows = $statement->fetchAll();

        $porItem = [];

        foreach ($rows as $row) {
            $itemId = (int) $row['item_id'];

            if (!isset($porItem[$itemId])) {
                $porItem[$itemId] = $row;
                continue;
            }

            if ((string) $row['canal_slug'] === 'loja' && (string) $porItem[$itemId]['canal_slug'] !== 'loja') {
                $porItem[$itemId] = $row;
            }
        }

        return array_values($porItem);
    }

    /**
     * Total ja publicado por item (soma das quantidades em publicacoes ativas).
     */
    public function quantidadePublicadaPorItem(): array
    {
        $rows = $this->db->query(
            "SELECT item_id, COALESCE(SUM(quantidade), 0) AS total
             FROM loja_itens_publicados
             WHERE status <> 'encerrado'
             GROUP BY item_id"
        )->fetchAll();

        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row['item_id']] = (int) $row['total'];
        }

        return $map;
    }

    public function idsPublicados(int $canalId): array
    {
        $statement = $this->db->prepare(
            "SELECT item_id FROM loja_itens_publicados
             WHERE canal_id = :canal_id AND status <> 'encerrado'"
        );
        $statement->execute(['canal_id' => $canalId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function exists(int $itemId, int $canalId): bool
    {
        $statement = $this->db->prepare(
            "SELECT 1 FROM loja_itens_publicados
             WHERE item_id = :item_id AND canal_id = :canal_id AND status <> 'encerrado'
             LIMIT 1"
        );
        $statement->execute(['item_id' => $itemId, 'canal_id' => $canalId]);

        return $statement->fetchColumn() !== false;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO loja_itens_publicados
                (item_id, canal_id, preco_venda, preco_reserva, quantidade, status, titulo, descricao, criado_por)
             VALUES
                (:item_id, :canal_id, :preco_venda, :preco_reserva, :quantidade, :status, :titulo, :descricao, :criado_por)'
        );

        $statement->execute([
            'item_id' => $data['item_id'],
            'canal_id' => $data['canal_id'],
            'preco_venda' => $data['preco_venda'],
            'preco_reserva' => $data['preco_reserva'] ?? null,
            'quantidade' => $data['quantidade'] ?? 1,
            'status' => $data['status'] ?? 'publicado',
            'titulo' => $data['titulo'] ?? null,
            'descricao' => $data['descricao'] ?? null,
            'criado_por' => $data['criado_por'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Mapa item_id => lista de publicacoes ativas (canal, preco, quantidade).
     */
    public function mapaPorItem(): array
    {
        $rows = $this->db->query(
            "SELECT p.item_id, p.preco_venda, p.quantidade, c.slug, c.nome
             FROM loja_itens_publicados p
             INNER JOIN loja_canais c ON c.id = p.canal_id
             WHERE p.status <> 'encerrado'"
        )->fetchAll();

        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row['item_id']][] = [
                'slug' => (string) $row['slug'],
                'nome' => (string) $row['nome'],
                'preco_venda' => $row['preco_venda'],
                'quantidade' => (int) $row['quantidade'],
            ];
        }

        return $map;
    }

    public function findPublicacao(int $id): ?array
    {
        $statement = $this->db->prepare(
            "SELECT p.*,
                i.nome AS item_nome, i.codigo AS item_codigo, i.descricao AS item_descricao,
                i.custo_unitario_real, i.quantidade AS item_quantidade, i.status AS item_status,
                c.slug AS canal_slug, c.nome AS canal_nome,
                (SELECT li.id FROM loja_item_imagens li
                    WHERE li.item_id = i.id
                    ORDER BY li.imagem_principal DESC, li.ordem_exibicao ASC, li.id ASC
                    LIMIT 1) AS imagem_id,
                (SELECT li.caminho_imagem FROM loja_item_imagens li
                    WHERE li.item_id = i.id
                    ORDER BY li.imagem_principal DESC, li.ordem_exibicao ASC, li.id ASC
                    LIMIT 1) AS imagem_caminho
             FROM loja_itens_publicados p
             INNER JOIN loja_itens i ON i.id = p.item_id
             INNER JOIN loja_canais c ON c.id = p.canal_id
             WHERE p.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $id]);

        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public function delete(int $id): bool
    {
        $statement = $this->db->prepare('DELETE FROM loja_itens_publicados WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }
}
