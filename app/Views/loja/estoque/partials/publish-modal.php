<?php

declare(strict_types=1);

$itens = $itens ?? [];
$mapa = $mapa ?? [];
$canais = $canais ?? [];
$publicarId = (int) ($publicarId ?? 0);
$autoOpen = $publicarId > 0;

$canaisModal = array_values(array_filter(
    $canais,
    static fn (array $canal): bool => in_array((string) $canal['slug'], ['loja', 'telegram'], true)
));

if ($canaisModal === []) {
    $canaisModal = [['id' => 0, 'slug' => 'loja', 'nome' => 'Loja virtual']];
}
?>
<div
    class="modal fade"
    id="publishModal"
    tabindex="-1"
    aria-labelledby="publishModalLabel"
    aria-hidden="true"
    data-auto-open="<?= $autoOpen ? '1' : '0' ?>"
    data-publish-preselect="<?= $publicarId ?>"
>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form
                method="post"
                action="<?= e(url('/admin/loja/estoque/publicados')) ?>"
                data-publish-form
            >
                <?= csrf_field() ?>

                <div class="modal-header">
                    <div>
                        <h3 class="modal-title h5" id="publishModalLabel">Publicar item</h3>
                        <p class="modal-subtitle mb-0">
                            Escolha os canais, veja como o anúncio ficará e defina o valor e a quantidade.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <?php if ($itens === []): ?>
                        <div class="loja-empty-inline">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            Nenhum item disponível para publicar. O item precisa estar disponível em estoque.
                        </div>
                    <?php endif; ?>

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="publish_item" class="form-label">Item em estoque</label>
                            <select
                                class="form-select"
                                id="publish_item"
                                name="item_id"
                                required
                                data-publish-select
                                <?= $itens === [] ? 'disabled' : '' ?>
                            >
                                <option value="">Selecione um item disponível</option>
                                <?php foreach ($itens as $item): ?>
                                    <?php
                                    $itemId = (int) $item['id'];
                                    $meta = array_filter([
                                        $item['categoria_nome'] ?? null,
                                        $item['marca_nome'] ?? null,
                                        $item['escala_nome'] ?? null,
                                        !empty($item['ano_fabricacao']) ? 'Ano ' . $item['ano_fabricacao'] : null,
                                    ]);
                                    $imagemId = (int) ($item['imagem_id'] ?? 0);
                                    $itemCanais = array_map(
                                        static fn (array $pub): string => (string) $pub['slug'],
                                        $mapa[$itemId] ?? []
                                    );
                                    ?>
                                    <option
                                        value="<?= $itemId ?>"
                                        data-status="<?= e((string) $item['status']) ?>"
                                        data-nome="<?= e((string) $item['nome']) ?>"
                                        data-sku="<?= e((string) ($item['codigo'] ?? '')) ?>"
                                        data-descricao="<?= e((string) ($item['descricao'] ?? '')) ?>"
                                        data-meta="<?= e(implode(' · ', $meta)) ?>"
                                        data-custo="<?= e(number_format((float) $item['custo_unitario_real'], 3, '.', '')) ?>"
                                        data-quantidade="<?= (int) $item['quantidade'] ?>"
                                        data-disponivel="<?= max(0, (int) $item['quantidade'] - (int) ($item['comprometido'] ?? 0)) ?>"
                                        data-publicado="<?= array_sum(array_map(static fn (array $pub): int => (int) ($pub['quantidade'] ?? 0), $mapa[$itemId] ?? [])) ?>"
                                        data-canais="<?= e(implode(',', $itemCanais)) ?>"
                                        data-imagem="<?= $imagemId > 0 ? e((string) item_imagem_url($imagemId)) : '' ?>"
                                        <?= $itemId === $publicarId ? 'selected' : '' ?>
                                    ><?= e((string) $item['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label mb-0">Canais de publicação</label>
                            <span class="small text-muted">Disponível: <strong data-available-qty>-</strong></span>
                        </div>

                        <div class="publish-channels" data-publish-channels>
                            <?php foreach ($canaisModal as $canal): ?>
                                <?php $slug = (string) $canal['slug']; ?>
                                <label class="publish-channel" data-publish-channel-row data-channel-slug="<?= e($slug) ?>">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        name="canais[]"
                                        value="<?= e($slug) ?>"
                                        data-publish-channel
                                    >
                                    <span class="publish-channel-name">
                                        <i class="bi <?= $slug === 'telegram' ? 'bi-telegram' : 'bi-shop' ?>" aria-hidden="true"></i>
                                        <?= e((string) $canal['nome']) ?>
                                    </span>
                                    <input
                                        type="number"
                                        class="form-control form-control-sm publish-channel-qty"
                                        name="quantidades[<?= e($slug) ?>]"
                                        value="1"
                                        min="1"
                                        data-publish-channel-qty
                                        disabled
                                    >
                                    <span class="publish-channel-note small text-muted" data-publish-channel-note></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-text">
                            A soma das quantidades por canal não pode exceder o disponível. Canais já publicados ficam bloqueados.
                        </div>
                    </div>

                    <div class="publish-preview" data-publish-preview hidden>
                        <div class="publish-preview-media">
                            <img data-preview-image src="" alt="" hidden>
                            <i class="bi bi-image" data-preview-placeholder aria-hidden="true"></i>
                        </div>
                        <div class="publish-preview-body">
                            <span class="publish-preview-eyebrow" data-preview-canal>Anúncio</span>
                            <h4 class="publish-preview-title" data-preview-nome></h4>
                            <p class="publish-preview-meta" data-preview-meta></p>
                            <p class="publish-preview-desc" data-preview-descricao></p>
                            <p class="publish-preview-price" data-preview-preco></p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="publish_preco" class="form-label">Valor de venda</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="publish_preco"
                                    name="preco_venda"
                                    inputmode="decimal"
                                    placeholder="0,000"
                                    data-money
                                    data-publish-price
                                    required
                                >
                            </div>
                        </div>
                        <div class="col-12 col-md-3" data-reserve-field hidden>
                            <label for="publish_reserva" class="form-label">Valor de reserva</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="publish_reserva"
                                    name="preco_reserva"
                                    inputmode="decimal"
                                    placeholder="0,000"
                                    data-money
                                    data-publish-reserve
                                >
                            </div>
                            <div class="form-text">Pago no ato; o restante fica para a entrega.</div>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label">Referências</label>
                            <div class="d-flex flex-column gap-1">
                                <span class="small text-muted">
                                    Custo/un.: <strong data-cost-display>-</strong>
                                </span>
                                <span class="small text-muted">
                                    Margem: <strong data-margin-display>-</strong>
                                    <span data-margin-pct></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-brand" data-publish-submit <?= $itens === [] ? 'disabled' : '' ?>>
                        <i class="bi bi-send" aria-hidden="true"></i>
                        Publicar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
