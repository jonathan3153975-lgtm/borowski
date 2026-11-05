<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use InvalidArgumentException;

class UserService
{
    private const MIN_PASSWORD_LENGTH = 8;
    private const MAX_PASSWORD_LENGTH = 72;
    private const DUMMY_HASH = '$2y$12$4fbwSc2zC1iFdQ8sYrtv3ewTjRhBFp/bdW.bZ7H237EfjQ4/MUp9y';

    private User $users;
    private AuditService $audit;

    public function __construct()
    {
        $this->users = new User();
        $this->audit = new AuditService();
    }

    public function list(string $search = ''): array
    {
        return $this->users->search(trim($search));
    }

    public function find(int $id): array
    {
        $user = $this->users->find($id);

        if ($user === null) {
            throw new InvalidArgumentException('Administrador não encontrado.');
        }

        return $user;
    }

    public function hasSuperAdmin(): bool
    {
        return $this->users->countSuperAdmins() > 0;
    }

    public function createAdmin(array $data, array $actor): int
    {
        $this->assertSuperAdmin($actor);

        $nome = $this->validateName($data['nome'] ?? '');
        $email = $this->validateEmail($data['email'] ?? '');
        $senha = $this->validatePassword($data['senha'] ?? '');

        if ($this->users->emailExists($email)) {
            throw new InvalidArgumentException('Já existe um usuário cadastrado com este e-mail.');
        }

        $id = $this->users->create([
            'nome' => $nome,
            'email' => $email,
            'senha' => password_hash($senha, PASSWORD_DEFAULT),
            'tipo' => 'admin',
            'status' => 'ativo',
        ]);

        $this->audit->log(
            'admin_criado',
            "Administrador criado: {$nome} <{$email}> (ID {$id}).",
            (int) $actor['id']
        );

        return $id;
    }

    public function updateAdmin(int $id, array $data, array $actor): void
    {
        $this->assertSuperAdmin($actor);

        $target = $this->find($id);

        if ($target['tipo'] !== 'admin') {
            throw new InvalidArgumentException('O super-administrador não pode ser alterado por esta operação.');
        }

        $nome = $this->validateName($data['nome'] ?? '');
        $email = $this->validateEmail($data['email'] ?? '');

        if ($this->users->emailExists($email, $id)) {
            throw new InvalidArgumentException('Já existe um usuário cadastrado com este e-mail.');
        }

        $fields = ['nome' => $nome, 'email' => $email];

        if (trim((string) ($data['senha'] ?? '')) !== '') {
            $fields['senha'] = password_hash(
                $this->validatePassword($data['senha']),
                PASSWORD_DEFAULT
            );
        }

        $this->users->update($id, $fields);

        $this->audit->log(
            'admin_editado',
            "Administrador atualizado: {$nome} <{$email}> (ID {$id}).",
            (int) $actor['id']
        );
    }

    public function toggleStatus(int $id, array $actor): string
    {
        $this->assertSuperAdmin($actor);

        $target = $this->find($id);

        if ($target['tipo'] !== 'admin') {
            throw new InvalidArgumentException('O super-administrador não pode ser manipulado por esta operação.');
        }

        $newStatus = $target['status'] === 'ativo' ? 'inativo' : 'ativo';
        $this->users->updateStatus($id, $newStatus);

        $action = $newStatus === 'ativo' ? 'admin_ativado' : 'admin_inativado';

        $this->audit->log(
            $action,
            "Administrador {$target['email']} (ID {$id}) alterado para {$newStatus}.",
            (int) $actor['id']
        );

        return $newStatus;
    }

    public function deleteAdmin(int $id, array $actor): void
    {
        $this->assertSuperAdmin($actor);

        if ((int) $actor['id'] === $id) {
            throw new InvalidArgumentException('O próprio usuário autenticado não pode ser excluído.');
        }

        $target = $this->find($id);

        if ($target['tipo'] !== 'admin') {
            throw new InvalidArgumentException('O super-administrador não pode ser excluído por esta operação.');
        }

        $this->users->delete($id);

        $this->audit->log(
            'admin_excluido',
            "Administrador excluído: {$target['nome']} <{$target['email']}> (ID {$id}).",
            (int) $actor['id']
        );
    }

    public function authenticate(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));
        $user = $this->users->findByEmail($email);
        $hash = $user['senha'] ?? self::DUMMY_HASH;
        $passwordMatches = password_verify($password, $hash);

        if ($user === null || !$passwordMatches || $user['status'] !== 'ativo') {
            $this->audit->log(
                'login_falha',
                'Tentativa de login inválida.',
                $user === null ? null : (int) $user['id']
            );

            throw new InvalidArgumentException('E-mail ou senha inválidos.');
        }

        $this->users->updateLastAccess((int) $user['id']);
        $this->audit->log('login_sucesso', 'Login realizado com sucesso.', (int) $user['id']);
        login_user($user);

        return $user;
    }

    public function installSuperAdmin(string $nome, string $email, string $senha): int
    {
        if ($this->hasSuperAdmin()) {
            throw new InvalidArgumentException('Já existe um super-administrador instalado.');
        }

        $nome = $this->validateName($nome);
        $email = $this->validateEmail($email);
        $senha = $this->validatePassword($senha);

        if ($this->users->emailExists($email)) {
            throw new InvalidArgumentException('Já existe um usuário cadastrado com este e-mail.');
        }

        $id = $this->users->create([
            'nome' => $nome,
            'email' => $email,
            'senha' => password_hash($senha, PASSWORD_DEFAULT),
            'tipo' => 'super_admin',
            'status' => 'ativo',
        ]);

        $this->audit->log(
            'instalacao_super_admin',
            "Super-administrador instalado: {$nome} <{$email}> (ID {$id}).",
            $id
        );

        return $id;
    }

    private function assertSuperAdmin(array $actor): void
    {
        if (($actor['tipo'] ?? null) !== 'super_admin') {
            throw new InvalidArgumentException('Acesso restrito ao super-administrador.');
        }
    }

    private function validateName(mixed $value): string
    {
        $name = trim((string) $value);
        $length = mb_strlen($name);

        if ($length < 3 || $length > 150) {
            throw new InvalidArgumentException('O nome deve ter entre 3 e 150 caracteres.');
        }

        return $name;
    }

    private function validateEmail(mixed $value): string
    {
        $email = mb_strtolower(trim((string) $value));

        if (mb_strlen($email) > 180 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Informe um e-mail válido.');
        }

        return $email;
    }

    private function validatePassword(mixed $value): string
    {
        $password = (string) $value;
        $length = mb_strlen($password);

        if ($length < self::MIN_PASSWORD_LENGTH || $length > self::MAX_PASSWORD_LENGTH) {
            throw new InvalidArgumentException(
                'A senha deve ter entre ' . self::MIN_PASSWORD_LENGTH . ' e ' . self::MAX_PASSWORD_LENGTH . ' caracteres.'
            );
        }

        if (preg_match('/[A-Za-z]/', $password) !== 1 || preg_match('/\d/', $password) !== 1) {
            throw new InvalidArgumentException('A senha deve conter pelo menos uma letra e um número.');
        }

        return $password;
    }

}
