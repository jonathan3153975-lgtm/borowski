<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\Loja\EntregaService;
use App\Services\Loja\TelegramService;
use InvalidArgumentException;
use Throwable;

class EntregaController extends Controller
{
    public function index(): void
    {
        $this->view('entregas.index', [
            'title' => 'Entregas',
            'breadcrumb' => [
                ['label' => 'Entregas', 'url' => null],
            ],
            'entregas' => (new EntregaService())->listar(200),
            'extraStyles' => [asset('css/loja.css')],
            'extraScripts' => [asset('js/loja.js')],
        ], 'admin');
    }

    public function itens(string $id): void
    {
        $service = new EntregaService();
        $entrega = $service->find((int) $id);

        if ($entrega === null) {
            $this->json(['error' => 'Entrega não encontrada.']);
            return;
        }

        $this->json([
            'entrega' => $entrega,
            'itens' => $service->itens((int) $id),
        ]);
    }

    public function encaminhar(string $id): void
    {
        try {
            $service = new EntregaService();

            if ($service->find((int) $id) === null) {
                throw new InvalidArgumentException('Entrega não encontrada.');
            }

            $service->atualizarStatus((int) $id, 'enviada');
            flash('success', 'Encaminhamento registrado para a entrega #' . (int) $id . '.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Falha ao registrar o encaminhamento: ' . $exception->getMessage());
        }

        redirect('/admin/entregas');
    }

    public function finalizar(string $id): void
    {
        try {
            $service = new EntregaService();

            if ($service->find((int) $id) === null) {
                throw new InvalidArgumentException('Entrega não encontrada.');
            }

            $service->atualizarStatus((int) $id, 'finalizada');

            try {
                (new TelegramService())->notificarEntregaFinalizada((int) $id);
            } catch (Throwable $exception) {
                error_log('[entrega] falha ao notificar finalizacao: ' . $exception->getMessage());
            }

            flash('success', 'Entrega #' . (int) $id . ' finalizada.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Falha ao finalizar a entrega: ' . $exception->getMessage());
        }

        redirect('/admin/entregas');
    }
}
