<?php

declare(strict_types=1);

$activeTab = $activeTab ?? 'entradas';
?>
<nav class="loja-tabs" aria-label="Seções do módulo Loja">
    <ul class="nav nav-pills">
        <li class="nav-item">
            <a
                class="nav-link<?= $activeTab === 'entradas' ? ' active' : '' ?>"
                href="<?= e(url('/admin/loja/entradas')) ?>"
                <?= $activeTab === 'entradas' ? 'aria-current="page"' : '' ?>
            >
                <i class="bi bi-box-seam" aria-hidden="true"></i>
                Entrada de produtos
            </a>
        </li>
        <li class="nav-item">
            <a
                class="nav-link<?= $activeTab === 'estoque' ? ' active' : '' ?>"
                href="<?= e(url('/admin/loja/estoque')) ?>"
                <?= $activeTab === 'estoque' ? 'aria-current="page"' : '' ?>
            >
                <i class="bi bi-boxes" aria-hidden="true"></i>
                Estoque
            </a>
        </li>
    </ul>
</nav>
