<?php

declare(strict_types=1);

$entregas = $entregas ?? [];
$total = count($entregas);
?>
<section class="panel-card">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title">Entregas</h2>
            <p class="panel-card-subtitle mb-0">
                Pacotes de entrega das vendas confirmadas. Visualize os itens, registre o encaminhamento e finalize.
            </p>
        </div>
        <span class="badge-soft"><?= (int) $total ?> entrega(s)</span>
    </div>

    <?php if ($entregas === []): ?>
        <div class="empty-state">
            <i class="bi bi-truck" aria-hidden="true"></i>
            <p class="mb-1">Nenhuma entrega criada.</p>
            <span>Crie entregas a partir da tela de Vendas.</span>
        </div>
    <?php else: ?>
        <div class="d-none d-lg-block table-responsive">
            <table class="table admin-table loja-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Entrega</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Itens</th>
                        <th scope="col">Total</th>
                        <th scope="col">Status</th>
                        <th scope="col">Criada em</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entregas as $entrega): ?>
                        <?php
                        $status = (string) $entrega['status'];
                        $statusClasse = match ($status) {
                            'enviada' => 'status-pill status-pill-pre',
                            'entregue' => 'status-pill status-pill-active',
                            'cancelada' => 'status-pill',
                            default => 'status-pill status-pill-transit',
                        };
                        $statusLabel = match ($status) {
                            'pendente' => 'Pendente',
                            'enviada' => 'Enviada',
                            'entregue' => 'Entregue',
                            'cancelada' => 'Cancelada',
                            default => ucfirst($status),
                        };
                        ?>
                        <tr>
                            <td><strong>#<?= (int) $entrega['id'] ?></strong></td>
                            <td>
                                <strong class="d-block"><?= e((string) $entrega['cliente_nome']) ?></strong>
                                <?php if (!empty($entrega['cidade'])): ?>
                                    <span class="small text-muted d-block">
                                        <?= e((string) $entrega['cidade']) ?><?= !empty($entrega['estado']) ? ' / ' . e((string) $entrega['estado']) : '' ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($entrega['telefone'])): ?>
                                    <span class="small text-muted d-block"><?= e((string) $entrega['telefone']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= (int) $entrega['itens_count'] ?> item(ns)
                                <span class="small text-muted d-block"><?= (int) $entrega['unidades'] ?> unidade(s)</span>
                            </td>
                            <td><strong><?= e(format_money($entrega['total'])) ?></strong></td>
                            <td>
                                <span class="<?= e($statusClasse) ?>"><?= e($statusLabel) ?></span>
                                <?php if (!empty($entrega['codigo_rastreio'])): ?>
                                    <span class="small text-muted d-block mt-1">Rastreio: <?= e((string) $entrega['codigo_rastreio']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(format_datetime($entrega['criado_em'] ?? null)) ?></td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-entrega-view="<?= (int) $entrega['id'] ?>"
                                        data-entrega-view-url="<?= e(url('/admin/entregas/' . (int) $entrega['id'] . '/itens')) ?>"
                                    >
                                        <i class="bi bi-box-seam" aria-hidden="true"></i>
                                        Ver itens
                                    </button>
                                    <?php if ($status === 'pendente'): ?>
                                        <form method="post" action="<?= e(url('/admin/entregas/' . (int) $entrega['id'] . '/encaminhar')) ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Registrar envio (ex.: correio)">
                                                <i class="bi bi-send" aria-hidden="true"></i>
                                                Encaminhar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(url('/admin/entregas/' . (int) $entrega['id'] . '/finalizar')) ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-brand" title="Concluir a entrega">
                                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                            Finalizar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-lg-none loja-entry-cards">
            <?php foreach ($entregas as $entrega): ?>
                <?php
                $status = (string) $entrega['status'];
                $statusClasse = match ($status) {
                    'enviada' => 'status-pill status-pill-pre',
                    'entregue' => 'status-pill status-pill-active',
                    'cancelada' => 'status-pill',
                    default => 'status-pill status-pill-transit',
                };
                $statusLabel = match ($status) {
                    'pendente' => 'Pendente',
                    'enviada' => 'Enviada',
                    'entregue' => 'Entregue',
                    'cancelada' => 'Cancelada',
                    default => ucfirst($status),
                };
                ?>
                <article class="loja-entry-card">
                    <div class="loja-entry-card-top">
                        <div class="loja-entry-card-info">
                            <span class="loja-entry-card-title">Entrega #<?= (int) $entrega['id'] ?></span>
                            <span class="loja-entry-card-date"><?= e((string) $entrega['cliente_nome']) ?></span>
                            <?php if (!empty($entrega['cidade'])): ?>
                                <span class="loja-entry-card-date">
                                    <?= e((string) $entrega['cidade']) ?><?= !empty($entrega['estado']) ? ' / ' . e((string) $entrega['estado']) : '' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="<?= e($statusClasse) ?>"><?= e($statusLabel) ?></span>
                    </div>

                    <div class="loja-entry-card-stats">
                        <div><span>Itens</span><strong><?= (int) $entrega['itens_count'] ?></strong></div>
                        <div><span>Unidades</span><strong><?= (int) $entrega['unidades'] ?></strong></div>
                        <div><span>Total</span><strong><?= e(format_money($entrega['total'])) ?></strong></div>
                    </div>

                    <div class="small text-muted">Criada em <?= e(format_datetime($entrega['criado_em'] ?? null)) ?></div>
                    <?php if (!empty($entrega['codigo_rastreio'])): ?>
                        <div class="small text-muted">Rastreio: <?= e((string) $entrega['codigo_rastreio']) ?></div>
                    <?php endif; ?>

                    <div class="loja-entry-card-actions">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            data-entrega-view="<?= (int) $entrega['id'] ?>"
                            data-entrega-view-url="<?= e(url('/admin/entregas/' . (int) $entrega['id'] . '/itens')) ?>"
                        >
                            <i class="bi bi-box-seam" aria-hidden="true"></i>
                            Ver itens
                        </button>
                        <?php if ($status === 'pendente'): ?>
                            <form method="post" action="<?= e(url('/admin/entregas/' . (int) $entrega['id'] . '/encaminhar')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-send" aria-hidden="true"></i>
                                    Encaminhar
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="<?= e(url('/admin/entregas/' . (int) $entrega['id'] . '/finalizar')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-brand">
                                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                Finalizar
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="entregaItensModal" tabindex="-1" aria-labelledby="entregaItensModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="entregaItensModalLabel">Itens da entrega</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="entregaItensInfo" class="mb-3"></div>
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
                        <tbody id="entregaItensBody">
                            <tr><td colspan="4" class="text-muted">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
