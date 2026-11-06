<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Core\Database;
use App\Models\Loja\Entrada;
use App\Models\Loja\EntradaCusto;
use App\Models\Loja\ItemEstoque;
use App\Models\Loja\ItemFornecedor;
use App\Models\Configuracao;
use App\Models\Loja\ItemImagem;
use App\Services\AuditService;
use InvalidArgumentException;
use Throwable;

class EntradaService
{
    public const PER_PAGE = 20;
    public const MAX_QUANTITY = 999999;

    private const AUX_ITENS = [
        'categoria_id' => ['table' => 'categorias', 'label' => 'Categoria'],
        'marca_id' => ['table' => 'marcas', 'label' => 'Marca'],
        'fabricante_id' => ['table' => 'fabricantes', 'label' => 'Fabricante'],
        'colecao_id' => ['table' => 'colecoes', 'label' => 'Coleção'],
        'escala_id' => ['table' => 'escalas', 'label' => 'Escala'],
    ];

    private Entrada $entradas;
    private EntradaCusto $custos;
    private ItemEstoque $itens;
    private ItemFornecedor $ofertas;
    private ItemImagem $itemImagens;
    private ItemImagemService $itemImagemService;
    private Configuracao $configuracoes;
    private CalculoService $calculo;
    private AuditService $audit;

    public function __construct()
    {
        $this->entradas = new Entrada();
        $this->custos = new EntradaCusto();
        $this->itens = new ItemEstoque();
        $this->ofertas = new ItemFornecedor();
        $this->itemImagens = new ItemImagem();
        $this->itemImagemService = new ItemImagemService();
        $this->configuracoes = new Configuracao();
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
        $imagens = $this->itemImagens->listByEntrada($id);
        $this->entradas->delete($id);
        $this->itemImagemService->deletarArquivos($imagens);

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
        $valorMilesimos = $this->validarMoeda($data['custo_valor'] ?? '', 'Valor do custo');

        if ($valorMilesimos <= 0) {
            throw new InvalidArgumentException('O valor do custo deve ser maior que zero.');
        }

        $custoId = $this->transaction(function () use ($entradaId, $descricao, $valorMilesimos): int {
            $id = $this->custos->create(
                $entradaId,
                $descricao,
                $this->calculo->milesimosParaDecimal($valorMilesimos)
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

    public function adicionarItem(int $entradaId, array $data, array $actor, array $files = []): int
    {
        $entrada = $this->find($entradaId);

        $nome = $this->validarTexto($data['nome'] ?? '', 2, 180, 'Nome do item');
        $descricao = $this->validarTextoOpcional($data['descricao'] ?? '', 5000, 'Descrição do item');
        $codigo = $this->validarTextoOpcional($data['codigo'] ?? '', 80, 'Código do item');
        $quantidade = $this->validarQuantidade($data['quantidade'] ?? '');
        $observacoes = $this->validarTextoOpcional($data['item_observacoes'] ?? '', 2000, 'Observações do item');
        $categoriaId = $this->validarAuxiliarOpcional($data['categoria_id'] ?? '', 'categoria_id');
        $marcaId = $this->validarAuxiliarOpcional($data['marca_id'] ?? '', 'marca_id');
        $fabricanteId = $this->validarAuxiliarOpcional($data['fabricante_id'] ?? '', 'fabricante_id');
        $colecaoId = $this->validarAuxiliarOpcional($data['colecao_id'] ?? '', 'colecao_id');
        $escalaId = $this->validarAuxiliarOpcional($data['escala_id'] ?? '', 'escala_id');
        $anoFabricacao = $this->validarAnoOpcional($data['ano_fabricacao'] ?? '');
        $validatedFiles = $files === [] ? [] : $this->itemImagemService->validarUploads($files);

        $itemId = $this->transaction(function () use (
            $entrada,
            $entradaId,
            $data,
            $nome,
            $descricao,
            $codigo,
            $quantidade,
            $observacoes,
            $categoriaId,
            $marcaId,
            $fabricanteId,
            $colecaoId,
            $escalaId,
            $anoFabricacao
        ): int {
            if ($entrada['modo'] === 'direta') {
                $baseMilesimos = $this->validarMoeda($data['custo_unitario_base'] ?? '', 'Custo unitário base');

                $id = $this->itens->create([
                    'entrada_id' => $entradaId,
                    'categoria_id' => $categoriaId,
                    'marca_id' => $marcaId,
                    'fabricante_id' => $fabricanteId,
                    'colecao_id' => $colecaoId,
                    'escala_id' => $escalaId,
                    'ano_fabricacao' => $anoFabricacao,
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'codigo' => $codigo,
                    'quantidade' => $quantidade,
                    'custo_unitario_base' => $this->calculo->milesimosParaDecimal($baseMilesimos),
                    'status' => (int) $entrada['recebido'] === 1 ? 'disponivel' : 'em_transporte',
                    'observacoes' => $observacoes,
                ]);

                $this->recalcularCustos($entradaId);

                return $id;
            }

            $oferta = $this->validarOferta($data, false);

            $id = $this->itens->create([
                'entrada_id' => $entradaId,
                'categoria_id' => $categoriaId,
                'marca_id' => $marcaId,
                'fabricante_id' => $fabricanteId,
                'colecao_id' => $colecaoId,
                'escala_id' => $escalaId,
                'ano_fabricacao' => $anoFabricacao,
                'nome' => $nome,
                'descricao' => $descricao,
                'codigo' => $codigo,
                'quantidade' => $quantidade,
                'custo_unitario_base' => '0.000',
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

        if ($validatedFiles !== []) {
            try {
                $this->itemImagemService->salvar($itemId, $validatedFiles, $actor);
            } catch (Throwable $exception) {
                $this->removerItemAposFalha($itemId, $entradaId, $entrada['modo']);

                throw $exception;
            }
        }

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

    public function atualizarItem(int $itemId, array $data, array $actor, array $files = []): int
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
        $categoriaId = $this->validarAuxiliarOpcional($data['categoria_id'] ?? '', 'categoria_id');
        $marcaId = $this->validarAuxiliarOpcional($data['marca_id'] ?? '', 'marca_id');
        $fabricanteId = $this->validarAuxiliarOpcional($data['fabricante_id'] ?? '', 'fabricante_id');
        $colecaoId = $this->validarAuxiliarOpcional($data['colecao_id'] ?? '', 'colecao_id');
        $escalaId = $this->validarAuxiliarOpcional($data['escala_id'] ?? '', 'escala_id');
        $anoFabricacao = $this->validarAnoOpcional($data['ano_fabricacao'] ?? '');
        $validatedFiles = $files === [] ? [] : $this->itemImagemService->validarUploads($files);

        $this->transaction(function () use (
            $entrada,
            $entradaId,
            $itemId,
            $data,
            $nome,
            $descricao,
            $codigo,
            $quantidade,
            $observacoes,
            $categoriaId,
            $marcaId,
            $fabricanteId,
            $colecaoId,
            $escalaId,
            $anoFabricacao
        ): void {
            $fields = [
                'categoria_id' => $categoriaId,
                'marca_id' => $marcaId,
                'fabricante_id' => $fabricanteId,
                'colecao_id' => $colecaoId,
                'escala_id' => $escalaId,
                'ano_fabricacao' => $anoFabricacao,
                'nome' => $nome,
                'descricao' => $descricao,
                'codigo' => $codigo,
                'quantidade' => $quantidade,
                'observacoes' => $observacoes,
            ];

            if ($entrada['modo'] === 'direta') {
                $baseMilesimos = $this->validarMoeda($data['custo_unitario_base'] ?? '', 'Custo unitário base');
                $fields['custo_unitario_base'] = $this->calculo->milesimosParaDecimal($baseMilesimos);
                $this->itens->update($itemId, $fields);
                $this->recalcularCustos($entradaId);

                return;
            }

            $ofertaSelecionada = $this->ofertas->selectedByItem($itemId);
            $baseMilesimos = $ofertaSelecionada !== null
                ? $this->calculo->paraMilesimos($ofertaSelecionada['preco_custo'])
                : 0;
            $custos = $this->calculo->calcularItemPrevisao($quantidade, $baseMilesimos);

            $this->itens->update($itemId, array_merge($fields, $custos));
        });

        if ($validatedFiles !== []) {
            $this->itemImagemService->salvar($itemId, $validatedFiles, $actor);
        }

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
        $imagens = $this->itemImagens->listByItem($itemId);

        $this->transaction(function () use ($entrada, $entradaId, $itemId): void {
            $this->itens->delete($itemId);

            if ($entrada['modo'] === 'direta') {
                $this->recalcularCustos($entradaId);
            }
        });

        $this->itemImagemService->deletarArquivos($imagens);

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
            $base += $this->calculo->paraMilesimos($item['custo_unitario_base']) * $quantidade;
            $real += $this->calculo->paraMilesimos($item['custo_total_real']);
        }

        $adicionais = 0;

        foreach ($custos as $custo) {
            $adicionais += $this->calculo->paraMilesimos($custo['valor']);
        }

        return [
            'base' => $this->calculo->milesimosParaDecimal($base),
            'adicionais' => $this->calculo->milesimosParaDecimal($adicionais),
            'real' => $this->calculo->milesimosParaDecimal($real),
            'itens' => count($itens),
        ];
    }

    private function validarOferta(array $data, bool $ignorarFornecedor = false): array
    {
        $fornecedor = $ignorarFornecedor
            ? null
            : $this->validarTexto($data['fornecedor'] ?? '', 2, 150, 'Fornecedor');
        $url = $this->validarUrl($data['url'] ?? '', true);
        $precoMilesimos = $this->validarMoeda($data['preco_custo'] ?? '', 'Preço de custo');
        $observacoes = $this->validarTextoOpcional($data['oferta_observacoes'] ?? '', 255, 'Observações da oferta');

        return [
            'fornecedor' => $fornecedor,
            'url' => $url,
            'preco_custo' => $this->calculo->milesimosParaDecimal($precoMilesimos),
            'observacoes' => $observacoes,
        ];
    }

    private function validarAuxiliarOpcional(mixed $valor, string $campo): ?int
    {
        $id = (int) $valor;

        if ($id <= 0) {
            return null;
        }

        if (!isset(self::AUX_ITENS[$campo])) {
            throw new InvalidArgumentException('Campo auxiliar inválido.');
        }

        $config = self::AUX_ITENS[$campo];

        if ($this->configuracoes->buscar($config['table'], $id) === null) {
            throw new InvalidArgumentException("{$config['label']} inválida.");
        }

        return $id;
    }

    private function validarAnoOpcional(mixed $valor): ?int
    {
        $texto = trim((string) $valor);

        if ($texto === '') {
            return null;
        }

        $ano = filter_var($texto, FILTER_VALIDATE_INT);

        if ($ano === false || $ano < 1900 || $ano > 2100) {
            throw new InvalidArgumentException('Ano de fabricação deve estar entre 1900 e 2100.');
        }

        return $ano;
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
            $milesimos = $this->calculo->paraMilesimos($valor);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException("{$campo} inválido.");
        }

        if ($milesimos < 0 || $milesimos > CalculoService::MAX_MONEY_MILESIMOS) {
            throw new InvalidArgumentException("{$campo} deve estar entre 0,000 e 999.999.999,999.");
        }

        return $milesimos;
    }

    private function removerItemAposFalha(int $itemId, int $entradaId, string $modo): void
    {
        $this->transaction(function () use ($itemId, $entradaId, $modo): void {
            $this->itens->delete($itemId);

            if ($modo === 'direta') {
                $this->recalcularCustos($entradaId);
            }
        });
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
