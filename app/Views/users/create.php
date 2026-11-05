<?php

declare(strict_types=1);
?>
<section class="panel-card form-card">
    <div class="panel-card-head">
        <div>
            <h3 class="panel-card-title">Dados do administrador</h3>
            <p class="panel-card-subtitle mb-0">
                O novo usuário será cadastrado com o perfil Administrador e status Ativo.
            </p>
        </div>
    </div>

    <form method="post" action="<?= e(url('/admin/usuarios')) ?>">
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label for="nome" class="form-label">Nome completo</label>
                <input
                    type="text"
                    class="form-control"
                    id="nome"
                    name="nome"
                    value="<?= e(old('nome', '')) ?>"
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
                    value="<?= e(old('email', '')) ?>"
                    maxlength="180"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="col-12 col-md-6">
                <label for="senha" class="form-label">Senha</label>
                <div class="input-group">
                    <input
                        type="password"
                        class="form-control"
                        id="senha"
                        name="senha"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        required
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
                Salvar administrador
            </button>
        </div>
    </form>
</section>
