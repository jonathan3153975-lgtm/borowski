<?php

declare(strict_types=1);

$user = $user ?? [];
?>
<section class="panel-card form-card">
    <div class="panel-card-head">
        <div>
            <h3 class="panel-card-title">Editar administrador</h3>
            <p class="panel-card-subtitle mb-0">
                A senha atual nunca é exibida. Preencha o campo apenas se desejar definir uma nova senha.
            </p>
        </div>
        <span class="badge-soft">
            <?= ($user['status'] ?? '') === 'ativo' ? 'Ativo' : 'Inativo' ?>
        </span>
    </div>

    <form method="post" action="<?= e(url('/admin/usuarios/' . (int) ($user['id'] ?? 0) . '/editar')) ?>">
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label for="nome" class="form-label">Nome completo</label>
                <input
                    type="text"
                    class="form-control"
                    id="nome"
                    name="nome"
                    value="<?= e(old('nome', $user['nome'] ?? '')) ?>"
                    minlength="3"
                    maxlength="150"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="col-12 col-md-6">
                <label for="email" class="form-label">E-mail</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    value="<?= e(old('email', $user['email'] ?? '')) ?>"
                    maxlength="180"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="col-12 col-md-6">
                <label for="senha" class="form-label">Nova senha (opcional)</label>
                <div class="input-group">
                    <input
                        type="password"
                        class="form-control"
                        id="senha"
                        name="senha"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        placeholder="Deixe em branco para manter"
                    >
                    <button
                        type="button"
                        class="btn btn-outline-secondary password-toggle"
                        data-password-toggle="senha"
                        aria-label="Mostrar senha"
                        aria-pressed="false"
                    >
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="form-text">Mínimo de 8 caracteres, com pelo menos uma letra e um número.</div>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= e(url('/admin/usuarios')) ?>" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-brand">
                <i class="bi bi-check-lg" aria-hidden="true"></i>
                Salvar alterações
            </button>
        </div>
    </form>
</section>
