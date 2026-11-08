<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script deve ser executado via linha de comando.\n");
}

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

\Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();

date_default_timezone_set((string) config('app.timezone', 'America/Sao_Paulo'));

function prompt(string $label): string
{
    fwrite(STDOUT, $label);

    $value = fgets(STDIN);

    return $value === false ? '' : trim($value);
}

function promptHidden(string $label): string
{
    if (DIRECTORY_SEPARATOR === '\\') {
        return promptHiddenWindows($label);
    }

    fwrite(STDOUT, $label);
    shell_exec('stty -echo');
    $value = fgets(STDIN);
    shell_exec('stty echo');
    fwrite(STDOUT, PHP_EOL);

    return $value === false ? '' : rtrim($value, "\r\n");
}

function promptHiddenWindows(string $label): string
{
    $script = "\$secure = Read-Host -AsSecureString -Prompt '" . addslashes($label) . "'\n"
        . '$bstr = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)' . "\n"
        . '[System.Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr) | Write-Output';

    $encoded = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));
    $output = shell_exec('powershell -NoProfile -EncodedCommand ' . $encoded);

    return $output === false || $output === null ? '' : rtrim($output, "\r\n");
}

echo "=== Instalação do primeiro super-administrador ===\n\n";

try {
    $pdo = \App\Core\Database::connection();
    $pdo->query('SELECT 1 FROM usuarios LIMIT 1');
} catch (Throwable $exception) {
    fwrite(STDERR, "Não foi possível acessar as tabelas iniciais.\n");
    fwrite(STDERR, "Execute a migration e verifique o .env antes de continuar.\n");
    fwrite(STDERR, "Detalhe: {$exception->getMessage()}\n");
    exit(1);
}

$service = new \App\Services\UserService();

try {
    if ($service->hasSuperAdmin()) {
        fwrite(STDERR, "Já existe um super-administrador instalado. Operação interrompida.\n");
        exit(1);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, "Falha ao verificar a instalação atual: {$exception->getMessage()}\n");
    exit(1);
}

$options = getopt('', ['nome::', 'email::']);

$nome = $options['nome'] ?? (getenv('SUPER_ADMIN_NAME') ?: null);
$email = $options['email'] ?? (getenv('SUPER_ADMIN_EMAIL') ?: null);

$nome = $nome !== null && $nome !== '' ? (string) $nome : prompt('Nome: ');
$email = $email !== null && $email !== '' ? (string) $email : prompt('E-mail: ');

$senha = getenv('SUPER_ADMIN_PASSWORD');

if ($senha === false || $senha === '') {
    $senha = promptHidden('Senha: ');
    $confirmacao = promptHidden('Confirme a senha: ');

    if ($senha !== $confirmacao) {
        fwrite(STDERR, "As senhas informadas não coincidem.\n");
        exit(1);
    }
}

try {
    $id = $service->installSuperAdmin($nome, $email, $senha);

    echo "\nSuper-administrador criado com sucesso (ID {$id}).\n";
    echo "Acesse o painel em " . url('/login') . "\n";
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, "Erro: {$exception->getMessage()}\n");
    exit(1);
} catch (Throwable $exception) {
    fwrite(STDERR, "Erro inesperado: {$exception->getMessage()}\n");
    exit(1);
}
