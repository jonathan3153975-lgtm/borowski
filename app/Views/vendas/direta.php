<?php

declare(strict_types=1);

$itens = $itens ?? [];
$oldSelecionados = array_map('intval', (array) old('selecionados', []));
$oldQuantidades = (array) old('quantidades', []);
$oldPrecos = (array) old('precos', []);
?>
<section class="panel-card">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title">Venda direta</h2>
            <p class="panel-card-subtitle mb-0">
                Registre vendas presenciais: selecione os itens, confirme o pagamento e a entrega.
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/vendas')) ?>">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Voltar para vendas
        </a>
    </div>

    <?php if ($itens === []): ?>
        <div class="empty-state">
            <i class="bi bi-box-seam" aria-hidden="true"></i>
            <p class="mb-1">Nenhum item disponível para venda.</p>
            <span>Cadastre itens no estoque para iniciar uma venda direta.</span>
        </div>
    <?php else: ?>
        <form method="post" action="<?= e(url('/admin/vendas/direta')) ?>" data-venda-direta-form>
            <?= csrf_field() ?>

            <div class="p-3">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label for="vendaCliente" class="form-label">Cliente (opcional)</label>
                        <input
                            type="text"
                            class="form-control"
                            id="vendaCliente"
                            name="cliente_nome"
                            maxlength="180"
                            placeholder="Venda presencial"
                            value="<?= e((string) old('cliente_nome', '')) ?>"
                        >
                    </div>
                    <div class="col-6 col-md-4">
                        <label for="vendaMetodo" class="form-label">Forma de pagamento</label>
                        <select class="form-select" id="vendaMetodo" name="metodo_pagamento">
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">Pix</option>
                            <option value="cartao_credito">Cartão de crédito</option>
                            <option value="cartao_debito">Cartão de débito</option>
                            <option value="transferencia">Transferência</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label">Entrega</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="vendaEntregar" name="entregar_agora" value="1" checked>
                            <label class="form-check-label" for="vendaEntregar">Entregar ao cliente agora</label>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table admin-table loja-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 42px;"></th>
                                <th scope="col">Item</th>
                                <th scope="col">Disponível</th>
                                <th scope="col" style="width: 110px;">Qtd.</th>
                                <th scope="col" style="width: 150px;">Preço un.</th>
                                <th scope="col" class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                                <?php
                                $itemId = (int) $item['id'];
                                $selecionado = in_array($itemId, $oldSelecionados, true);
                                $quantidade = (int) ($oldQuantidades[$itemId] ?? 1);
                                $preco = $oldPrecos[$itemId] ?? format_money_input($item['preco_sugerido'] ?? null);
                                ?>
                                <tr>
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            name="selecionados[]"
                                            value="<?= $itemId ?>"
                                            data-venda-item-check
                                            <?= $selecionado ? 'checked' : '' ?>
                                        >
                                    </td>
                                    <td>
                                        <div class="loja-item-cell-info">
                                            <strong class="d-block"><?= e((string) $item['nome']) ?></strong>
                                            <?php if (!empty($item['codigo'])): ?>
                                                <span class="small text-muted d-block">SKU: <?= e((string) $item['codigo']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= (int) $item['disponivel'] ?></td>
                                    <td>
                                        <input
                                            type="number"
                                            class="form-control form-control-sm"
                                            name="quantidades[<?= $itemId ?>]"
                                            value="<?= max(1, $quantidade) ?>"
                                            min="1"
                                            max="<?= max(1, (int) $item['disponivel']) ?>"
                                            data-venda-item-qty
                                            <?= $selecionado ? '' : 'disabled' ?>
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            name="precos[<?= $itemId ?>]"
                                            value="<?= e((string) $preco) ?>"
                                            data-money
                                            data-venda-item-price
                                            <?= $selecionado ? '' : 'disabled' ?>
                                        >
                                    </td>
                                    <td class="text-end">
                                        <strong data-venda-item-subtotal>—</strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="fs-5">
                        Total:
                        <strong data-venda-total>R$ 0,000</strong>
                    </div>
                    <button type="button" class="btn btn-brand" data-venda-revisar>
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        Confirmar venda
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</section>

<div class="modal fade" id="vendaDiretaResumoModal" tabindex="-1" aria-labelledby="vendaDiretaResumoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title h5" id="vendaDiretaResumoModalLabel">Extrato da venda</h3>
                    <p class="modal-subtitle mb-0">Confira os dados antes de registrar a venda.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="vendaDiretaRecibo">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <strong>Venda direta</strong>
                    <span class="small text-muted" data-venda-resumo-data></span>
                </div>
                <p class="small text-muted mb-3">
                    Cliente: <strong data-venda-resumo-cliente></strong>
                    • Pagamento: <strong data-venda-resumo-metodo></strong>
                    • <strong data-venda-resumo-entrega></strong>
                </p>

                <div class="table-responsive">
                    <table class="table admin-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Item</th>
                                <th scope="col">Qtd.</th>
                                <th scope="col">Preço</th>
                                <th scope="col" class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody data-venda-resumo-itens></tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <span class="fs-5">Total: <strong data-venda-resumo-total>R$ 0,000</strong></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Voltar</button>
                <button type="button" class="btn btn-outline-secondary" data-venda-imprimir>
                    <i class="bi bi-printer" aria-hidden="true"></i>
                    Imprimir
                </button>
                <button type="button" class="btn btn-outline-secondary" data-venda-pdf>
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    Salvar PDF
                </button>
                <button type="button" class="btn btn-brand" data-venda-confirmar>
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    Confirmar venda
                </button>
            </div>
        </div>
    </div>
</div>
