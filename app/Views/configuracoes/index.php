<?php

declare(strict_types=1);

$config = $config ?? ['label' => 'Registro', 'plural' => 'Registros'];
$items = $items ?? [];
?>
<?php require __DIR__ . '/partials/tabs.php'; ?>

<div class="toolbar">
    <div>
        <h2 class="h5 mb-1"><?= e($config['plural']) ?></h2>
        <p class="text-muted mb-0 small">Cadastros auxiliares reutilizados no formulário de produtos.</p>
    </div>
    <a href="<?= e(url('/admin/configuracoes/' . $tipo . '/criar')) ?>" class="btn btn-brand">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
        Novo <?= e(mb_strtolower($config['label'])) ?>
    </a>
</div>

<section class="panel-card">
    <div class="panel-card-head">
        <div>
            <h3 class="panel-card-title">Registros cadastrados</h3>
            <p class="panel-card-subtitle mb-0">A exclusão é bloqueada quando há itens vinculados.</p>
        </div>
        <span class="badge-soft"><?= count($items) ?> registro(s)</span>
    </div>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <i class="bi <?= e($config['icon']) ?>" aria-hidden="true"></i>
            <p class="mb-1">Nenhum registro cadastrado.</p>
            <span>Cadastre o primeiro <?= e(mb_strtolower($config['label'])) ?> para utilizá-lo nos produtos.</span>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Nome</th>
                        <th scope="col">Descrição</th>
                        <th scope="col">Status</th>
                        <th scope="col">Criado em</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $itemId = (int) $item['id'];
                        $isActive = $item['status'] === 'ativo';
                        ?>
                        <tr>
                            <td><strong><?= e((string) $item['nome']) ?></strong></td>
                            <td><?= e((string) ($item['descricao'] ?? '—')) ?></td>
                            <td>
                                <span class="status-pill <?= $isActive ? 'status-pill-active' : 'status-pill-inactive' ?>">
                                    <?= $isActive ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td><?= e(format_datetime($item['criado_em'] ?? null, 'd/m/Y')) ?></td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <a
                                        href="<?= e(url('/admin/configuracoes/' . $tipo . '/' . $itemId . '/editar')) ?>"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        Editar
                                    </a>

                                    <form method="post" action="<?= e(url('/admin/configuracoes/' . $tipo . '/' . $itemId . '/status')) ?>">
                                        <?= csrf_field() ?>
                                        <button
                                            type="submit"
                                            class="btn btn-sm <?= $isActive ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                            data-confirm="<?= e($isActive
                                                ? 'Inativar o registro ' . $item['nome'] . '?'
                                                : 'Ativar o registro ' . $item['nome'] . '?') ?>"
                                        >
                                            <?= $isActive ? 'Inativar' : 'Ativar' ?>
                                        </button>
                                    </form>

                                    <form method="post" action="<?= e(url('/admin/configuracoes/' . $tipo . '/' . $itemId . '/excluir')) ?>">
                                        <?= csrf_field() ?>
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            data-confirm="<?= e('Excluir o registro ' . $item['nome'] . '? A exclusão será bloqueada se houver itens vinculados.') ?>"
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
</section>
