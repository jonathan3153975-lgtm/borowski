<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Throwable;

class AuditService
{
    public function log(string $action, ?string $description = null, ?int $userId = null): void
    {
        try {
            $statement = Database::connection()->prepare(
                'INSERT INTO logs_auditoria (usuario_id, acao, descricao, ip)
                 VALUES (:usuario_id, :acao, :descricao, :ip)'
            );

            $statement->execute([
                'usuario_id' => $userId,
                'acao' => $action,
                'descricao' => $description,
                'ip' => client_ip(),
            ]);
        } catch (Throwable $exception) {
            error_log('Falha ao registrar auditoria: ' . $exception->getMessage());
        }
    }
}
