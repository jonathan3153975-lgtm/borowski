<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Core\Database;
use App\Models\Loja\Reserva;
use App\Services\AuditService;
use InvalidArgumentException;
use PDO;
use Throwable;

class EntregaService
{
    private PDO $db;
    private Reserva $reservas;
    private AuditService $audit;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->reservas = new Reserva();
        $this->audit = new AuditService();
    }

    public function clientePorTelegram(?int $telegramId): ?array
    {
        if ($telegramId === null || $telegramId <= 0) {
            return null;
        }

        $statement = $this->db->prepare(
            'SELECT * FROM loja_clientes_entrega WHERE telegram_user_id = :t ORDER BY id DESC LIMIT 1'
        );
        $statement->execute(['t' => $telegramId]);

        $registro = $statement->fetch();

        return $registro === false ? null : $registro;
    }

    /**
     * Cria uma entrega agrupando vendas confirmadas de um mesmo comprador.
     */
    public function criar(int $telegramId, array $reservaIds, array $dados, array $actor): int
    {
        $reservaIds = array_values(array_unique(array_filter(
            array_map('intval', $reservaIds),
            static fn (int $valor): bool => $valor > 0
        )));

        if ($reservaIds === []) {
            throw new InvalidArgumentException('Selecione ao menos uma venda para a entrega.');
        }

        $reservas = [];
        $comprador = null;

        foreach ($reservaIds as $id) {
            $reserva = $this->reservas->findReserva($id);

            if ($reserva === null) {
                throw new InvalidArgumentException('Venda não encontrada (#' . $id . ').');
            }

            if (!Reserva::pagamentoTotalConfirmado($reserva)) {
                throw new InvalidArgumentException('Somente vendas com pagamento total confirmado podem ser encaminhadas.');
            }

            if (!empty($reserva['entrega_id'])) {
                throw new InvalidArgumentException('Uma das vendas selecionadas já está em uma entrega.');
            }

            $compradorId = (int) ($reserva['telegram_user_id'] ?? 0);

            if ($comprador === null) {
                $comprador = $compradorId;
            } elseif ($comprador !== $compradorId) {
                throw new InvalidArgumentException('Selecione vendas do mesmo comprador.');
            }

            $reservas[] = $reserva;
        }

        if ($telegramId <= 0) {
            $telegramId = (int) $comprador;
        }

        $nome = trim((string) ($dados['nome'] ?? ''));

        if (mb_strlen($nome) < 3) {
            throw new InvalidArgumentException('Informe o nome do comprador.');
        }

        $cep = $this->digitos($dados['cep'] ?? '');

        if (strlen($cep) !== 8) {
            throw new InvalidArgumentException('Informe um CEP válido.');
        }

        $logradouro = trim((string) ($dados['logradouro'] ?? ''));
        $numero = trim((string) ($dados['numero'] ?? ''));
        $bairro = trim((string) ($dados['bairro'] ?? ''));
        $cidade = trim((string) ($dados['cidade'] ?? ''));
        $estado = strtoupper(trim((string) ($dados['estado'] ?? '')));

        if ($logradouro === '' || $numero === '' || $bairro === '' || $cidade === '' || $estado === '') {
            throw new InvalidArgumentException('Preencha o endereço completo (logradouro, número, bairro, cidade e estado).');
        }

        $clienteDados = [
            'telegram_user_id' => $telegramId > 0 ? $telegramId : null,
            'nome' => $nome,
            'cpf' => $this->digitos($dados['cpf'] ?? '') ?: null,
            'telefone' => $this->digitos($dados['telefone'] ?? '') ?: null,
            'cep' => $cep,
            'logradouro' => $logradouro,
            'numero' => $numero,
            'complemento' => trim((string) ($dados['complemento'] ?? '')) ?: null,
            'bairro' => $bairro,
            'cidade' => $cidade,
            'estado' => substr($estado, 0, 2),
        ];
        $observacoes = trim((string) ($dados['observacoes'] ?? '')) ?: null;
        $codigoRastreio = trim((string) ($dados['codigo_rastreio'] ?? '')) ?: null;

        if ($codigoRastreio !== null) {
            $codigoRastreio = mb_substr($codigoRastreio, 0, 120);
        }

        $this->db->beginTransaction();

        try {
            $clienteId = $this->salvarCliente($clienteDados);

            $statement = $this->db->prepare(
                'INSERT INTO loja_entregas (cliente_id, status, codigo_rastreio, observacoes, criado_por)
                 VALUES (:cliente, :status, :codigo_rastreio, :observacoes, :criado_por)'
            );
            $statement->execute([
                'cliente' => $clienteId,
                'status' => 'pendente',
                'codigo_rastreio' => $codigoRastreio,
                'observacoes' => $observacoes,
                'criado_por' => isset($actor['id']) ? (int) $actor['id'] : null,
            ]);
            $entregaId = (int) $this->db->lastInsertId();

            $itemStatement = $this->db->prepare(
                'INSERT INTO loja_entrega_itens (entrega_id, reserva_id, item_id, quantidade, preco_venda)
                 VALUES (:entrega, :reserva, :item, :quantidade, :preco)'
            );
            $reservaStatement = $this->db->prepare(
                'UPDATE loja_reservas SET entrega_id = :entrega WHERE id = :id'
            );

            foreach ($reservas as $reserva) {
                $itemStatement->execute([
                    'entrega' => $entregaId,
                    'reserva' => (int) $reserva['id'],
                    'item' => (int) $reserva['item_id'],
                    'quantidade' => (int) $reserva['quantidade'],
                    'preco' => $reserva['preco_venda'],
                ]);
                $reservaStatement->execute([
                    'entrega' => $entregaId,
                    'id' => (int) $reserva['id'],
                ]);
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }

        $this->audit->log(
            'loja_entrega_criada',
            'Entrega #' . $entregaId . ' criada para ' . $nome . ' com ' . count($reservas) . ' venda(s).',
            isset($actor['id']) ? (int) $actor['id'] : null
        );

        return $entregaId;
    }

    public function listar(int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));

        $statement = $this->db->prepare(
            "SELECT e.*, c.nome AS cliente_nome, c.telefone, c.cidade, c.estado,
                (SELECT COUNT(*) FROM loja_entrega_itens ei WHERE ei.entrega_id = e.id) AS itens_count,
                (SELECT COALESCE(SUM(ei.quantidade),0) FROM loja_entrega_itens ei
                    WHERE ei.entrega_id = e.id) AS unidades,
                (SELECT COALESCE(SUM(ei.quantidade * COALESCE(ei.preco_venda,0)),0)
                    FROM loja_entrega_itens ei WHERE ei.entrega_id = e.id) AS total
             FROM loja_entregas e
             INNER JOIN loja_clientes_entrega c ON c.id = e.cliente_id
             WHERE e.status <> 'finalizada'
             ORDER BY e.criado_em DESC, e.id DESC
             LIMIT :limite"
        );
        $statement->bindValue(':limite', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function find(int $entregaId): ?array
    {
        $statement = $this->db->prepare(
            "SELECT e.*, c.nome AS cliente_nome, c.telefone, c.cpf, c.cep, c.logradouro, c.numero,
                c.complemento, c.bairro, c.cidade, c.estado
             FROM loja_entregas e
             INNER JOIN loja_clientes_entrega c ON c.id = e.cliente_id
             WHERE e.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $entregaId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function atualizarStatus(int $entregaId, string $status): bool
    {
        if (!in_array($status, ['pendente', 'enviada', 'entregue', 'cancelada', 'finalizada'], true)) {
            throw new InvalidArgumentException('Status de entrega inválido.');
        }

        $statement = $this->db->prepare(
            'UPDATE loja_entregas SET status = :status WHERE id = :id'
        );

        return $statement->execute(['status' => $status, 'id' => $entregaId]);
    }

    public function itens(int $entregaId): array
    {
        $statement = $this->db->prepare(
            "SELECT ei.*, i.nome AS item_nome, i.codigo AS item_codigo
             FROM loja_entrega_itens ei
             INNER JOIN loja_itens i ON i.id = ei.item_id
             WHERE ei.entrega_id = :entrega
             ORDER BY ei.id ASC"
        );
        $statement->execute(['entrega' => $entregaId]);

        return $statement->fetchAll();
    }

    private function salvarCliente(array $dados): int
    {
        $existenteId = null;

        if ($dados['telegram_user_id'] !== null) {
            $statement = $this->db->prepare(
                'SELECT id FROM loja_clientes_entrega WHERE telegram_user_id = :t ORDER BY id DESC LIMIT 1'
            );
            $statement->execute(['t' => $dados['telegram_user_id']]);
            $id = $statement->fetchColumn();

            if ($id !== false) {
                $existenteId = (int) $id;
            }
        }

        if ($existenteId === null && $dados['cpf'] !== null) {
            $statement = $this->db->prepare(
                'SELECT id FROM loja_clientes_entrega WHERE cpf = :cpf ORDER BY id DESC LIMIT 1'
            );
            $statement->execute(['cpf' => $dados['cpf']]);
            $id = $statement->fetchColumn();

            if ($id !== false) {
                $existenteId = (int) $id;
            }
        }

        if ($existenteId !== null) {
            $statement = $this->db->prepare(
                'UPDATE loja_clientes_entrega
                 SET telegram_user_id = :telegram, nome = :nome, cpf = :cpf, telefone = :telefone,
                     cep = :cep, logradouro = :logradouro, numero = :numero, complemento = :complemento,
                     bairro = :bairro, cidade = :cidade, estado = :estado
                 WHERE id = :id'
            );
            $statement->execute([
                'telegram' => $dados['telegram_user_id'],
                'nome' => $dados['nome'],
                'cpf' => $dados['cpf'],
                'telefone' => $dados['telefone'],
                'cep' => $dados['cep'],
                'logradouro' => $dados['logradouro'],
                'numero' => $dados['numero'],
                'complemento' => $dados['complemento'],
                'bairro' => $dados['bairro'],
                'cidade' => $dados['cidade'],
                'estado' => $dados['estado'],
                'id' => $existenteId,
            ]);

            return $existenteId;
        }

        $statement = $this->db->prepare(
            'INSERT INTO loja_clientes_entrega
                (telegram_user_id, nome, cpf, telefone, cep, logradouro, numero, complemento, bairro, cidade, estado)
             VALUES
                (:telegram, :nome, :cpf, :telefone, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado)'
        );
        $statement->execute([
            'telegram' => $dados['telegram_user_id'],
            'nome' => $dados['nome'],
            'cpf' => $dados['cpf'],
            'telefone' => $dados['telefone'],
            'cep' => $dados['cep'],
            'logradouro' => $dados['logradouro'],
            'numero' => $dados['numero'],
            'complemento' => $dados['complemento'],
            'bairro' => $dados['bairro'],
            'cidade' => $dados['cidade'],
            'estado' => $dados['estado'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function digitos(mixed $valor): string
    {
        return preg_replace('/\D/', '', (string) $valor) ?? '';
    }
}
