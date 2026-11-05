<?php

declare(strict_types=1);

$item = $item ?? [];
$entryId = (int) ($entryId ?? 0);
$isDireta = (bool) ($isDireta ?? false);
$itemId = (int) ($item['id'] ?? 0);
$isOld = (int) ($itemEditOld ?? 0) === $itemId;

$nome = $isOld ? old('nome', $item['nome'] ?? '') : ($item['nome'] ?? '');
$codigo = $isOld ? old('codigo', $item['codigo'] ?? '') : ($item['codigo'] ?? '');
$quantidade = $isOld ? old('quantidade', $item['quantidade'] ?? 1) : ($item['quantidade'] ?? 1);
$custoBase = $isOld ? old('custo_unitario_base', $item['custo_unitario_base'] ?? '0.00') : ($item['custo_unitario_base'] ?? '0.00');
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="<?= e(url('/admin/loja/entradas/' . $entryId . '/itens/' . $itemId . '/atualizar')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="form_context" value="item_edit">
                <input type="hidden" name="item_id" value="<?= $itemId ?>">

                <div class="modal-header">
                    <h3 class="modal-title h5" id="editItemLabel<?= $itemId ?>">Editar item</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
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
                                    placeholder="0,00"
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
