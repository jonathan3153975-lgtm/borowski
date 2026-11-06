<?php

declare(strict_types=1);

$activeType = $tipo ?? '';
$map = $map ?? [];
$tipos = $tipos ?? [];
?>
<nav class="config-tabs" aria-label="Tipos de configuração">
    <ul class="nav nav-pills">
        <?php foreach ($tipos as $itemTipo): ?>
            <?php $itemConfig = $map[$itemTipo] ?? null; ?>
            <?php if ($itemConfig === null) { continue; } ?>
            <li class="nav-item">
                <a
                    class="nav-link<?= $activeType === $itemTipo ? ' active' : '' ?>"
                    href="<?= e(url('/admin/configuracoes/' . $itemTipo)) ?>"
                    <?= $activeType === $itemTipo ? 'aria-current="page"' : '' ?>
                >
                    <i class="bi <?= e($itemConfig['icon']) ?>" aria-hidden="true"></i>
                    <?= e($itemConfig['plural']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
