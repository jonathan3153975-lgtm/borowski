<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ConfiguracaoService;
use InvalidArgumentException;

class ConfiguracaoController extends Controller
{
    private ConfiguracaoService $configuracoes;

    public function __construct()
    {
        $this->configuracoes = new ConfiguracaoService();
    }

    public function index(string $tipo): void
    {
        try {
            $config = $this->configuracoes->config($tipo);
            $items = $this->configuracoes->listar($tipo);
        } catch (InvalidArgumentException $exception) {
            http_response_code(404);
            echo $exception->getMessage();
            exit;
        }

        $this->view('configuracoes.index', array_merge([
            'title' => $config['plural'],
            'breadcrumb' => [
                ['label' => 'Configurações', 'url' => null],
                ['label' => $config['plural'], 'url' => null],
            ],
            'tipo' => $tipo,
            'config' => $config,
            'map' => ConfiguracaoService::MAP,
            'tipos' => $this->configuracoes->tipos(),
            'items' => $items,
        ], $this->extraAssets()), 'admin');
    }

    public function create(string $tipo): void
    {
        try {
            $config = $this->configuracoes->config($tipo);
        } catch (InvalidArgumentException $exception) {
            http_response_code(404);
            echo $exception->getMessage();
            exit;
        }

        $this->view('configuracoes.create', array_merge([
            'title' => 'Novo ' . mb_strtolower($config['label']),
            'breadcrumb' => [
                ['label' => 'Configurações', 'url' => null],
                ['label' => $config['plural'], 'url' => '/admin/configuracoes/' . $tipo],
                ['label' => 'Novo', 'url' => null],
            ],
            'tipo' => $tipo,
            'config' => $config,
            'map' => ConfiguracaoService::MAP,
            'tipos' => $this->configuracoes->tipos(),
        ], $this->extraAssets()), 'admin');
    }

    public function store(string $tipo): void
    {
        try {
            $this->configuracoes->criar($tipo, $_POST, (array) auth_user());
            flash('success', 'Registro cadastrado com sucesso.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            flash_input($_POST);
            redirect('/admin/configuracoes/' . $tipo . '/criar');
        }

        redirect('/admin/configuracoes/' . $tipo);
    }

    public function edit(string $tipo, string $id): void
    {
        try {
            $config = $this->configuracoes->config($tipo);
            $item = $this->configuracoes->find($tipo, (int) $id);
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            redirect('/admin/configuracoes/' . $tipo);
        }

        $this->view('configuracoes.edit', array_merge([
            'title' => 'Editar ' . mb_strtolower($config['label']),
            'breadcrumb' => [
                ['label' => 'Configurações', 'url' => null],
                ['label' => $config['plural'], 'url' => '/admin/configuracoes/' . $tipo],
                ['label' => 'Editar', 'url' => null],
            ],
            'tipo' => $tipo,
            'config' => $config,
            'map' => ConfiguracaoService::MAP,
            'tipos' => $this->configuracoes->tipos(),
            'item' => $item,
        ], $this->extraAssets()), 'admin');
    }

    public function update(string $tipo, string $id): void
    {
        try {
            $this->configuracoes->atualizar($tipo, (int) $id, $_POST, (array) auth_user());
            flash('success', 'Registro atualizado com sucesso.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            flash_input($_POST);
            redirect('/admin/configuracoes/' . $tipo . '/' . (int) $id . '/editar');
        }

        redirect('/admin/configuracoes/' . $tipo);
    }

    public function status(string $tipo, string $id): void
    {
        try {
            $status = $this->configuracoes->alternarStatus($tipo, (int) $id, (array) auth_user());
            flash('success', $status === 'ativo'
                ? 'Registro ativado com sucesso.'
                : 'Registro inativado com sucesso.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        }

        redirect('/admin/configuracoes/' . $tipo);
    }

    public function destroy(string $tipo, string $id): void
    {
        try {
            $this->configuracoes->excluir($tipo, (int) $id, (array) auth_user());
            flash('success', 'Registro excluído com sucesso.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        }

        redirect('/admin/configuracoes/' . $tipo);
    }

    public function quickStore(string $tipo): void
    {
        try {
            $item = $this->configuracoes->quickCriar($tipo, $_POST, (array) auth_user());
            $this->json(['ok' => true, 'item' => $item]);
        } catch (InvalidArgumentException $exception) {
            $this->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    private function extraAssets(): array
    {
        return [
            'extraStyles' => [asset('css/configuracoes.css')],
        ];
    }
}
