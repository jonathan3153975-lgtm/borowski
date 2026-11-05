<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuditService;
use App\Services\UserService;
use InvalidArgumentException;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 900;
    private const LOCK_SECONDS = 900;

    public function showLogin(): void
    {
        $this->view('auth.login', ['title' => 'Entrar'], 'auth');
    }

    public function login(): void
    {
        $remaining = $this->lockRemaining();

        if ($remaining > 0) {
            $minutes = (int) ceil($remaining / 60);
            flash('error', "Muitas tentativas de login. Aguarde {$minutes} minuto(s) e tente novamente.");
            redirect('/login');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->registerFailure();
            flash('error', 'Informe o e-mail e a senha.');
            flash_input(['email' => $email]);
            redirect('/login');
        }

        try {
            (new UserService())->authenticate($email, $password);
            unset($_SESSION['login_throttle']);
            redirect('/admin');
        } catch (InvalidArgumentException $exception) {
            $this->registerFailure();
            flash('error', $exception->getMessage());
            flash_input(['email' => $email]);
            redirect('/login');
        }
    }

    public function logout(): void
    {
        $user = auth_user();

        if ($user !== null) {
            (new AuditService())->log('logout', 'Logout realizado.', (int) $user['id']);
        }

        logout_user();
        session_start();
        flash('success', 'Sessão encerrada com sucesso.');
        redirect('/login');
    }

    private function lockRemaining(): int
    {
        $state = $this->throttleState();

        if ($state['locked_until'] <= time()) {
            return 0;
        }

        return $state['locked_until'] - time();
    }

    private function registerFailure(): void
    {
        $state = $this->throttleState();
        $now = time();

        if ($now - $state['first_attempt'] > self::WINDOW_SECONDS) {
            $state = ['attempts' => 0, 'first_attempt' => $now, 'locked_until' => 0];
        }

        $state['attempts']++;

        if ($state['attempts'] >= self::MAX_ATTEMPTS) {
            $state['locked_until'] = $now + self::LOCK_SECONDS;
            (new AuditService())->log(
                'login_bloqueado',
                'Limite de tentativas de login excedido; acesso temporariamente bloqueado.'
            );
        }

        $_SESSION['login_throttle'] = $state;
    }

    private function throttleState(): array
    {
        $state = $_SESSION['login_throttle'] ?? [];

        return [
            'attempts' => (int) ($state['attempts'] ?? 0),
            'first_attempt' => (int) ($state['first_attempt'] ?? time()),
            'locked_until' => (int) ($state['locked_until'] ?? 0),
        ];
    }
}
