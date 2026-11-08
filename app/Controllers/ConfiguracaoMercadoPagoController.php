<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ConfiguracaoAppService;
use App\Services\Loja\MercadoPagoService;

class ConfiguracaoMercadoPagoController extends Controller
{
    public function index(): void
    {
        $config = new ConfiguracaoAppService();

        $this->view('configuracoes.mercadopago', [
            'title' => 'Mercado Pago',
            'breadcrumb' => [
                ['label' => 'Configurações', 'url' => null],
                ['label' => 'Mercado Pago', 'url' => null],
            ],
            'config' => $config->all(),
            'extraStyles' => [asset('css/configuracoes.css')],
        ], 'admin');
    }

    public function save(): void
    {
        $this->persistir($_POST);

        flash('success', 'Configurações do Mercado Pago salvas com sucesso.');
        redirect('/admin/configuracoes/mercadopago');
    }

    public function testar(): void
    {
        if (array_key_exists('mp_access_token', $_POST)) {
            $this->persistir($_POST);
        }

        $mercadoPago = new MercadoPagoService();
        $result = $mercadoPago->testarConexao();

        if ($result === null) {
            flash('error', 'Não foi possível conectar: ' . ($mercadoPago->ultimoErro() ?? 'erro desconhecido'));
        } else {
            $conta = (string) ($result['nickname'] ?? $result['email'] ?? 'conta');
            flash('success', 'Conectado ao Mercado Pago (' . $conta . ').');
        }

        redirect('/admin/configuracoes/mercadopago');
    }

    private function persistir(array $dados): void
    {
        (new ConfiguracaoAppService())->setMany([
            'mp_ativo' => (isset($dados['mp_ativo']) && $dados['mp_ativo'] === '1') ? '1' : '0',
            'mp_access_token' => trim((string) ($dados['mp_access_token'] ?? '')),
            'mp_public_key' => trim((string) ($dados['mp_public_key'] ?? '')),
        ]);
    }
}
