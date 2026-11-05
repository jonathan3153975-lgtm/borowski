<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'admin'): void
    {
        echo View::render($view, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        redirect($path);
    }

    protected function requireSuperAdmin(): void
    {
        if (is_super_admin()) {
            return;
        }

        http_response_code(403);
        echo View::render('errors/403', [], 'admin');
        exit;
    }

    protected function currentUser(): ?array
    {
        return auth_user();
    }
}
