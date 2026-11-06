<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Configuracao;
use InvalidArgumentException;

class ConfiguracaoService
{
    public const MAP = [
        'categorias' => [
            'table' => 'categorias',
            'label' => 'Categoria',
            'plural' => 'Categorias',
            'fk' => 'categoria_id',
            'icon' => 'bi-tags',
        ],
        'marcas' => [
            'table' => 'marcas',
            'label' => 'Marca',
            'plural' => 'Marcas',
            'fk' => 'marca_id',
            'icon' => 'bi-award',
        ],
        'fabricantes' => [
            'table' => 'fabricantes',
            'label' => 'Fabricante',
            'plural' => 'Fabricantes',
            'fk' => 'fabricante_id',
            'icon' => 'bi-building',
        ],
        'colecoes' => [
            'table' => 'colecoes',
            'label' => 'Coleção',
            'plural' => 'Coleções',
            'fk' => 'colecao_id',
            'icon' => 'bi-collection',
        ],
        'escalas' => [
            'table' => 'escalas',
            'label' => 'Escala',
            'plural' => 'Escalas',
            'fk' => 'escala_id',
            'icon' => 'bi-rulers',
        ],
    ];

    private Configuracao $model;
    private AuditService $audit;

    public function __construct()
    {
        $this->model = new Configuracao();
        $this->audit = new AuditService();
    }

    public function config(string $tipo): array
    {
        if (!isset(self::MAP[$tipo])) {
            throw new InvalidArgumentException('Tipo de configuração inválido.');
        }

        return self::MAP[$tipo];
    }

    public function tipos(): array
    {
        return array_keys(self::MAP);
    }

    public function opcoes(): array
    {
        return [
            'categorias' => $this->listar('categorias'),
            'marcas' => $this->listar('marcas'),
            'fabricantes' => $this->listar('fabricantes'),
            'colecoes' => $this->listar('colecoes'),
            'escalas' => $this->listar('escalas'),
        ];
    }

    public function listar(string $tipo): array
    {
        $config = $this->config($tipo);

        return $this->model->listar($config['table']);
    }

    public function find(string $tipo, int $id): array
    {
        $config = $this->config($tipo);
        $item = $this->model->buscar($config['table'], $id);

        if ($item === null) {
            throw new InvalidArgumentException($config['label'] . ' não encontrado(a).');
        }

        return $item;
    }

    public function criar(string $tipo, array $data, array $actor): int
    {
        $config = $this->config($tipo);
        $nome = $this->validarNome($data['nome'] ?? '');
        $descricao = $this->validarDescricao($data['descricao'] ?? '');
        $status = $this->validarStatus($data['status'] ?? 'ativo');

        if ($this->model->nomeExists($config['table'], $nome)) {
            throw new InvalidArgumentException('Já existe um registro com este nome.');
        }

        $id = $this->model->create($config['table'], [
            'nome' => $nome,
            'descricao' => $descricao,
            'status' => $status,
        ]);

        $this->audit->log(
            'configuracao_criada',
            "{$config['label']} criado(a): {$nome} (ID {$id}).",
            (int) ($actor['id'] ?? 0) ?: null
        );

        return $id;
    }

    public function atualizar(string $tipo, int $id, array $data, array $actor): void
    {
        $config = $this->config($tipo);
        $atual = $this->find($tipo, $id);
        $nome = $this->validarNome($data['nome'] ?? '');
        $descricao = $this->validarDescricao($data['descricao'] ?? '');
        $status = $this->validarStatus($data['status'] ?? 'ativo');

        if ($this->model->nomeExists($config['table'], $nome, $id)) {
            throw new InvalidArgumentException('Já existe um registro com este nome.');
        }

        $this->model->update($config['table'], $id, [
            'nome' => $nome,
            'descricao' => $descricao,
            'status' => $status,
        ]);

        $this->audit->log(
            'configuracao_atualizada',
            "{$config['label']} atualizado(a): {$atual['nome']} -> {$nome} (ID {$id}).",
            (int) ($actor['id'] ?? 0) ?: null
        );
    }

    public function alternarStatus(string $tipo, int $id, array $actor): string
    {
        $config = $this->config($tipo);
        $item = $this->find($tipo, $id);
        $novoStatus = $item['status'] === 'ativo' ? 'inativo' : 'ativo';

        $this->model->updateStatus($config['table'], $id, $novoStatus);

        $this->audit->log(
            'configuracao_status',
            "{$config['label']} {$item['nome']} (ID {$id}) alterado(a) para {$novoStatus}.",
            (int) ($actor['id'] ?? 0) ?: null
        );

        return $novoStatus;
    }

    public function excluir(string $tipo, int $id, array $actor): void
    {
        $config = $this->config($tipo);
        $item = $this->find($tipo, $id);

        if ($this->model->countItens($config['table'], $id, $config['fk']) > 0) {
            throw new InvalidArgumentException(
                'Existem itens vinculados a este registro. Inative-o em vez de excluir.'
            );
        }

        $this->model->delete($config['table'], $id);

        $this->audit->log(
            'configuracao_excluida',
            "{$config['label']} excluído(a): {$item['nome']} (ID {$id}).",
            (int) ($actor['id'] ?? 0) ?: null
        );
    }

    public function quickCriar(string $tipo, array $data, array $actor): array
    {
        $config = $this->config($tipo);
        $nome = $this->validarNome($data['nome'] ?? '');

        if ($this->model->nomeExists($config['table'], $nome)) {
            throw new InvalidArgumentException('Já existe um registro com este nome.');
        }

        $id = $this->model->create($config['table'], [
            'nome' => $nome,
            'descricao' => null,
            'status' => 'ativo',
        ]);

        $this->audit->log(
            'configuracao_criada',
            "{$config['label']} criado(a) rapidamente: {$nome} (ID {$id}).",
            (int) ($actor['id'] ?? 0) ?: null
        );

        return ['id' => $id, 'nome' => $nome];
    }

    private function validarNome(mixed $valor): string
    {
        $nome = trim((string) $valor);
        $length = mb_strlen($nome);

        if ($length < 2 || $length > 120) {
            throw new InvalidArgumentException('O nome deve ter entre 2 e 120 caracteres.');
        }

        return $nome;
    }

    private function validarDescricao(mixed $valor): ?string
    {
        $descricao = trim((string) $valor);

        if ($descricao === '') {
            return null;
        }

        if (mb_strlen($descricao) > 255) {
            throw new InvalidArgumentException('A descrição deve ter no máximo 255 caracteres.');
        }

        return $descricao;
    }

    private function validarStatus(mixed $valor): string
    {
        $status = (string) $valor;

        if (!in_array($status, ['ativo', 'inativo'], true)) {
            throw new InvalidArgumentException('Status inválido.');
        }

        return $status;
    }
}
