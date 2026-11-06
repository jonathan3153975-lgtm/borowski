<?php

declare(strict_types=1);

$item = $item ?? [];
$opcoes = $opcoes ?? [];
$entryId = (int) ($entryId ?? 0);
$isDireta = (bool) ($isDireta ?? false);
$itemId = (int) ($item['id'] ?? 0);
$isOld = (int) ($itemEditOld ?? 0) === $itemId;
$categoriaSelecionada = $isOld
    ? (int) old('categoria_id', $item['categoria_id'] ?? 0)
    : (int) ($item['categoria_id'] ?? 0);
$marcaSelecionada = $isOld
    ? (int) old('marca_id', $item['marca_id'] ?? 0)
    : (int) ($item['marca_id'] ?? 0);
$fabricanteSelecionado = $isOld
    ? (int) old('fabricante_id', $item['fabricante_id'] ?? 0)
    : (int) ($item['fabricante_id'] ?? 0);
$colecaoSelecionada = $isOld
    ? (int) old('colecao_id', $item['colecao_id'] ?? 0)
    : (int) ($item['colecao_id'] ?? 0);
$escalaSelecionada = $isOld
    ? (int) old('escala_id', $item['escala_id'] ?? 0)
    : (int) ($item['escala_id'] ?? 0);
$anoFabricacao = $isOld
    ? old('ano_fabricacao', $item['ano_fabricacao'] ?? '')
    : ($item['ano_fabricacao'] ?? '');

$nome = $isOld ? old('nome', $item['nome'] ?? '') : ($item['nome'] ?? '');
$codigo = $isOld ? old('codigo', $item['codigo'] ?? '') : ($item['codigo'] ?? '');
$quantidade = $isOld ? old('quantidade', $item['quantidade'] ?? 1) : ($item['quantidade'] ?? 1);
$custoBase = $isOld
    ? old('custo_unitario_base', format_money_input($item['custo_unitario_base'] ?? '0.000'))
    : format_money_input($item['custo_unitario_base'] ?? '0.000');
$descricao = $isOld ? old('descricao', $item['descricao'] ?? '') : ($item['descricao'] ?? '');
$observacoes = $isOld ? old('item_observacoes', $item['observacoes'] ?? '') : ($item['observacoes'] ?? '');
?>
<div
    class="modal fade"
    id="editItem<?= $itemId ?>"
    tabindex="-1"
    aria-labelledby="editItemLabel<?= $itemId ?>"
    aria-hidden="true"
    data-auto-open="<?= $isOld ? '1' : '0' ?>"
>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form
                method="post"
                action="<?= e(url('/admin/loja/entradas/' . $entryId . '/itens/' . $itemId . '/atualizar')) ?>"
                class="loja-item-modal-form"
                enctype="multipart/form-data"
            >
                <?= csrf_field() ?>
                <input type="hidden" name="form_context" value="item_edit">
                <input type="hidden" name="item_id" value="<?= $itemId ?>">

                <div class="modal-header">
                    <h3 class="modal-title h5" id="editItemLabel<?= $itemId ?>">Editar item</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body" style="max-height: 75vh;">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="loja-form-divider"><span>Catálogo do item</span></div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="categoria<?= $itemId ?>" class="form-label">Categoria</label>
                            <select class="form-select" id="categoria<?= $itemId ?>" name="categoria_id">
                                <option value="">Não informada</option>
                                <?php $renderConfigOptions($opcoes['categorias'] ?? [], $categoriaSelecionada); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="marca<?= $itemId ?>" class="form-label">Marca</label>
                            <select class="form-select" id="marca<?= $itemId ?>" name="marca_id">
                                <option value="">Não informada</option>
                                <?php $renderConfigOptions($opcoes['marcas'] ?? [], $marcaSelecionada); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="fabricante<?= $itemId ?>" class="form-label">Fabricante</label>
                            <select class="form-select" id="fabricante<?= $itemId ?>" name="fabricante_id">
                                <option value="">Não informado</option>
                                <?php $renderConfigOptions($opcoes['fabricantes'] ?? [], $fabricanteSelecionado); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="colecao<?= $itemId ?>" class="form-label">Coleção</label>
                            <select class="form-select" id="colecao<?= $itemId ?>" name="colecao_id">
                                <option value="">Não informada</option>
                                <?php $renderConfigOptions($opcoes['colecoes'] ?? [], $colecaoSelecionada); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="escala<?= $itemId ?>" class="form-label">Escala</label>
                            <select class="form-select" id="escala<?= $itemId ?>" name="escala_id">
                                <option value="">Não informada</option>
                                <?php $renderConfigOptions($opcoes['escalas'] ?? [], $escalaSelecionada); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="ano<?= $itemId ?>" class="form-label">Ano de fabricação</label>
                            <input
                                type="number"
                                class="form-control"
                                id="ano<?= $itemId ?>"
                                name="ano_fabricacao"
                                value="<?= e((string) $anoFabricacao) ?>"
                                min="1900"
                                max="2100"
                            >
                        </div>

                        <div class="col-12 col-md-8">
                            <label for="nome<?= $itemId ?>" class="form-label">Nome</label>
                            <input
                                type="text"
                                class="form-control"
                                id="nome<?= $itemId ?>"
                                name="nome"
                                value="<?= e($nome) ?>"
                                minlength="2"
                                maxlength="180"
                                required
                            >
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="quantidade<?= $itemId ?>" class="form-label">Quantidade</label>
                            <input
                                type="number"
                                class="form-control"
                                id="quantidade<?= $itemId ?>"
                                name="quantidade"
                                value="<?= e((string) $quantidade) ?>"
                                min="1"
                                max="999999"
                                required
                            >
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="codigo<?= $itemId ?>" class="form-label">Código/SKU</label>
                            <input
                                type="text"
                                class="form-control"
                                id="codigo<?= $itemId ?>"
                                name="codigo"
                                value="<?= e($codigo) ?>"
                                maxlength="80"
                            >
                        </div>
                        <?php if ($isDireta): ?>
                            <div class="col-12 col-md-6">
                                <label for="custo<?= $itemId ?>" class="form-label">Custo unitário base</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="custo<?= $itemId ?>"
                                    name="custo_unitario_base"
                                    value="<?= e($custoBase) ?>"
                                    inputmode="decimal"
                                    placeholder="0,000"
                                    data-money
                                    required
                                >
                            </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <label for="descricao<?= $itemId ?>" class="form-label">Descrição</label>
                            <textarea
                                class="form-control"
                                id="descricao<?= $itemId ?>"
                                name="descricao"
                                rows="2"
                                maxlength="5000"
                            ><?= e($descricao) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label for="observacoes<?= $itemId ?>" class="form-label">Observações</label>
                            <textarea
                                class="form-control"
                                id="observacoes<?= $itemId ?>"
                                name="item_observacoes"
                                rows="2"
                                maxlength="2000"
                            ><?= e($observacoes) ?></textarea>
                        </div>

                        <div class="col-12">
                            <div class="loja-form-divider"><span>Fotos do item</span></div>
                        </div>

                        <div class="col-12">
                            <label for="edit_imagens<?= $itemId ?>" class="form-label">Fotos do item (opcional)</label>
                            <input
                                type="file"
                                class="form-control"
                                id="edit_imagens<?= $itemId ?>"
                                name="imagens[]"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                                data-image-preview="editItemImagePreview<?= $itemId ?>"
                            >
                            <div class="form-text">
                                Até <?= $maxItemImages ?> fotos, nos formatos JPG, PNG ou WebP, com no máximo
                                <?= e(number_format($maxItemImageSize / 1048576, 1, ',', '.')) ?> MB cada.
                                As fotos precisam ser selecionadas novamente se houver erro no cadastro.
                            </div>
                            <div class="image-preview-grid" id="editItemImagePreview<?= $itemId ?>" aria-live="polite"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Salvar item</button>
                </div>
            </form>
        </div>
    </div>
</div>
