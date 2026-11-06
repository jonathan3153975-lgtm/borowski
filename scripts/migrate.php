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
$dbName = (string) $config['database'];

if (preg_match('/^[A-Za-z0-9_]+$/', $dbName) !== 1) {
    fwrite(STDERR, "Nome de banco inválido no .env.\n");
    exit(1);
}

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

    try {
        $pdo->exec("USE `{$dbName}`");
    } catch (PDOException $exception) {
        $pdo->exec(
            "CREATE DATABASE IF NOT EXISTS `{$dbName}`
             CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $pdo->exec("USE `{$dbName}`");
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            migration VARCHAR(255) NOT NULL,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $applied = array_flip(array_map('strval', $applied));

    $migrations = glob(BASE_PATH . '/database/migrations/*.sql');

    if ($migrations === false || $migrations === []) {
        throw new RuntimeException('Nenhuma migration encontrada.');
    }

    sort($migrations, SORT_STRING);

    foreach ($migrations as $migration) {
        $name = basename($migration);

        if (isset($applied[$name])) {
            echo 'Ignorada (já aplicada): ' . $name . "\n";
            continue;
        }

        $sql = file_get_contents($migration);

        if ($sql === false) {
            throw new RuntimeException('Não foi possível ler a migration: ' . $name);
        }

        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }

        $statement = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
        $statement->execute(['migration' => $name]);

        echo 'Migration aplicada: ' . $name . "\n";
    }

    echo "Migrations verificadas com sucesso.\n";
    echo "Banco: {$config['database']}\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Falha ao executar as migrations.\n");
    fwrite(STDERR, "Detalhe: {$exception->getMessage()}\n");
    fwrite(STDERR, "Verifique as credenciais do .env e se o servidor MySQL está ativo.\n");
    exit(1);
}
