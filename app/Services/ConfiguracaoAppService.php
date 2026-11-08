<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class ConfiguracaoAppService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        $rows = $this->db->query('SELECT chave, valor FROM configuracoes_app')->fetchAll();
        $result = [];

        foreach ($rows as $row) {
            $result[(string) $row['chave']] = (string) ($row['valor'] ?? '');
        }

        return $result;
    }

    public function get(string $chave, string $default = ''): string
    {
        $statement = $this->db->prepare(
            'SELECT valor FROM configuracoes_app WHERE chave = :chave LIMIT 1'
        );
        $statement->execute(['chave' => $chave]);

        $value = $statement->fetchColumn();

        return $value === false ? $default : (string) $value;
    }

    public function set(string $chave, string $valor): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO configuracoes_app (chave, valor)
             VALUES (:chave, :valor)
             ON DUPLICATE KEY UPDATE valor = :valor_update'
        );

        $statement->execute([
            'chave' => $chave,
            'valor' => $valor,
            'valor_update' => $valor,
        ]);
    }

    public function setMany(array $pares): void
    {
        $this->db->beginTransaction();

        try {
            foreach ($pares as $chave => $valor) {
                $this->set((string) $chave, (string) $valor);
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }
}
