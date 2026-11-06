<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Core\Database;
use App\Models\Loja\Entrada;
use App\Models\Loja\ItemEstoque;
use App\Models\Loja\ItemFornecedor;
use App\Services\AuditService;
use InvalidArgumentException;
use Throwable;

class PrevisaoService
{
    private Entrada $entradas;
    private ItemEstoque $itens;
    private ItemFornecedor $ofertas;
    private CalculoService $calculo;
    private AuditService $audit;

    public function __construct()
    {
        $this->entradas = new Entrada();
        $this->itens = new ItemEstoque();
        $this->ofertas = new ItemFornecedor();
        $this->calculo = new CalculoService();
        $this->audit = new AuditService();
    }

    public function adicionarOferta(int $itemId, array $data, array $actor): int
    {
        $item = $this->obterItemPrevisao($itemId);
        $entradaId = (int) $item['entrada_id'];

        $fornecedor = $this->validarTexto($data['fornecedor'] ?? '', 2, 150, 'Fornecedor');
        $url = $this->validarUrl($data['url'] ?? '');
        $precoMilesimos = $this->validarMoeda($data['preco_custo'] ?? '', 'Preço de custo');
        $observacoes = $this->validarTextoOpcional($data['oferta_observacoes'] ?? '', 255, 'Observações da oferta');

        if ($this->ofertas->existsByUrl($itemId, $url)) {
            throw new InvalidArgumentException('Este link já está cadastrado para o item.');
        }

        $ofertaId = $this->ofertas->create([
            'item_id' => $itemId,
            'fornecedor' => $fornecedor,
            'url' => $url,
            'preco_custo' => $this->calculo->milesimosParaDecimal($precoMilesimos),
            'selecionado' => 0,
            'observacoes' => $observacoes,
        ]);

        $this->audit->log(
            'loja_fornecedor_adicionado',
            "Oferta de fornecedor incluída no item {$item['nome']} (ID {$itemId}): {$fornecedor} (ID {$ofertaId}).",
            (int) ($actor['id'] ?? 0) ?: null
        );

        return $entradaId;
    }

    public function selecionarOferta(int $itemId, int $ofertaId, array $actor): int
    {
        $item = $this->obterItemPrevisao($itemId);
        $entradaId = (int) $item['entrada_id'];
        $oferta = $this->obterOfertaDoItem($itemId, $ofertaId);

        $quantidade = max(1, (int) $item['quantidade']);
        $baseMilesimos = $this->calculo->paraMilesimos($oferta['preco_custo']);
        $custos = $this->calculo->calcularItemPrevisao($quantidade, $baseMilesimos);

        $this->transaction(function () use ($itemId, $ofertaId, $custos): void {
            $this->ofertas->deselectAllForItem($itemId);
            $this->ofertas->select($ofertaId);
            $this->itens->update($itemId, $custos);
        });

        $this->audit->log(
            'loja_fornecedor_selecionado',
            "Oferta selecionada para o item {$item['nome']} (ID {$itemId}): {$oferta['fornecedor']} (ID {$ofertaId}).",
            (int) ($actor['id'] ?? 0) ?: null
        );

        return $entradaId;
    }

    public function excluirOferta(int $itemId, int $ofertaId, array $actor): int
    {
        $item = $this->obterItemPrevisao($itemId);
        $entradaId = (int) $item['entrada_id'];
        $oferta = $this->obterOfertaDoItem($itemId, $ofertaId);

        $this->transaction(function () use ($itemId, $ofertaId, $oferta): void {
            $this->ofertas->delete($ofertaId);

            if ((int) $oferta['selecionado'] === 1) {
                $this->itens->update($itemId, [
                    'custo_unitario_base' => '0.000',
                    'custo_adicional_rateado' => '0.000',
                    'custo_total_real' => '0.000',
                    'custo_unitario_real' => '0.000',
                ]);
            }
        });

        $this->audit->log(
            'loja_fornecedor_excluido',
            "Oferta excluída do item {$item['nome']} (ID {$itemId}): {$oferta['fornecedor']} (ID {$ofertaId}).",
            (int) ($actor['id'] ?? 0) ?: null
        );

        return $entradaId;
    }

    private function obterItemPrevisao(int $itemId): array
    {
        $item = $this->itens->find($itemId);

        if ($item === null) {
            throw new InvalidArgumentException('Item não encontrado.');
        }

        if ($item['entrada_modo'] !== 'previsao') {
            throw new InvalidArgumentException('Ofertas de fornecedor são permitidas apenas em previsões de compra.');
        }

        return $item;
    }

    private function obterOfertaDoItem(int $itemId, int $ofertaId): array
    {
        $oferta = $this->ofertas->find($ofertaId);

        if ($oferta === null || (int) $oferta['item_id'] !== $itemId) {
            throw new InvalidArgumentException('Oferta de fornecedor não encontrada para este item.');
        }

        return $oferta;
    }

    private function validarTexto(mixed $valor, int $min, int $max, string $campo): string
    {
        $texto = trim((string) $valor);
        $length = mb_strlen($texto);

        if ($length < $min || $length > $max) {
            throw new InvalidArgumentException(
                "{$campo} deve ter entre {$min} e {$max} caracteres."
            );
        }

        return $texto;
    }

    private function validarTextoOpcional(mixed $valor, int $max, string $campo): ?string
    {
        $texto = trim((string) $valor);

        if ($texto === '') {
            return null;
        }

        if (mb_strlen($texto) > $max) {
            throw new InvalidArgumentException("{$campo} deve ter no máximo {$max} caracteres.");
        }

        return $texto;
    }

    private function validarUrl(mixed $valor): string
    {
        $url = trim((string) $valor);

        if ($url === '') {
            throw new InvalidArgumentException('Informe a URL do fornecedor.');
        }

        if (mb_strlen($url) > 500 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Informe uma URL válida.');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('A URL deve usar http ou https.');
        }

        return $url;
    }

    private function validarMoeda(mixed $valor, string $campo): int
    {
        try {
            $milesimos = $this->calculo->paraMilesimos($valor);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException("{$campo} inválido.");
        }

        if ($milesimos < 0 || $milesimos > CalculoService::MAX_MONEY_MILESIMOS) {
            throw new InvalidArgumentException("{$campo} deve estar entre 0,000 e 999.999.999,999.");
        }

        return $milesimos;
    }

    private function transaction(callable $callback): mixed
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $result = $callback();
            $db->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }
}
