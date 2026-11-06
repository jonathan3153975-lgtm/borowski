<?php

declare(strict_types=1);

$config = $config ?? ['label' => 'Registro', 'plural' => 'Registros'];
$isEdit = false;
$action = url('/admin/configuracoes/' . ($tipo ?? ''));
?>
<?php require __DIR__ . '/partials/tabs.php'; ?>
<?php require __DIR__ . '/partials/form.php'; ?>
