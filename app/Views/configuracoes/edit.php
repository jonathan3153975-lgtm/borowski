<?php

declare(strict_types=1);

$config = $config ?? ['label' => 'Registro', 'plural' => 'Registros'];
$item = $item ?? [];
$isEdit = true;
$action = url('/admin/configuracoes/' . ($tipo ?? '') . '/' . (int) ($item['id'] ?? 0));
?>
<?php require __DIR__ . '/partials/tabs.php'; ?>
<?php require __DIR__ . '/partials/form.php'; ?>
