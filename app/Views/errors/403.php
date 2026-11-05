<?php

declare(strict_types=1);
?>
<div class="alert alert-warning d-flex align-items-start gap-3" role="alert">
    <i class="bi bi-shield-lock fs-4" aria-hidden="true"></i>
    <div>
        <h2 class="h5 mb-1">Acesso negado</h2>
        <p class="mb-1">Esta área é restrita ao super-administrador.</p>
        <a href="<?= e(url('/admin')) ?>" class="alert-link">Voltar ao dashboard</a>
    </div>
</div>
