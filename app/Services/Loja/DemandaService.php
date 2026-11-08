<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Core\Database;
use App\Models\Loja\ItemEstoque;
use App\Services\AuditService;
use InvalidArgumentException;
use PDO;

class DemandaService
{
    private ItemEstoque $itens;
    private ItemImagemService $imagens;
    private AuditService $audit;
    private PDO $db;

    public function __construct()
    {
        $this->itens = new ItemEstoque();
        $this->imagens = new ItemImagemService();
        $this->audit = new AuditService();
        $this->db = Database::connection();
    }

    /**
     * Cria um item sob demanda (pre-venda) sem entrada, com multiplas origens/custos.
     */
    public function criar(array $data, array $actor, array $files = []): int
    {
        $validatedFiles = $this->imagens->validarUploads($files);
        $nome = trim((string) ($data['nome'] ?? ''));

        if (mb_strlen($nome) < 2 || mb_strlen($nome) > 180) {
            throw new InvalidArgumentException('O nome do item deve ter entre 2 e 180 caracteres.');
        }

        $quantidade = max(1, (int) ($data['quantidade'] ?? 1));

        $ofertas = [];

        foreach ((array) ($data['ofertas'] ?? []) as $oferta) {
            if (!is_array($oferta)) {
                continue;
            }

            $fornecedor = trim((string) ($oferta['fornecedor'] ?? ''));
            $preco = $this->parseMoney($oferta['preco_custo'] ?? null);

            if ($fornecedor === '' || $preco <= 0) {
                continue;
            }

            $ofertas[] = [
                'fornecedor' => mb_substr($fornecedor, 0, 150),
                'url' => trim((string) ($oferta['url'] ?? '')),
                'preco_custo' => $preco,
                'observacoes' => trim((string) ($oferta['observacoes'] ?? '')),
            ];
        }

        if ($ofertas === []) {
            throw new InvalidArgumentException('Informe ao menos um fornecedor com preço de custo maior que zero.');
        }

        $menorCusto = min(array_map(static fn (array $o): float => $o['preco_custo'], $ofertas));

        $this->db->beginTransaction();

        try {
            $itemId = $this->itens->create([
                'entrada_id' => null,
                'categoria_id' => $this->nullableId($data['categoria_id'] ?? null),
                'marca_id' => $this->nullableId($data['marca_id'] ?? null),
                'fabricante_id' => $this->nullableId($data['fabricante_id'] ?? null),
                'colecao_id' => $this->nullableId($data['colecao_id'] ?? null),
                'escala_id' => $this->nullableId($data['escala_id'] ?? null),
                'ano_fabricacao' => $this->nullableId($data['ano_fabricacao'] ?? null),
                'nome' => $nome,
                'descricao' => trim((string) ($data['descricao'] ?? '')) ?: null,
                'codigo' => trim((string) ($data['codigo'] ?? '')) ?: null,
                'quantidade' => $quantidade,
                'custo_unitario_base' => number_format($menorCusto, 3, '.', ''),
                'custo_adicional_rateado' => '0.000',
                'custo_total_real' => number_format($menorCusto * $quantidade, 3, '.', ''),
                'custo_unitario_real' => number_format($menorCusto, 3, '.', ''),
                'status' => 'pre_venda',
                'observacoes' => trim((string) ($data['observacoes'] ?? '')) ?: null,
            ]);

            $statement = $this->db->prepare(
                'INSERT INTO loja_item_fornecedores
                    (item_id, fornecedor, url, preco_custo, selecionado, observacoes)
                 VALUES
                    (:item_id, :fornecedor, :url, :preco_custo, :selecionado, :observacoes)'
            );

            foreach ($ofertas as $oferta) {
                $statement->execute([
                    'item_id' => $itemId,
                    'fornecedor' => $oferta['fornecedor'],
                    'url' => $oferta['url'] !== '' ? $oferta['url'] : '',
                    'preco_custo' => number_format($oferta['preco_custo'], 3, '.', ''),
                    'selecionado' => $oferta['preco_custo'] === $menorCusto ? 1 : 0,
                    'observacoes' => $oferta['observacoes'] !== '' ? mb_substr($oferta['observacoes'], 0, 255) : null,
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }

        if ($validatedFiles !== []) {
            $this->imagens->salvar($itemId, $validatedFiles, $actor);
        }

        $this->audit->log(
            'loja_item_demanda_criado',
            'Item sob demanda (pré-venda) criado: ' . $nome . ' (ID ' . $itemId . ', '
                . count($ofertas) . ' origem(ns)).',
            (int) ($actor['id'] ?? 0) ?: null
        );

        return $itemId;
    }

    private function nullableId(mixed $value): ?int
    {
        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function parseMoney(mixed $value): float
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        if ($digits === '') {
            return 0.0;
        }

        return ((int) $digits) / 1000;
    }
}
