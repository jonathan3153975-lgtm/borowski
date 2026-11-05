<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Core\Database;
use App\Models\Loja\Entrada;
use App\Models\Loja\EntradaCusto;
use App\Models\Loja\ItemEstoque;
use App\Models\Loja\ItemFornecedor;
use App\Services\AuditService;
use InvalidArgumentException;
use Throwable;

class EntradaService
{
    public const PER_PAGE = 20;
    public const MAX_QUANTITY = 999999;

    private Entrada $entradas;
    private EntradaCusto $custos;
    private ItemEstoque $itens;
    private ItemFornecedor $ofertas;
    private CalculoService $calculo;
    private AuditService $audit;

    public function __construct()
    {
        $this->entradas = new Entrada();
        $this->custos = new EntradaCusto();
        $this->itens = new ItemEstoque();
        $this->ofertas = new ItemFornecedor();
        $this->calculo = new CalculoService();
        $this->audit = new AuditService();
    }

    public function listar(array $filters, int $page, int $perPage = self::PER_PAGE): array
    {
        $page = max(1, $page);
        $total = $this->entradas->contar($filters);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        return [
            'items' => $this->entradas->listar($filters, $perPage, $offset),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    public function find(int $id): array
    {
        $entrada = $this->entradas->findDetalhada($id);

        if ($entrada === null) {
            throw new InvalidArgumentException('Entrada não encontrada.');
        }

        return $entrada;
    }

    public function obterDetalhe(int $id): array
    {
        $entrada = $this->find($id);
        $custos = $entrada['modo'] === 'direta' ? $this->custos->listByEntrada($id) : [];
        $itens = $this->itens->listByEntrada($id);

        foreach ($itens as &$item) {
            $item['ofertas'] = $entrada['modo'] === 'previsao'
                ? $this->ofertas->listByItem((int) $item['id'])
                : [];
        }
        unset($item);

        return [
            'entrada' => $entrada,
            'custos' => $custos,
            'itens' => $itens,
            'totais' => $this->calcularTotais($itens, $custos),
        ];
    }

    public function criar(array $data, array $actor): int
    {
        $modo = $this->validarModo($data['modo'] ?? '');
        $descricao = $this->validarTexto($data['descricao_origem'] ?? '', 3, 180, 'Descrição de origem');
        $observacoes = $this->validarTextoOpcional($data['observacoes'] ?? '', 2000, 'Observações');

        $link = null;
        $recebido = 0;
        $rastreio = null;

        if ($modo === 'direta') {
            $link = $this->validarUrl($data['link_compra'] ?? '', true);
            $recebido = !empty($data['recebido']) ? 1 : 0;
            $rastreio = $this->validarTextoOpcional($data['codigo_rastreio'] ?? '', 120, 'Código de rastreio');
        }

        $id = $this->entradas->create([
            'modo' => $modo,
            'descricao_origem' => $descricao,
            'link_compra' => $link,
            'recebido' => $recebido,
            'codigo_rastreio' => $rastreio,
            'observacoes' => $observacoes,
            'criado_por' => (int) ($actor['id'] ?? 0) ?: null,
        ]);

        $this->audit->log(
            'loja_entrada_criada',
            "Entrada criada: {$descricao} (ID {$id}, modo {$modo}).",
            (int) ($actor['id'] ?? 0) ?: null
        );

        return $id;
    }

    public function atualizar(int $id, array $data, array $actor): void
    {
        $entrada = $this->find($id);
        $descricao = $this->validarTexto($data['descricao_origem'] ?? '', 3, 180, 'Descrição de origem');
        $observacoes = $this->validarTextoOpcional($data['observacoes'] ?? '', 2000, 'Observações');

        $fields = [
            'descricao_origem' => $descricao,
            'observacoes' => $observacoes,
        ];

        if ($entrada['modo'] === 'direta') {
            $fields['link_compra'] = $this->validarUrl($data['link_compra'] ?? '', true);
            $fields['codigo_rastreio'] = $this->validarTextoOpcional(
                $data['codigo_rastreio'] ?? '',
                120,
                'Código de rastreio'
            );
        }

        $this->entradas->update($id, $fields);

        $this->audit->log(
            'loja_entrada_atualizada',
            "Entrada atualizada: {$descricao} (ID {$id}).",
            (int) ($actor['id'] ?? 0) ?: null
        );
    }

    public function alternarRecebido(int $id, array $actor): bool
    {
        $entrada = $this->find($id);

        if ($entrada['modo'] !== 'direta') {
            throw new InvalidArgumentException('O recebimento só se aplica a compras diretas.');
        }

        $novoRecebido = (int) $entrada['recebido'] !== 1;

        $this->transaction(function () use ($id, $novoRecebido): void {
            $this->entradas->updateRecebido($id, $novoRecebido);
            $this->itens->updateStatusByEntrada(
                $id,
                $novoRecebido ? 'disponivel' : 'em_transporte'
            );
        });

        $this->audit->log(
            $novoRecebido ? 'loja_entrada_recebida' : 'loja_entrada_reaberta',
            "Entrada {$entrada['descricao_origem']} (ID {$id}) marcada como "
                . ($novoRecebido ? 'recebida.' : 'não recebida.'),
            (int) ($actor['id'] ?? 0) ?: null
        );

        return $novoRecebido;
    }

    public function excluir(int $id, array $actor): void
    {
        $entrada = $this->find($id);
        $this->entradas->delete($id);

        $this->audit->log(
            'loja_entrada_excluida',
            "Entrada excluída: {$entrada['descricao_origem']} (ID {$id}).",
            (int) ($actor['id'] ?? 0) ?: null
        );
    }

    public function adicionarCusto(int $entradaId, array $data, array $actor): int
    {
        $entrada = $this->find($entradaId);

        if ($entrada['modo'] !== 'direta') {
            throw new InvalidArgumentException('Custos adicionais são permitidos apenas em compras diretas.');
        }

        $descricao = $this->validarTexto($data['custo_descricao'] ?? '', 2, 150, 'Descrição do custo');
        $valorCentavos = $this->validarMoeda($data['custo_valor'] ?? '', 'Valor do custo');

        if ($valorCentavos <= 0) {
            throw new InvalidArgumentException('O valor do custo deve ser maior que zero.');
        }

        $custoId = $this->transaction(function () use ($entradaId, $descricao, $valorCentavos): int {
            $id = $this->custos->create(
                $entradaId,
                $descricao,
                $this->calculo->centavosParaDecimal($valorCentavos)
            );
            $this->recalcularCustos($entradaId);

            return $id;
        });

        $this->audit->log(
            'loja_custo_adicionado',
            "Custo adicional incluído na entrada {$entrada['descricao_origem']} (ID {$entradaId}): {$descricao}.",
            (int) ($actor['id'] ?? 0) ?: null
        );

        return $custoId;
    }

    public function excluirCusto(int $entradaId, int $custoId, array $actor): void
    {
        $entrada = $this->find($entradaId);

        if ($entrada['modo'] !== 'direta') {
            throw new InvalidArgumentException('Custos adicionais são permitidos apenas em compras diretas.');
        }

        $custo = $this->custos->find($custoId);

        if ($custo === null || (int) $custo['entrada_id'] !== $entradaId) {
            throw new InvalidArgumentException('Custo adicional não encontrado para esta entrada.');
        }

        $this->transaction(function () use ($entradaId, $custoId): void {
            $this->custos->delete($custoId);
            $this->recalcularCustos($entradaId);
        });

        $this->audit->log(
            'loja_custo_excluido',
            "Custo adicional excluído da entrada {$entrada['descricao_origem']} (ID {$entradaId}): {$custo['descricao']}.",
            (int) ($actor['id'] ?? 0) ?: null
        );
    }

    public function adicionarItem(int $entradaId, array $data, array $actor): int
    {
        $entrada = $this->find($entradaId);

        $nome = $this->validarTexto($data['nome'] ?? '', 2, 180, 'Nome do item');
        $descricao = $this->validarTextoOpcional($data['descricao'] ?? '', 5000, 'Descrição do item');
        $codigo = $this->validarTextoOpcional($data['codigo'] ?? '', 80, 'Código do item');
        $quantidade = $this->validarQuantidade($data['quantidade'] ?? '');
        $observacoes = $this->validarTextoOpcional($data['item_observacoes'] ?? '', 2000, 'Observações do item');

        $itemId = $this->transaction(function () use (
            $entrada,
            $entradaId,
            $data,
            $nome,
            $descricao,
            $codigo,
            $quantidade,
            $observacoes
        ): int {
            if ($entrada['modo'] === 'direta') {
                $baseCentavos = $this->validarMoeda($data['custo_unitario_base'] ?? '', 'Custo unitário base');

                $id = $this->itens->create([
                    'entrada_id' => $entradaId,
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'codigo' => $codigo,
                    'quantidade' => $quantidade,
                    'custo_unitario_base' => $this->calculo->centavosParaDecimal($baseCentavos),
                    'status' => (int) $entrada['recebido'] === 1 ? 'disponivel' : 'em_transporte',
                    'observacoes' => $observacoes,
                ]);

                $this->recalcularCustos($entradaId);

                return $id;
            }

            $oferta = $this->validarOferta($data, false);

            $id = $this->itens->create([
                'entrada_id' => $entradaId,
                'nome' => $nome,
                'descricao' => $descricao,
                'codigo' => $codigo,
                'quantidade' => $quantidade,
                'custo_unitario_base' => '0.00',
                'status' => 'pre_venda',
                'observacoes' => $observacoes,
            ]);

            $this->ofertas->create([
                'item_id' => $id,
                'fornecedor' => $oferta['fornecedor'],
                'url' => $oferta['url'],
                'preco_custo' => $oferta['preco_custo'],
                'selecionado' => 0,
                'observacoes' => $oferta['observacoes'],
            ]);

            return $id;
        });

        $this->audit->log(
            'loja_item_adicionado',
            "Item incluído na entrada {$entrada['descricao_origem']} (ID {$entradaId}): {$nome} (ID {$itemId}).",
            (int) ($actor['id'] ?? 0) ?: null
        );

        if ($entrada['modo'] === 'previsao') {
            $this->audit->log(
                'loja_fornecedor_adicionado',
                "Oferta de fornecedor incluída no item {$nome} (ID {$itemId}).",
                (int) ($actor['id'] ?? 0) ?: null
            );
        }

        return $itemId;
    }

    public function atualizarItem(int $itemId, array $data, array $actor): int
    {
        $item = $this->itens->find($itemId);

        if ($item === null) {
            throw new InvalidArgumentException('Item não encontrado.');
        }

        $entradaId = (int) $item['entrada_id'];
        $entrada = $this->find($entradaId);

        $nome = $this->validarTexto($data['nome'] ?? '', 2, 180, 'Nome do item');
        $descricao = $this->validarTextoOpcional($data['descricao'] ?? '', 5000, 'Descrição do item');
        $codigo = $this->validarTextoOpcional($data['codigo'] ?? '', 80, 'Código do item');
        $quantidade = $this->validarQuantidade($data['quantidade'] ?? '');
        $observacoes = $this->validarTextoOpcional($data['item_observacoes'] ?? '', 2000, 'Observações do item');

        $this->transaction(function () use (
            $entrada,
            $entradaId,
            $itemId,
            $data,
            $nome,
            $descricao,
            $codigo,
            $quantidade,
            $observacoes
        ): void {
            $fields = [
                'nome' => $nome,
                'descricao' => $descricao,
                'codigo' => $codigo,
                'quantidade' => $quantidade,
                'observacoes' => $observacoes,
            ];

            if ($entrada['modo'] === 'direta') {
                $baseCentavos = $this->validarMoeda($data['custo_unitario_base'] ?? '', 'Custo unitário base');
                $fields['custo_unitario_base'] = $this->calculo->centavosParaDecimal($baseCentavos);
                $this->itens->update($itemId, $fields);
                $this->recalcularCustos($entradaId);

                return;
            }

            $ofertaSelecionada = $this->ofertas->selectedByItem($itemId);
            $baseCentavos = $ofertaSelecionada !== null
                ? $this->calculo->paraCentavos($ofertaSelecionada['preco_custo'])
                : 0;
            $custos = $this->calculo->calcularItemPrevisao($quantidade, $baseCentavos);

            $this->itens->update($itemId, array_merge($fields, $custos));
        });

        $this->audit->log(
            'loja_item_atualizado',
            "Item atualizado na entrada {$entrada['descricao_origem']} (ID {$entradaId}): {$nome} (ID {$itemId}).",
            (int) ($actor['id'] ?? 0) ?: null
        );

        return $entradaId;
    }

    public function excluirItem(int $itemId, array $actor): int
    {
        $item = $this->itens->find($itemId);

        if ($item === null) {
            throw new InvalidArgumentException('Item não encontrado.');
        }

        $entradaId = (int) $item['entrada_id'];
        $entrada = $this->find($entradaId);

        $this->transaction(function () use ($entrada, $entradaId, $itemId): void {
            $this->itens->delete($itemId);

            if ($entrada['modo'] === 'direta') {
                $this->recalcularCustos($entradaId);
            }
        });

        $this->audit->log(
            'loja_item_excluido',
            "Item excluído da entrada {$entrada['descricao_origem']} (ID {$entradaId}): {$item['nome']} (ID {$itemId}).",
            (int) ($actor['id'] ?? 0) ?: null
        );

        return $entradaId;
    }

    public function recalcularCustos(int $entradaId): void
    {
        $entrada = $this->find($entradaId);

        if ($entrada['modo'] !== 'direta') {
            return;
        }

        $itens = $this->itens->listByEntrada($entradaId);

        if ($itens === []) {
            return;
        }

        $totalAdicionais = $this->custos->totalByEntrada($entradaId);
        $calculado = $this->calculo->calcularRateio($totalAdicionais, $itens);

        foreach ($calculado as $itemId => $custos) {
            $this->itens->updateCosts((int) $itemId, $custos);
        }
    }

    private function calcularTotais(array $itens, array $custos): array
    {
        $base = 0;
        $real = 0;

        foreach ($itens as $item) {
            $quantidade = max(1, (int) $item['quantidade']);
            $base += $this->calculo->paraCentavos($item['custo_unitario_base']) * $quantidade;
            $real += $this->calculo->paraCentavos($item['custo_total_real']);
        }

        $adicionais = 0;

        foreach ($custos as $custo) {
            $adicionais += $this->calculo->paraCentavos($custo['valor']);
        }

        return [
            'base' => $this->calculo->centavosParaDecimal($base),
            'adicionais' => $this->calculo->centavosParaDecimal($adicionais),
            'real' => $this->calculo->centavosParaDecimal($real),
            'itens' => count($itens),
        ];
    }

    private function validarOferta(array $data, bool $ignorarFornecedor = false): array
    {
        $fornecedor = $ignorarFornecedor
            ? null
            : $this->validarTexto($data['fornecedor'] ?? '', 2, 150, 'Fornecedor');
        $url = $this->validarUrl($data['url'] ?? '', true);
        $precoCentavos = $this->validarMoeda($data['preco_custo'] ?? '', 'Preço de custo');
        $observacoes = $this->validarTextoOpcional($data['oferta_observacoes'] ?? '', 255, 'Observações da oferta');

        return [
            'fornecedor' => $fornecedor,
            'url' => $url,
            'preco_custo' => $this->calculo->centavosParaDecimal($precoCentavos),
            'observacoes' => $observacoes,
        ];
    }

    private function validarModo(mixed $valor): string
    {
        $modo = (string) $valor;

        if (!in_array($modo, ['direta', 'previsao'], true)) {
            throw new InvalidArgumentException('Modo de entrada inválido.');
        }

        return $modo;
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

    private function validarUrl(mixed $valor, bool $obrigatorio): ?string
    {
        $url = trim((string) $valor);

        if ($url === '') {
            if ($obrigatorio) {
                throw new InvalidArgumentException('Informe uma URL válida.');
            }

            return null;
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

    private function validarQuantidade(mixed $valor): int
    {
        $quantidade = filter_var($valor, FILTER_VALIDATE_INT);

        if ($quantidade === false || $quantidade < 1 || $quantidade > self::MAX_QUANTITY) {
            throw new InvalidArgumentException(
                'A quantidade deve ser um número inteiro entre 1 e ' . self::MAX_QUANTITY . '.'
            );
        }

        return $quantidade;
    }

    private function validarMoeda(mixed $valor, string $campo): int
    {
        try {
            $centavos = $this->calculo->paraCentavos($valor);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException("{$campo} inválido.");
        }

        if ($centavos < 0 || $centavos > CalculoService::MAX_MONEY_CENTS) {
            throw new InvalidArgumentException("{$campo} deve estar entre 0,00 e 9.999.999,99.");
        }

        return $centavos;
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
