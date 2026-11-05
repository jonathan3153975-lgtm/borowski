<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\UserService;
use InvalidArgumentException;

class UserController extends Controller
{
    public function index(): void
    {
        $this->requireSuperAdmin();

        $search = trim((string) ($_GET['busca'] ?? ''));
        $users = (new UserService())->list($search);

        $this->view('users.index', [
            'title' => 'Administradores',
            'breadcrumb' => [['label' => 'Administradores', 'url' => null]],
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function create(): void
    {
        $this->requireSuperAdmin();

        $this->view('users.create', [
            'title' => 'Novo administrador',
            'breadcrumb' => [
                ['label' => 'Administradores', 'url' => '/admin/usuarios'],
                ['label' => 'Novo', 'url' => null],
            ],
        ]);
    }

    public function store(): void
    {
        $this->requireSuperAdmin();

        try {
            (new UserService())->createAdmin($_POST, (array) auth_user());
            flash('success', 'Administrador cadastrado com sucesso.');
            redirect('/admin/usuarios');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            flash_input([
                'nome' => trim((string) ($_POST['nome'] ?? '')),
                'email' => trim((string) ($_POST['email'] ?? '')),
            ]);
            redirect('/admin/usuarios/criar');
        }
    }

    public function edit(string $id): void
    {
        $this->requireSuperAdmin();

        try {
            $user = (new UserService())->find((int) $id);
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            redirect('/admin/usuarios');
        }

        if ($user['tipo'] !== 'admin') {
            flash('error', 'O super-administrador não pode ser alterado por esta operação.');
            redirect('/admin/usuarios');
        }

        $this->view('users.edit', [
            'title' => 'Editar administrador',
            'breadcrumb' => [
                ['label' => 'Administradores', 'url' => '/admin/usuarios'],
                ['label' => 'Editar', 'url' => null],
            ],
            'user' => $user,
        ]);
    }

    public function update(string $id): void
    {
        $this->requireSuperAdmin();

        try {
            (new UserService())->updateAdmin((int) $id, $_POST, (array) auth_user());
            flash('success', 'Administrador atualizado com sucesso.');
            redirect('/admin/usuarios');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            flash_input([
                'nome' => trim((string) ($_POST['nome'] ?? '')),
                'email' => trim((string) ($_POST['email'] ?? '')),
            ]);
            redirect('/admin/usuarios/' . (int) $id . '/editar');
        }
    }

    public function status(string $id): void
    {
        $this->requireSuperAdmin();

        try {
            $status = (new UserService())->toggleStatus((int) $id, (array) auth_user());
            $message = $status === 'ativo'
                ? 'Administrador ativado com sucesso.'
                : 'Administrador inativado com sucesso.';

            flash('success', $message);
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        }

        redirect('/admin/usuarios');
    }

    public function destroy(string $id): void
    {
        $this->requireSuperAdmin();

        try {
            (new UserService())->deleteAdmin((int) $id, (array) auth_user());
            flash('success', 'Administrador excluído com sucesso.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        }

        redirect('/admin/usuarios');
    }
}
