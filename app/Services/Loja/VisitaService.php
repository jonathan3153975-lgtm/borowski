<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Core\Database;
use PDO;
use Throwable;

class VisitaService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function registrar(int $itemId, ?int $publicacaoId, string $origem, ?int $telegramUserId = null): void
    {
        if ($itemId <= 0) {
            return;
        }

        if (!in_array($origem, ['loja', 'telegram', 'oferta'], true)) {
            $origem = 'loja';
        }

        try {
            $statement = $this->db->prepare(
                'INSERT INTO loja_item_visitas
                    (item_id, publicacao_id, origem, telegram_user_id, ip, user_agent)
                 VALUES
                    (:item, :publicacao, :origem, :telegram, :ip, :user_agent)'
            );

            $statement->execute([
                'item' => $itemId,
                'publicacao' => $publicacaoId,
                'origem' => $origem,
                'telegram' => $telegramUserId,
                'ip' => client_ip(),
                'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (Throwable $exception) {
            error_log('[visitas] ' . $exception->getMessage());
        }
    }
}
