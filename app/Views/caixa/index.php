<?php

declare(strict_types=1);

$registros = $registros ?? [];
$filters = $filters ?? ['categoria' => '', 'status' => '', 'busca' => '', 'mes' => 0, 'ano' => 0];
$categorias = $categorias ?? [];
$statusOpcoes = $statusOpcoes ?? [];
$meses = $meses ?? [];
$anos = $anos ?? [];
$totais = $totais ?? ['registros' => 0, 'total' => 0, 'pago' => 0, 'receber' => 0];

$statusClasse = static function (string $status): string {
    return match ($status) {
        'entregue' => 'status-pill status-pill-active',
        'pago' => 'status-pill status-pill-active',
        'pagamento_entrega' => 'status-pill status-pill-transit',
        'saldo_pendente' => 'status-pill status-pill-transit',
        'pendente' => 'status-pill status-pill-pre',
        'cancelado' => 'status-pill',
        'expirado' => 'status-pill',
        default => 'status-pill',
    };
};
?>
<section class="panel-card">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title">Caixa</h2>
            <p class="panel-card-subtitle mb-0">
                Todos os registros da loja, vendas diretas e canais, com filtros por categoria e status.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge-soft"><?= (int) $totais['registros'] ?> registro(s)</span>
            <span class="badge-soft">Total: <?= e(format_money($totais['total'])) ?></span>
            <span class="badge-soft">Pago: <?= e(format_money($totais['pago'])) ?></span>
            <span class="badge-soft">A receber: <?= e(format_money($totais['receber'])) ?></span>
        </div>
    </div>

    <form
        method="get"
        action="<?= e(url('/admin/caixa')) ?>"
        class="loja-entry-toolbar"
        style="padding: 0 1.35rem 0.85rem;"
    >
        <div class="row g-2 w-100">
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input
                        type="search"
                        class="form-control"
                        name="busca"
                        value="<?= e((string) $filters['busca']) ?>"
                        placeholder="Buscar por código, cliente ou item"
                        maxlength="180"
                    >
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select" name="categoria">
                    <option value="">Categoria</option>
                    <?php foreach ($categorias as $slug => $nome): ?>
                        <option value="<?= e((string) $slug) ?>" <?= (string) $filters['categoria'] === (string) $slug ? 'selected' : '' ?>>
                            <?= e((string) $nome) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select" name="status">
                    <option value="">Status</option>
                    <?php foreach ($statusOpcoes as $slug => $nome): ?>
                        <option value="<?= e((string) $slug) ?>" <?= (string) $filters['status'] === (string) $slug ? 'selected' : '' ?>>
                            <?= e((string) $nome) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-1">
                <select class="form-select" name="mes">
                    <option value="">Mês</option>
                    <?php foreach ($meses as $numero => $nome): ?>
                        <option value="<?= (int) $numero ?>" <?= (int) $filters['mes'] === (int) $numero ? 'selected' : '' ?>>
                            <?= e((string) $nome) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-1">
                <select class="form-select" name="ano">
                    <option value="">Ano</option>
                    <?php foreach ($anos as $ano): ?>
                        <option value="<?= (int) $ano ?>" <?= (int) $filters['ano'] === (int) $ano ? 'selected' : '' ?>>
                            <?= (int) $ano ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary flex-grow-1">Filtrar</button>
                <a class="btn btn-outline-secondary" href="<?= e(url('/admin/caixa')) ?>">Limpar</a>
            </div>
        </div>
    </form>

    <?php if ($registros === []): ?>
        <div class="empty-state">
            <i class="bi bi-safe" aria-hidden="true"></i>
            <p class="mb-1">Nenhum registro encontrado.</p>
            <span>Ajuste os filtros para visualizar outros registros.</span>
        </div>
    <?php else: ?>
        <div class="d-none d-lg-block table-responsive">
            <table class="table admin-table loja-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Registro</th>
                        <th scope="col">Categoria</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Itens</th>
                        <th scope="col">Total</th>
                        <th scope="col">Status</th>
                        <th scope="col">Data</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $registro): ?>
                        <?php
                        $titulo = $registro['tipo'] === 'pedido' ? 'Pedido #' . $registro['codigo'] : 'Registro ' . $registro['codigo'];
                        $info = $registro['categoria_label'] . ' • ' . $registro['status_label'] . ' • ' . format_datetime($registro['criado_em'] ?? null);
                        $itensJson = (string) json_encode($registro['detalhes'], JSON_UNESCAPED_UNICODE);
                        ?>
                        <tr data-filter-row>
                            <td>
                                <strong class="d-block"><?= e($titulo) ?></strong>
                                <?php if (!empty($registro['metodo_pagamento'])): ?>
                                    <span class="small text-muted d-block">Pagamento: <?= e((string) $registro['metodo_pagamento']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge-soft"><?= e($registro['categoria_label']) ?></span></td>
                            <td><?= e($registro['cliente']) ?></td>
                            <td><?= (int) $registro['itens'] ?></td>
                            <td>
                                <strong class="d-block"><?= e(format_money($registro['total'])) ?></strong>
                                <?php if (($registro['valor_pago'] ?? 0) > 0): ?>
                                    <span class="small text-muted d-block">Pago: <?= e(format_money($registro['valor_pago'])) ?></span>
                                <?php endif; ?>
                                <?php if (($registro['saldo'] ?? 0) > 0): ?>
                                    <span class="small text-danger d-block">A receber: <?= e(format_money($registro['saldo'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="<?= e($statusClasse($registro['status'])) ?>"><?= e($registro['status_label']) ?></span></td>
                            <td><?= e(format_datetime($registro['criado_em'] ?? null)) ?></td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#registroDetalhesModal"
                                    data-registro-titulo="<?= e($titulo) ?>"
                                    data-registro-subtitulo="<?= e($registro['cliente']) ?>"
                                    data-registro-info="<?= e($info) ?>"
                                    data-registro-itens="<?= e($itensJson) ?>"
                                >
                                    <i class="bi bi-list-ul" aria-hidden="true"></i>
                                    Ver detalhes
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-lg-none loja-entry-cards">
            <?php foreach ($registros as $registro): ?>
                <?php
                $titulo = $registro['tipo'] === 'pedido' ? 'Pedido #' . $registro['codigo'] : 'Registro ' . $registro['codigo'];
                $info = $registro['categoria_label'] . ' • ' . $registro['status_label'] . ' • ' . format_datetime($registro['criado_em'] ?? null);
                $itensJson = (string) json_encode($registro['detalhes'], JSON_UNESCAPED_UNICODE);
                ?>
                <article class="loja-entry-card">
                    <div class="loja-entry-card-top">
                        <div class="loja-entry-card-info">
                            <span class="loja-entry-card-title"><?= e($titulo) ?></span>
                            <span class="loja-entry-card-date"><?= e($registro['cliente']) ?></span>
                        </div>
                        <span class="<?= e($statusClasse($registro['status'])) ?>"><?= e($registro['status_label']) ?></span>
                    </div>

                    <div class="loja-entry-card-stats">
                        <div><span>Categoria</span><strong><?= e($registro['categoria_label']) ?></strong></div>
                        <div><span>Itens</span><strong><?= (int) $registro['itens'] ?></strong></div>
                        <div><span>Total</span><strong><?= e(format_money($registro['total'])) ?></strong></div>
                        <div><span>Pago</span><strong><?= e(format_money($registro['valor_pago'] ?? 0)) ?></strong></div>
                        <?php if (($registro['saldo'] ?? 0) > 0): ?>
                            <div><span>A receber</span><strong class="text-danger"><?= e(format_money($registro['saldo'])) ?></strong></div>
                        <?php endif; ?>
                    </div>

                    <div class="small text-muted"><?= e(format_datetime($registro['criado_em'] ?? null)) ?></div>

                    <div class="loja-entry-card-actions">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal"
                            data-bs-target="#registroDetalhesModal"
                            data-registro-titulo="<?= e($titulo) ?>"
                            data-registro-subtitulo="<?= e($registro['cliente']) ?>"
                            data-registro-info="<?= e($info) ?>"
                            data-registro-itens="<?= e($itensJson) ?>"
                        >
                            <i class="bi bi-list-ul" aria-hidden="true"></i>
                            Ver detalhes
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../loja/partials/registro-detalhes-modal.php'; ?>
