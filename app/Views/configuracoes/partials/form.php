<?php

declare(strict_types=1);

$config = $config ?? ['label' => 'Registro', 'plural' => 'Registros'];
$item = $item ?? [];
$isEdit = $isEdit ?? false;
$action = $action ?? '';
?>
<div class="panel-card form-card">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title">
                <?= $isEdit ? 'Editar ' . e(mb_strtolower($config['label'])) : 'Novo ' . e(mb_strtolower($config['label'])) ?>
            </h2>
            <p class="panel-card-subtitle mb-0">
                Registros inativos continuam vinculados aos itens existentes, mas não devem ser usados em novos cadastros.
            </p>
        </div>
    </div>

    <form method="post" action="<?= e($action) ?>" class="config-form">
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-12 col-md-7">
                <label for="nome" class="form-label">Nome</label>
                <input
                    type="text"
                    class="form-control"
                    id="nome"
                    name="nome"
                    value="<?= e(old('nome', $item['nome'] ?? '')) ?>"
                    minlength="2"
                    maxlength="120"
                    required
                >
            </div>

            <div class="col-12 col-md-5">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="ativo" <?= old('status', $item['status'] ?? 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                    <option value="inativo" <?= old('status', $item['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>

            <div class="col-12">
                <label for="descricao" class="form-label">Descrição (opcional)</label>
                <textarea
                    class="form-control"
                    id="descricao"
                    name="descricao"
                    rows="3"
                    maxlength="255"
                ><?= e(old('descricao', $item['descricao'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= e(url('/admin/configuracoes/' . ($tipo ?? ''))) ?>" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-brand">
                <i class="bi bi-check-lg" aria-hidden="true"></i>
                Salvar
            </button>
        </div>
    </form>
</div>
