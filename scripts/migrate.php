<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script deve ser executado via linha de comando.\n");
}

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

\Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();

$config = config('database.connections.mysql');
$dsn = sprintf(
    'mysql:host=%s;port=%d;charset=%s',
    $config['host'],
    $config['port'],
    $config['charset']
);

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $migrations = glob(BASE_PATH . '/database/migrations/*.sql');

    if ($migrations === false || $migrations === []) {
        throw new RuntimeException('Nenhuma migration encontrada.');
    }

    sort($migrations, SORT_STRING);

    foreach ($migrations as $migration) {
        $sql = file_get_contents($migration);

        if ($sql === false) {
            throw new RuntimeException('Não foi possível ler a migration: ' . basename($migration));
        }

        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }

        echo 'Migration aplicada: ' . basename($migration) . "\n";
    }

    echo "Migrations executadas com sucesso.\n";
    echo "Banco: {$config['database']}\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Falha ao executar as migrations.\n");
    fwrite(STDERR, "Detalhe: {$exception->getMessage()}\n");
    fwrite(STDERR, "Verifique as credenciais do .env e se o servidor MySQL está ativo.\n");
    exit(1);
}
