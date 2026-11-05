<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class View
{
    private static string $viewsPath;

    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $viewPath = self::resolve($view);

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $content = (string) ob_get_clean();

        if ($layout === null) {
            return $content;
        }

        $layoutPath = self::resolve('layouts.' . $layout);

        ob_start();
        require $layoutPath;

        return (string) ob_get_clean();
    }

    private static function resolve(string $view): string
    {
        $relative = str_replace('.', '/', $view);

        if ($relative === '' || preg_match('/^[a-zA-Z0-9_\/-]+$/', $relative) !== 1) {
            throw new RuntimeException('Nome de view inválido.');
        }

        $base = self::basePath();
        $path = realpath($base . '/' . $relative . '.php');

        if ($path === false || !str_starts_with($path, realpath($base) . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('View não encontrada: ' . $view);
        }

        return $path;
    }

    private static function basePath(): string
    {
        if (!isset(self::$viewsPath)) {
            self::$viewsPath = BASE_PATH . '/app/Views';
        }

        return self::$viewsPath;
    }
}
