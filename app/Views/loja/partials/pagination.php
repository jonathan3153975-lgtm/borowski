<?php

declare(strict_types=1);

$pages = max(1, (int) ($pages ?? 1));
$page = min(max(1, (int) ($page ?? 1)), $pages);
$total = (int) ($total ?? 0);
$basePath = $basePath ?? '/';
$query = $query ?? [];

if ($pages <= 1) {
    return;
}

$makeUrl = static function (int $target) use ($basePath, $query): string {
    return url($basePath . '?' . http_build_query(array_merge($query, ['pagina' => $target])));
};

$start = max(1, $page - 2);
$end = min($pages, $page + 2);
?>
<nav class="loja-pagination" aria-label="Paginação">
    <ul class="pagination mb-0">
        <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e($page <= 1 ? '#' : $makeUrl($page - 1)) ?>" aria-label="Página anterior">
                Anterior
            </a>
        </li>

        <?php for ($p = $start; $p <= $end; $p++): ?>
            <li class="page-item<?= $p === $page ? ' active' : '' ?>">
                <a class="page-link" href="<?= e($makeUrl($p)) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>

        <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e($page >= $pages ? '#' : $makeUrl($page + 1)) ?>" aria-label="Próxima página">
                Próxima
            </a>
        </li>
    </ul>
    <span class="loja-pagination-info">
        Página <?= $page ?> de <?= $pages ?> · <?= $total ?> registro(s)
    </span>
</nav>
