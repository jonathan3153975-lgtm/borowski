<?php

declare(strict_types=1);

namespace App\Middleware;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!auth_check()) {
            flash('error', 'Faça login para acessar o painel administrativo.');
            redirect('/login');
        }
    }
}
