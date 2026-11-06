<?php

declare(strict_types=1);

namespace App\Controllers\Loja;

use App\Core\Controller;
use App\Models\Loja\ItemImagem;
use App\Services\Loja\ItemImagemService;

class ItemImagemController extends Controller
{
    private ItemImagem $model;
    private ItemImagemService $imagens;

    public function __construct()
    {
        $this->model = new ItemImagem();
        $this->imagens = new ItemImagemService();
    }

    public function show(string $id): void
    {
        $imagem = $this->model->find((int) $id);

        if ($imagem === null) {
            $this->notFound();
            return;
        }

        $arquivo = $this->imagens->caminhoAbsoluto((string) $imagem['caminho_imagem']);
        $real = realpath($arquivo);
        $base = realpath((string) config('app.uploads.base_path', BASE_PATH . '/storage/uploads'));

        if (
            $real === false
            || $base === false
            || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)
            || !is_file($real)
        ) {
            $this->notFound();
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($real);

        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $this->notFound();
            return;
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($real));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        readfile($real);
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Foto não encontrada.';
    }
}
