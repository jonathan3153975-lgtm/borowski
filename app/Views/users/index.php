<?php

declare(strict_types=1);

$users = $users ?? [];
$search = $search ?? '';
?>
<div class="toolbar">
    <form method="get" action="<?= e(url('/admin/usuarios')) ?>" class="toolbar-search" role="search">
        <label for="busca" class="visually-hidden">Pesquisar administradores</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
            <input
                type="search"
                class="form-control"
                id="busca"
                name="busca"
                value="<?= e($search) ?>"
                placeholder="Pesquisar por nome ou e-mail"
                maxlength="180"
            >
            <button type="submit" class="btn btn-outline-secondary">Pesquisar</button>
            <?php if ($search !== ''): ?>
                <a href="<?= e(url('/admin/usuarios')) ?>" class="btn btn-outline-secondary">Limpar</a>
            <?php endif; ?>
        </div>
    </form>

    <a href="<?= e(url('/admin/usuarios/criar')) ?>" class="btn btn-brand">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
        Novo administrador
    </a>
</div>

<section class="panel-card">
    <div class="panel-card-head">
        <div>
            <h3 class="panel-card-title">Administradores cadastrados</h3>
            <p class="panel-card-subtitle mb-0">
                O super-administrador é protegido e não pode ser editado, inativado ou excluído por esta tela.
            </p>
        </div>
        <span class="badge-soft"><?= count($users) ?> registro(s)</span>
    </div>

    <?php if ($users === []): ?>
        <div class="empty-state">
            <i class="bi bi-people" aria-hidden="true"></i>
            <p class="mb-1">Nenhum administrador encontrado</p>
            <span>
                <?= $search !== ''
                    ? 'Ajuste a pesquisa ou cadastre um novo administrador.'
                    : 'Cadastre o primeiro administrador para começar.' ?>
            </span>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Administrador</th>
                        <th scope="col">Perfil</th>
                        <th scope="col">Status</th>
                        <th scope="col">Último acesso</th>
                        <th scope="col">Cadastro</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $item): ?>
                        <?php
                        $isProtected = $item['tipo'] !== 'admin';
                        $isActive = $item['status'] === 'ativo';
                        $itemId = (int) $item['id'];
                        ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <span class="admin-avatar admin-avatar-sm" aria-hidden="true">
                                        <?= e(mb_strtoupper(mb_substr((string) $item['nome'], 0, 1))) ?>
                                    </span>
                                    <span>
                                        <strong class="d-block"><?= e((string) $item['nome']) ?></strong>
                                        <span class="text-muted small"><?= e((string) $item['email']) ?></span>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php if ($isProtected): ?>
                                    <span class="badge-soft badge-soft-yellow">Superadministrador</span>
                                <?php else: ?>
                                    <span class="badge-soft">Administrador</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-pill <?= $isActive ? 'status-pill-active' : 'status-pill-inactive' ?>">
                                    <?= $isActive ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td><?= e(format_datetime($item['ultimo_acesso'] ?? null)) ?></td>
                            <td><?= e(format_datetime($item['criado_em'] ?? null, 'd/m/Y')) ?></td>
                            <td class="text-end">
                                <?php if ($isProtected): ?>
                                    <span class="text-muted small" title="Conta protegida">
                                        <i class="bi bi-lock" aria-hidden="true"></i> Protegido
                                    </span>
                                <?php else: ?>
                                    <div class="table-actions">
                                        <a
                                            href="<?= e(url('/admin/usuarios/' . $itemId . '/editar')) ?>"
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                            Editar
                                        </a>

                                        <form method="post" action="<?= e(url('/admin/usuarios/' . $itemId . '/status')) ?>">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="btn btn-sm <?= $isActive ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                                data-confirm="<?= e($isActive
                                                    ? 'Inativar o administrador ' . $item['nome'] . '?'
                                                    : 'Ativar o administrador ' . $item['nome'] . '?') ?>"
                                            >
                                                <i class="bi <?= $isActive ? 'bi-pause-circle' : 'bi-check-circle' ?>" aria-hidden="true"></i>
                                                <?= $isActive ? 'Inativar' : 'Ativar' ?>
                                            </button>
                                        </form>

                                        <form method="post" action="<?= e(url('/admin/usuarios/' . $itemId . '/excluir')) ?>">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                                data-confirm="<?= e('Excluir o administrador ' . $item['nome'] . '? Esta ação não pode ser desfeita.') ?>"
                                            >
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
