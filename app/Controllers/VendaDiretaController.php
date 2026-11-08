<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\Loja\VendaDiretaService;
use InvalidArgumentException;
use Throwable;

class VendaDiretaController extends Controller
{
    public function index(): void
    {
        $this->view('vendas.direta', [
            'title' => 'Venda direta',
            'breadcrumb' => [
                ['label' => 'Vendas', 'url' => '/admin/vendas'],
                ['label' => 'Venda direta', 'url' => null],
            ],
            'itens' => (new VendaDiretaService())->listarItens(),
            'extraStyles' => [asset('css/loja.css')],
            'extraScripts' => [asset('js/loja.js')],
        ], 'admin');
    }

    public function store(): void
    {
        try {
            $selecionados = [];
            $ids = (array) ($_POST['selecionados'] ?? []);
            $quantidades = (array) ($_POST['quantidades'] ?? []);
            $precos = (array) ($_POST['precos'] ?? []);

            foreach ($ids as $id) {
                $id = (int) $id;

                if ($id <= 0) {
                    continue;
                }

                $selecionados[$id] = [
                    'quantidade' => (int) ($quantidades[$id] ?? 0),
                    'preco' => $precos[$id] ?? null,
                ];
            }

            $pedidoId = (new VendaDiretaService())->criar($_POST, $selecionados, (array) auth_user());

            flash('success', 'Venda direta #' . $pedidoId . ' registrada com sucesso.');
            redirect('/admin/vendas');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            flash_input($_POST);
        } catch (Throwable $exception) {
            flash('error', 'Falha ao registrar a venda: ' . $exception->getMessage());
            flash_input($_POST);
        }

        redirect('/admin/vendas/direta');
    }
}
