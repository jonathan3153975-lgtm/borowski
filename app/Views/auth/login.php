<?php

declare(strict_types=1);
?>
<form
    method="post"
    action="<?= e(url('/login')) ?>"
    class="auth-form"
    data-loading-form
>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="email" class="form-label">E-mail</label>
        <input
            type="email"
            class="form-control"
            id="email"
            name="email"
            value="<?= e(old('email', '')) ?>"
            autocomplete="username"
            inputmode="email"
            maxlength="180"
            required
            autofocus
        >
    </div>

    <div class="mb-4">
        <label for="password" class="form-label">Senha</label>
        <div class="input-group">
            <input
                type="password"
                class="form-control"
                id="password"
                name="password"
                autocomplete="current-password"
                maxlength="72"
                required
            >
            <button
                type="button"
                class="btn btn-outline-secondary password-toggle"
                data-password-toggle="password"
                aria-label="Mostrar senha"
                aria-pressed="false"
            >
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-brand w-100" data-loading-button>
        <span class="button-label">Entrar</span>
        <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
    </button>

    <p class="auth-note mt-4 mb-0">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        Acesso exclusivo para administradores autorizados.
    </p>
</form>
