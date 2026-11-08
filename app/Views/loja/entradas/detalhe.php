<?php

declare(strict_types=1);

$detail = $detail ?? [];
$opcoes = $opcoes ?? [];
$maxItemImages = (int) config('app.uploads.loja_itens.max_images', 6);
$maxItemImageSize = (int) config('app.uploads.loja_itens.max_size', 5242880);
$entrada = $detail['entrada'] ?? [];
$custos = $detail['custos'] ?? [];
$itens = $detail['itens'] ?? [];
$totais = $detail['totais'] ?? ['base' => '0.00', 'adicionais' => '0.00', 'real' => '0.00', 'itens' => 0];

$isDireta = ($entrada['modo'] ?? '') === 'direta';
$entryId = (int) ($entrada['id'] ?? 0);
$recebido = (int) ($entrada['recebido'] ?? 0) === 1;
$semCustoPrevisao = !$isDireta && (int) $totais['itens'] > 0 && (float) $totais['real'] <= 0;

$oldContext = (string) old('form_context', '');
$oldItemId = (int) old('item_id', 0);
$entryOld = $oldContext === 'entry_edit';
$costOld = $oldContext === 'cost_add';
$itemAddOld = $oldContext === 'item_add';
$itemEditOld = $oldContext === 'item_edit' ? $oldItemId : 0;
$offerAddOld = $oldContext === 'offer_add' ? $oldItemId : 0;

$entryDescricao = $entryOld ? old('descricao_origem', $entrada['descricao_origem'] ?? '') : ($entrada['descricao_origem'] ?? '');
$entryLink = $entryOld ? old('link_compra', $entrada['link_compra'] ?? '') : ($entrada['link_compra'] ?? '');
$entryRastreio = $entryOld ? old('codigo_rastreio', $entrada['codigo_rastreio'] ?? '') : ($entrada['codigo_rastreio'] ?? '');
$entryObservacoes = $entryOld ? old('observacoes', $entrada['observacoes'] ?? '') : ($entrada['observacoes'] ?? '');

$custoDescricao = $costOld ? old('custo_descricao', '') : '';
$custoValor = $costOld ? old('custo_valor', '') : '';

$addCategoriaId = $itemAddOld ? (int) old('categoria_id', 0) : 0;
$addMarcaId = $itemAddOld ? (int) old('marca_id', 0) : 0;
$addFabricanteId = $itemAddOld ? (int) old('fabricante_id', 0) : 0;
$addColecaoId = $itemAddOld ? (int) old('colecao_id', 0) : 0;
$addEscalaId = $itemAddOld ? (int) old('escala_id', 0) : 0;
$addAnoFabricacao = $itemAddOld ? old('ano_fabricacao', '') : '';
$addNome = $itemAddOld ? old('nome', '') : '';
$addCodigo = $itemAddOld ? old('codigo', '') : '';
$addQuantidade = $itemAddOld ? old('quantidade', '1') : '1';
$addBase = $itemAddOld ? old('custo_unitario_base', '') : '';
$addDescricao = $itemAddOld ? old('descricao', '') : '';
$addObservacoes = $itemAddOld ? old('item_observacoes', '') : '';
$addFornecedor = $itemAddOld ? old('fornecedor', '') : '';
$addUrl = $itemAddOld ? old('url', '') : '';
$addPreco = $itemAddOld ? old('preco_custo', '') : '';
$addOfertaObservacoes = $itemAddOld ? old('oferta_observacoes', '') : '';

$renderConfigOptions = static function (array $items, int $selected): void {
    foreach ($items as $item) {
        $nome = (string) $item['nome'];

        if (($item['status'] ?? 'ativo') === 'inativo') {
            $nome .= ' (inativo)';
        }

        echo '<option value="' . (int) $item['id'] . '"'
            . ((int) $item['id'] === $selected ? ' selected' : '')
            . '>' . e($nome) . '</option>';
    }
};
?>
<?php require __DIR__ . '/../partials/tabs.php'; ?>

<section class="panel-card loja-entry-head">
    <div class="loja-entry-top">
        <div class="loja-entry-info">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge-soft"><?= e(loja_mode_label((string) ($entrada['modo'] ?? ''))) ?></span>
                <?php if ($isDireta): ?>
                    <span class="status-pill <?= $recebido ? 'status-pill-active' : 'status-pill-transit' ?>">
                        <?= $recebido ? 'Recebida' : 'Em transporte' ?>
                    </span>
                <?php else: ?>
                    <span class="status-pill status-pill-pre">Em pré-venda</span>
                <?php endif; ?>
            </div>

            <h2 class="loja-entry-title"><?= e((string) ($entrada['descricao_origem'] ?? '')) ?></h2>
            <p class="loja-entry-meta">
                Criada em <?= e(format_datetime($entrada['criado_em'] ?? null)) ?>
                por <?= e((string) ($entrada['criado_por_nome'] ?? 'Sistema')) ?>
            </p>

            <?php if ($isDireta && !empty($entrada['link_compra'])): ?>
                <p class="mb-1">
                    <i class="bi bi-link-45deg" aria-hidden="true"></i>
                    <a href="<?= e((string) $entrada['link_compra']) ?>" target="_blank" rel="noopener noreferrer">
                        Abrir link de compra
                    </a>
                </p>
            <?php endif; ?>

            <?php if ($isDireta && !empty($entrada['codigo_rastreio'])): ?>
                <p class="mb-1">
                    <i class="bi bi-upc-scan" aria-hidden="true"></i>
                    Rastreio: <code><?= e((string) $entrada['codigo_rastreio']) ?></code>
                </p>
            <?php endif; ?>

            <?php if (!empty($entrada['observacoes'])): ?>
                <p class="loja-entry-notes"><?= nl2br(e((string) $entrada['observacoes'])) ?></p>
            <?php endif; ?>
        </div>

        <div class="loja-entry-actions">
            <button
                type="button"
                class="btn btn-outline-secondary"
                data-bs-toggle="collapse"
                data-bs-target="#editEntry"
                aria-expanded="<?= $entryOld ? 'true' : 'false' ?>"
                aria-controls="editEntry"
            >
                <i class="bi bi-pencil" aria-hidden="true"></i>
                Editar dados
            </button>

            <?php if ($isDireta): ?>
                <form method="post" action="<?= e(url('/admin/loja/entradas/' . $entryId . '/recebido')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn <?= $recebido ? 'btn-outline-warning' : 'btn-brand' ?>">
                        <i class="bi <?= $recebido ? 'bi-arrow-counterclockwise' : 'bi-check2-circle' ?>" aria-hidden="true"></i>
                        <?= $recebido ? 'Desmarcar recebimento' : 'Marcar como recebida' ?>
                    </button>
                </form>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/admin/loja/entradas/' . $entryId . '/excluir')) ?>">
                <?= csrf_field() ?>
                <button
                    type="submit"
                    class="btn btn-outline-danger"
                    data-confirm="<?= e('Excluir a entrada "' . $entrada['descricao_origem'] . '"? Itens, custos e ofertas vinculados também serão removidos.') ?>"
                >
                    <i class="bi bi-trash" aria-hidden="true"></i>
                    Excluir entrada
                </button>
            </form>
        </div>
    </div>

    <div class="collapse<?= $entryOld ? ' show' : '' ?>" id="editEntry">
        <form method="post" action="<?= e(url('/admin/loja/entradas/' . $entryId)) ?>" class="loja-edit-entry">
            <?= csrf_field() ?>
            <input type="hidden" name="form_context" value="entry_edit">

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="edit_descricao_origem" class="form-label">Descrição de origem / previsão</label>
                    <input
                        type="text"
                        class="form-control"
                        id="edit_descricao_origem"
                        name="descricao_origem"
                        value="<?= e((string) $entryDescricao) ?>"
                        minlength="3"
                        maxlength="180"
                        required
                    >
                </div>

                <?php if ($isDireta): ?>
                    <div class="col-12 col-md-6">
                        <label for="edit_link_compra" class="form-label">Link de compra</label>
                        <input
                            type="url"
                            class="form-control"
                            id="edit_link_compra"
                            name="link_compra"
                            value="<?= e((string) $entryLink) ?>"
                            maxlength="500"
                            required
                        >
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="edit_codigo_rastreio" class="form-label">Código de rastreio</label>
                        <input
                            type="text"
                            class="form-control"
                            id="edit_codigo_rastreio"
                            name="codigo_rastreio"
                            value="<?= e((string) $entryRastreio) ?>"
                            maxlength="120"
                        >
                    </div>
                <?php endif; ?>

                <div class="col-12">
                    <label for="edit_observacoes" class="form-label">Observações</label>
                    <textarea
                        class="form-control"
                        id="edit_observacoes"
                        name="observacoes"
                        rows="3"
                        maxlength="2000"
                    ><?= e((string) $entryObservacoes) ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#editEntry">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-brand">Salvar alterações</button>
            </div>
        </form>
    </div>
</section>

<div class="loja-summary-grid">
    <article class="loja-summary-card">
        <span class="loja-summary-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></span>
        <div>
            <span class="loja-summary-label"><?= $isDireta ? 'Total base' : 'Custo base selecionado' ?></span>
            <strong class="loja-summary-value">
                <?= $semCustoPrevisao ? 'A definir' : e(format_money($totais['base'])) ?>
            </strong>
        </div>
    </article>

    <?php if ($isDireta): ?>
        <article class="loja-summary-card">
            <span class="loja-summary-icon"><i class="bi bi-truck" aria-hidden="true"></i></span>
            <div>
                <span class="loja-summary-label">Custos adicionais</span>
                <strong class="loja-summary-value"><?= e(format_money($totais['adicionais'])) ?></strong>
            </div>
        </article>
    <?php endif; ?>

    <article class="loja-summary-card">
        <span class="loja-summary-icon"><i class="bi bi-calculator" aria-hidden="true"></i></span>
        <div>
            <span class="loja-summary-label">Custo real total</span>
            <strong class="loja-summary-value">
                <?= $semCustoPrevisao ? 'A definir' : e(format_money($totais['real'])) ?>
            </strong>
        </div>
    </article>

    <article class="loja-summary-card">
        <span class="loja-summary-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
        <div>
            <span class="loja-summary-label">Itens cadastrados</span>
            <strong class="loja-summary-value"><?= (int) $totais['itens'] ?></strong>
        </div>
    </article>
</div>

<?php if ($isDireta): ?>
    <section class="panel-card mt-3">
        <div class="panel-card-head">
            <div>
                <h2 class="panel-card-title">Custos adicionais</h2>
                <p class="panel-card-subtitle mb-0">Frete, impostos, taxas e outros valores agregados ao custo real.</p>
            </div>
            <span class="badge-soft">Total: <?= e(format_money($totais['adicionais'])) ?></span>
        </div>

        <div class="loja-section-body">
            <form method="post" action="<?= e(url('/admin/loja/entradas/' . $entryId . '/custos')) ?>" class="loja-inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="form_context" value="cost_add">

                <div class="loja-inline-field loja-inline-field-grow">
                    <label for="custo_descricao" class="form-label">Descrição</label>
                    <input
                        type="text"
                        class="form-control"
                        id="custo_descricao"
                        name="custo_descricao"
                        value="<?= e((string) $custoDescricao) ?>"
                        minlength="2"
                        maxlength="150"
                        placeholder="Ex.: frete, imposto, embalagem"
                        required
                    >
                </div>

                <div class="loja-inline-field">
                    <label for="custo_valor" class="form-label">Valor</label>
                    <input
                        type="text"
                        class="form-control"
                        id="custo_valor"
                        name="custo_valor"
                        value="<?= e((string) $custoValor) ?>"
                        inputmode="decimal"
                        placeholder="0,000"
                        data-money
                        required
                    >
                </div>

                <div class="loja-inline-field loja-inline-action">
                    <button type="submit" class="btn btn-brand">Adicionar custo</button>
                </div>
            </form>

            <?php if ($custos === []): ?>
                <div class="loja-empty-inline">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    Nenhum custo adicional cadastrado. O custo real é igual ao custo base dos itens.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Descrição</th>
                                <th scope="col">Valor</th>
                                <th scope="col" class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($custos as $custo): ?>
                                <?php $custoId = (int) $custo['id']; ?>
                                <tr>
                                    <td><?= e((string) $custo['descricao']) ?></td>
                                    <td><?= e(format_money($custo['valor'])) ?></td>
                                    <td class="text-end">
                                        <form method="post" action="<?= e(url('/admin/loja/entradas/' . $entryId . '/custos/' . $custoId . '/excluir')) ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                                data-confirm="<?= e('Excluir o custo "' . $custo['descricao'] . '"? O custo real dos itens será recalculado.') ?>"
                                            >
                                                Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<section class="panel-card mt-3">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title"><?= $isDireta ? 'Itens da compra' : 'Itens da previsão' ?></h2>
            <p class="panel-card-subtitle mb-0">
                <?= $isDireta
                    ? 'Mercadorias vinculadas a esta entrada e seus custos reais.'
                    : 'Itens disponibilizados para pré-venda com seus fornecedores.' ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge-soft"><?= count($itens) ?> item(ns)</span>
            <button
                type="button"
                class="btn btn-brand loja-entry-add-item"
                data-bs-toggle="modal"
                data-bs-target="#addItemModal"
                aria-label="Adicionar item"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Adicionar item
            </button>
        </div>
    </div>

    <div class="loja-section-body">
        <div
            class="modal fade"
            id="addItemModal"
            tabindex="-1"
            aria-labelledby="addItemModalLabel"
            aria-hidden="true"
            data-auto-open="<?= $itemAddOld ? '1' : '0' ?>"
        >
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <form
                        method="post"
                        action="<?= e(url('/admin/loja/entradas/' . $entryId . '/itens')) ?>"
                        class="loja-item-modal-form"
                        enctype="multipart/form-data"
                    >
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_context" value="item_add">

                        <div class="modal-header">
                            <div>
                                <h3 class="modal-title h5" id="addItemModalLabel">Adicionar item</h3>
                                <p class="modal-subtitle mb-0">
                                    Cadastre a mercadoria, os custos e as fotos do item.
                                </p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>

                        <div class="modal-body" style="max-height: 75vh;">
                            <div class="row g-3">
                <div class="col-12">
                    <div class="loja-form-divider"><span>Catálogo do item</span></div>
                </div>

                <div class="col-12 col-md-4">
                    <label for="add_categoria" class="form-label">Categoria</label>
                    <select class="form-select" id="add_categoria" name="categoria_id">
                        <option value="">Não informada</option>
                        <?php $renderConfigOptions($opcoes['categorias'] ?? [], $addCategoriaId); ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label for="add_marca" class="form-label">Marca</label>
                    <select class="form-select" id="add_marca" name="marca_id">
                        <option value="">Não informada</option>
                        <?php $renderConfigOptions($opcoes['marcas'] ?? [], $addMarcaId); ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label for="add_fabricante" class="form-label">Fabricante</label>
                    <select class="form-select" id="add_fabricante" name="fabricante_id">
                        <option value="">Não informado</option>
                        <?php $renderConfigOptions($opcoes['fabricantes'] ?? [], $addFabricanteId); ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label for="add_colecao" class="form-label">Coleção</label>
                    <select class="form-select" id="add_colecao" name="colecao_id">
                        <option value="">Não informada</option>
                        <?php $renderConfigOptions($opcoes['colecoes'] ?? [], $addColecaoId); ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label for="add_escala" class="form-label">Escala</label>
                    <select class="form-select" id="add_escala" name="escala_id">
                        <option value="">Não informada</option>
                        <?php $renderConfigOptions($opcoes['escalas'] ?? [], $addEscalaId); ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label for="add_ano_fabricacao" class="form-label">Ano de fabricação</label>
                    <input
                        type="number"
                        class="form-control"
                        id="add_ano_fabricacao"
                        name="ano_fabricacao"
                        value="<?= e((string) $addAnoFabricacao) ?>"
                        min="1900"
                        max="2100"
                    >
                </div>

                <div class="col-12 col-md-5">
                    <label for="add_nome" class="form-label">Nome do item</label>
                    <input
                        type="text"
                        class="form-control"
                        id="add_nome"
                        name="nome"
                        value="<?= e((string) $addNome) ?>"
                        minlength="2"
                        maxlength="180"
                        required
                    >
                </div>
                <div class="col-6 col-md-3">
                    <label for="add_codigo" class="form-label">Código/SKU</label>
                    <input
                        type="text"
                        class="form-control"
                        id="add_codigo"
                        name="codigo"
                        value="<?= e((string) $addCodigo) ?>"
                        maxlength="80"
                    >
                </div>
                <div class="col-6 col-md-2">
                    <label for="add_quantidade" class="form-label">Quantidade</label>
                    <input
                        type="number"
                        class="form-control"
                        id="add_quantidade"
                        name="quantidade"
                        value="<?= e((string) $addQuantidade) ?>"
                        min="1"
                        max="999999"
                        required
                    >
                </div>

                <?php if ($isDireta): ?>
                    <div class="col-12 col-md-2">
                        <label for="add_custo" class="form-label">Custo unit. base</label>
                        <input
                            type="text"
                            class="form-control"
                            id="add_custo"
                            name="custo_unitario_base"
                            value="<?= e((string) $addBase) ?>"
                            inputmode="decimal"
                            placeholder="0,000"
                            data-money
                            required
                        >
                    </div>
                <?php endif; ?>

                <div class="col-12">
                    <label for="add_descricao" class="form-label">Descrição (opcional)</label>
                    <input
                        type="text"
                        class="form-control"
                        id="add_descricao"
                        name="descricao"
                        value="<?= e((string) $addDescricao) ?>"
                        maxlength="5000"
                    >
                </div>

                <div class="col-12">
                    <label for="add_observacoes" class="form-label">Observações (opcional)</label>
                    <input
                        type="text"
                        class="form-control"
                        id="add_observacoes"
                        name="item_observacoes"
                        value="<?= e((string) $addObservacoes) ?>"
                        maxlength="2000"
                    >
                </div>

                <?php if (!$isDireta): ?>
                    <div class="col-12">
                        <div class="loja-form-divider">
                            <span>Primeira oferta de fornecedor</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="add_fornecedor" class="form-label">Fornecedor</label>
                        <input
                            type="text"
                            class="form-control"
                            id="add_fornecedor"
                            name="fornecedor"
                            value="<?= e((string) $addFornecedor) ?>"
                            minlength="2"
                            maxlength="150"
                            required
                        >
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="add_url" class="form-label">Link do fornecedor</label>
                        <input
                            type="url"
                            class="form-control"
                            id="add_url"
                            name="url"
                            value="<?= e((string) $addUrl) ?>"
                            maxlength="500"
                            placeholder="https://..."
                            required
                        >
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="add_preco" class="form-label">Preço de custo</label>
                        <input
                            type="text"
                            class="form-control"
                            id="add_preco"
                            name="preco_custo"
                            value="<?= e((string) $addPreco) ?>"
                            inputmode="decimal"
                            placeholder="0,000"
                            data-money
                            required
                        >
                    </div>
                    <div class="col-12">
                        <label for="add_oferta_observacoes" class="form-label">Observações da oferta (opcional)</label>
                        <input
                            type="text"
                            class="form-control"
                            id="add_oferta_observacoes"
                            name="oferta_observacoes"
                            value="<?= e((string) $addOfertaObservacoes) ?>"
                            maxlength="255"
                        >
                    </div>
                <?php endif; ?>

                            <div class="col-12">
                                <div class="loja-form-divider"><span>Fotos do item</span></div>
                            </div>

                            <div class="col-12">
                                <label for="add_imagens" class="form-label">Fotos do item (opcional)</label>
                                <input
                                    type="file"
                                    class="form-control"
                                    id="add_imagens"
                                    name="imagens[]"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    data-image-preview="itemImagePreview"
                                >
                                <div class="form-text">
                                    Até <?= $maxItemImages ?> fotos, nos formatos JPG, PNG ou WebP, com no máximo
                                    <?= e(number_format($maxItemImageSize / 1048576, 1, ',', '.')) ?> MB cada.
                                    As fotos precisam ser selecionadas novamente se houver erro no cadastro.
                                </div>
                                <div class="image-preview-grid" id="itemImagePreview" aria-live="polite"></div>
                            </div>
                        </div>
                    </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-brand">
                                <i class="bi bi-check-lg" aria-hidden="true"></i>
                                Salvar item
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($itens === []): ?>
            <div class="empty-state loja-items-empty">
                <i class="bi bi-box" aria-hidden="true"></i>
                <p class="mb-1">Nenhum item cadastrado.</p>
                <span>Use o botão "Adicionar item" para incluir a primeira mercadoria.</span>
            </div>
        <?php elseif ($isDireta): ?>
            <div class="table-responsive">
                <table class="table admin-table loja-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Item</th>
                            <th scope="col">Qtd.</th>
                            <th scope="col">Custo base</th>
                            <th scope="col">Rateado</th>
                            <th scope="col">Custo real/un.</th>
                            <th scope="col">Total real</th>
                            <th scope="col">Situação</th>
                            <th scope="col" class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item): ?>
                            <?php $itemId = (int) $item['id']; ?>
                            <tr>
                                <td>
                                    <div class="loja-item-cell">
                                        <span class="loja-item-thumb">
                                            <?php if (!empty($item['imagem_id'])): ?>
                                                <img
                                                    src="<?= e((string) item_imagem_url((int) $item['imagem_id'])) ?>"
                                                    alt="<?= e((string) $item['nome']) ?>"
                                                    loading="lazy"
                                                >
                                            <?php else: ?>
                                                <i class="bi bi-image" aria-hidden="true"></i>
                                            <?php endif; ?>
                                        </span>
                                        <div class="loja-item-cell-info">
                                            <strong class="d-block"><?= e((string) $item['nome']) ?></strong>
                                            <?php if (!empty($item['codigo'])): ?>
                                                <span class="small text-muted">Código: <?= e((string) $item['codigo']) ?></span>
                                            <?php endif; ?>
                                            <?php
                                            $itemMeta = array_filter([
                                                $item['categoria_nome'] ?? null,
                                                $item['marca_nome'] ?? null,
                                                $item['escala_nome'] ?? null,
                                                !empty($item['ano_fabricacao']) ? 'Ano ' . $item['ano_fabricacao'] : null,
                                            ]);
                                            ?>
                                            <?php if ($itemMeta !== []): ?>
                                                <span class="small text-muted d-block"><?= e(implode(' · ', $itemMeta)) ?></span>
                                            <?php endif; ?>
                                            <?php if ((int) ($item['imagens_count'] ?? 0) > 0): ?>
                                                <span class="small text-muted d-block"><?= (int) $item['imagens_count'] ?> foto(s)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= (int) $item['quantidade'] ?></td>
                                <td><?= e(format_money($item['custo_unitario_base'])) ?></td>
                                <td><?= e(format_money($item['custo_adicional_rateado'])) ?></td>
                                <td><strong><?= e(format_money($item['custo_unitario_real'])) ?></strong></td>
                                <td><?= e(format_money($item['custo_total_real'])) ?></td>
                                <td>
                                    <span class="status-pill <?= e(loja_status_class((string) $item['status'])) ?>">
                                        <?= e(loja_status_label((string) $item['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="table-actions">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editItem<?= $itemId ?>"
                                        >
                                            Editar
                                        </button>
                                        <form method="post" action="<?= e(url('/admin/loja/itens/' . $itemId . '/excluir')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="entrada_id" value="<?= $entryId ?>">
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                                data-confirm="<?= e('Excluir o item "' . $item['nome'] . '"?') ?>"
                                            >
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php foreach ($itens as $item): ?>
                <?php
                $itemEditOld = $oldContext === 'item_edit' ? $oldItemId : 0;
                require __DIR__ . '/../partials/item-edit-modal.php';
                ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="loja-prevision-items">
                <?php foreach ($itens as $item): ?>
                    <?php
                    $itemId = (int) $item['id'];
                    $ofertas = $item['ofertas'] ?? [];
                    $menorOferta = $item['menor_oferta'] ?? null;
                    $ofertaIsOld = $offerAddOld === $itemId;
                    $ofFornecedor = $ofertaIsOld ? old('fornecedor', '') : '';
                    $ofUrl = $ofertaIsOld ? old('url', '') : '';
                    $ofPreco = $ofertaIsOld ? old('preco_custo', '') : '';
                    $ofObservacoes = $ofertaIsOld ? old('oferta_observacoes', '') : '';
                    ?>
                    <article class="loja-item-card" id="item-<?= (int) $item['id'] ?>">
                        <header class="loja-item-card-head">
                            <div class="loja-item-card-main">
                                <span class="loja-item-thumb">
                                    <?php if (!empty($item['imagem_id'])): ?>
                                        <img
                                            src="<?= e((string) item_imagem_url((int) $item['imagem_id'])) ?>"
                                            alt="<?= e((string) $item['nome']) ?>"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <i class="bi bi-image" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </span>
                                <div>
                                    <h3 class="loja-item-title"><?= e((string) $item['nome']) ?></h3>
                                    <p class="loja-item-meta mb-0">
                                        <?php if (!empty($item['codigo'])): ?>
                                            Código: <?= e((string) $item['codigo']) ?> ·
                                        <?php endif; ?>
                                        Quantidade: <?= (int) $item['quantidade'] ?> ·
                                        <?= count($ofertas) ?> oferta(s)
                                        <?php if ((int) ($item['imagens_count'] ?? 0) > 0): ?>
                                            · <?= (int) $item['imagens_count'] ?> foto(s)
                                        <?php endif; ?>
                                    </p>
                                    <?php
                                    $itemMeta = array_filter([
                                        $item['categoria_nome'] ?? null,
                                        $item['marca_nome'] ?? null,
                                        $item['escala_nome'] ?? null,
                                        !empty($item['ano_fabricacao']) ? 'Ano ' . $item['ano_fabricacao'] : null,
                                    ]);
                                    ?>
                                    <?php if ($itemMeta !== []): ?>
                                        <p class="loja-item-meta mb-0"><?= e(implode(' · ', $itemMeta)) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="loja-item-cost">
                                <span class="status-pill status-pill-pre">Em pré-venda</span>
                                <?php if ($item['fornecedor_selecionado'] === null): ?>
                                    <span class="badge-soft">Custo a definir</span>
                                <?php else: ?>
                                    <span class="loja-price">
                                        <?= e(format_money($item['custo_unitario_real'])) ?> <small>/ un.</small>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </header>

                        <?php if ($ofertas === []): ?>
                            <div class="loja-empty-inline">
                                <i class="bi bi-info-circle" aria-hidden="true"></i>
                                Nenhuma oferta cadastrada para este item.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table admin-table loja-offer-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col">Fornecedor</th>
                                            <th scope="col">Preço de custo</th>
                                            <th scope="col">Link</th>
                                            <th scope="col">Situação</th>
                                            <th scope="col" class="text-end">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ofertas as $oferta): ?>
                                            <?php
                                            $ofertaId = (int) $oferta['id'];
                                            $selecionada = (int) $oferta['selecionado'] === 1;
                                            $menor = $menorOferta !== null
                                                && abs((float) $oferta['preco_custo'] - (float) $menorOferta) < 0.001;
                                            ?>
                                            <tr class="<?= $selecionada ? 'loja-offer-selected' : '' ?>">
                                                <td><?= e((string) $oferta['fornecedor']) ?></td>
                                                <td><strong><?= e(format_money($oferta['preco_custo'])) ?></strong></td>
                                                <td>
                                                    <a href="<?= e((string) $oferta['url']) ?>" target="_blank" rel="noopener noreferrer">
                                                        Abrir link
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php if ($selecionada): ?>
                                                        <span class="badge-soft badge-soft-yellow">Selecionada</span>
                                                    <?php elseif ($menor): ?>
                                                        <span class="badge-soft">Menor preço</span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Disponível</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="table-actions">
                                                        <?php if (!$selecionada): ?>
                                                            <form method="post" action="<?= e(url('/admin/loja/itens/' . $itemId . '/fornecedores/' . $ofertaId . '/selecionar')) ?>">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="entrada_id" value="<?= $entryId ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-success">Selecionar</button>
                                                            </form>
                                                        <?php endif; ?>

                                                        <form method="post" action="<?= e(url('/admin/loja/itens/' . $itemId . '/fornecedores/' . $ofertaId . '/excluir')) ?>">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="entrada_id" value="<?= $entryId ?>">
                                                            <button
                                                                type="submit"
                                                                class="btn btn-sm btn-outline-danger"
                                                                data-confirm="<?= e($selecionada
                                                                    ? 'Excluir a oferta selecionada? O custo do item voltará para "Custo a definir".'
                                                                    : 'Excluir esta oferta de fornecedor?') ?>"
                                                            >
                                                                Excluir
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?= e(url('/admin/loja/itens/' . $itemId . '/fornecedores')) ?>" class="loja-inline-form loja-offer-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="form_context" value="offer_add">
                            <input type="hidden" name="entrada_id" value="<?= $entryId ?>">
                            <input type="hidden" name="item_id" value="<?= $itemId ?>">

                            <div class="loja-inline-field loja-inline-field-grow">
                                <label for="fornecedor<?= $itemId ?>" class="form-label">Fornecedor</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="fornecedor<?= $itemId ?>"
                                    name="fornecedor"
                                    value="<?= e((string) $ofFornecedor) ?>"
                                    minlength="2"
                                    maxlength="150"
                                    required
                                >
                            </div>
                            <div class="loja-inline-field loja-inline-field-grow">
                                <label for="url<?= $itemId ?>" class="form-label">Link</label>
                                <input
                                    type="url"
                                    class="form-control"
                                    id="url<?= $itemId ?>"
                                    name="url"
                                    value="<?= e((string) $ofUrl) ?>"
                                    maxlength="500"
                                    placeholder="https://..."
                                    required
                                >
                            </div>
                            <div class="loja-inline-field">
                                <label for="preco<?= $itemId ?>" class="form-label">Preço de custo</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="preco<?= $itemId ?>"
                                    name="preco_custo"
                                    value="<?= e((string) $ofPreco) ?>"
                                    inputmode="decimal"
                                    placeholder="0,000"
                                    data-money
                                    required
                                >
                            </div>
                            <div class="loja-inline-field loja-inline-action">
                                <button type="submit" class="btn btn-brand">Adicionar oferta</button>
                            </div>
                        </form>

                        <div class="loja-item-card-actions">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal"
                                data-bs-target="#editItem<?= $itemId ?>"
                            >
                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                Editar item
                            </button>

                            <form method="post" action="<?= e(url('/admin/loja/itens/' . $itemId . '/excluir')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="entrada_id" value="<?= $entryId ?>">
                                <button
                                    type="submit"
                                    class="btn btn-sm btn-outline-danger"
                                    data-confirm="<?= e('Excluir o item "' . $item['nome'] . '" e todas as suas ofertas?') ?>"
                                >
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                    Excluir item
                                </button>
                            </form>
                        </div>
                    </article>

                    <?php
                    $itemEditOld = $oldContext === 'item_edit' ? $oldItemId : 0;
                    require __DIR__ . '/../partials/item-edit-modal.php';
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
