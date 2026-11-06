<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Core\Database;
use App\Models\Loja\ItemImagem;
use App\Services\AuditService;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ItemImagemService
{
    private ItemImagem $imagens;
    private AuditService $audit;
    private string $basePath;
    private string $path;
    private int $maxSize;
    private int $maxImages;
    private array $allowedMimes;
    private array $allowedExtensions;

    public function __construct()
    {
        $this->imagens = new ItemImagem();
        $this->audit = new AuditService();

        $config = (array) config('app.uploads.loja_itens', []);

        $this->basePath = (string) config('app.uploads.base_path', BASE_PATH . '/storage/uploads');
        $this->path = (string) ($config['path'] ?? $this->basePath . '/loja_itens');
        $this->maxSize = max(1, (int) ($config['max_size'] ?? 5242880));
        $this->maxImages = max(1, (int) ($config['max_images'] ?? 6));
        $this->allowedMimes = (array) ($config['allowed_mimes'] ?? ['image/jpeg', 'image/png', 'image/webp']);
        $this->allowedExtensions = (array) ($config['allowed_extensions'] ?? ['jpg', 'jpeg', 'png', 'webp']);
    }

    public function validarUploads(array $files): array
    {
        $normalizados = $this->normalizarArquivos($files);

        if ($normalizados === []) {
            return [];
        }

        if (count($normalizados) > $this->maxImages) {
            throw new InvalidArgumentException(
                'O item permite no máximo ' . $this->maxImages . ' fotos.'
            );
        }

        foreach ($normalizados as $file) {
            $this->validarArquivo($file);
        }

        return $normalizados;
    }

    public function salvar(int $itemId, array $validatedFiles, array $actor): array
    {
        if ($validatedFiles === []) {
            return [];
        }

        $atuais = $this->imagens->countByItem($itemId);

        if ($atuais + count($validatedFiles) > $this->maxImages) {
            throw new InvalidArgumentException(
                'O item permite no máximo ' . $this->maxImages . ' fotos.'
            );
        }

        $this->garantirDiretorio();

        $ordem = $this->imagens->proximaOrdem($itemId);
        $temImagens = $atuais > 0;
        $salvos = [];
        $ids = [];
        $db = Database::connection();
        $db->beginTransaction();

        try {
            foreach ($validatedFiles as $index => $file) {
                $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;
                $destino = $this->path . DIRECTORY_SEPARATOR . $nomeArquivo;

                if (!move_uploaded_file($file['tmp_name'], $destino)) {
                    throw new RuntimeException('Não foi possível salvar a foto do item.');
                }

                $salvos[] = $destino;
                $principal = !$temImagens && $index === 0;

                $ids[] = $this->imagens->create(
                    $itemId,
                    'loja_itens/' . $nomeArquivo,
                    mb_substr(basename($file['name']), 0, 180),
                    $ordem++,
                    $principal
                );
            }

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            foreach ($salvos as $arquivo) {
                if (is_file($arquivo)) {
                    @unlink($arquivo);
                }
            }

            throw $exception;
        }

        $this->audit->log(
            'imagem_item_adicionada',
            'Fotos adicionadas ao item (ID ' . $itemId . '): ' . count($ids) . '.',
            (int) ($actor['id'] ?? 0) ?: null
        );

        return $ids;
    }

    public function deletarArquivos(array $imagens): void
    {
        foreach ($imagens as $imagem) {
            $this->deletarArquivo((string) ($imagem['caminho_imagem'] ?? ''));
        }
    }

    public function caminhoAbsoluto(string $caminhoRelativo): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($caminhoRelativo, '/\\');
    }

    private function normalizarArquivos(array $files): array
    {
        if (!isset($files['name'])) {
            return [];
        }

        $normalizados = [];

        if (is_array($files['name'])) {
            foreach ($files['name'] as $index => $name) {
                $normalizados[] = [
                    'name' => (string) $name,
                    'tmp_name' => (string) ($files['tmp_name'][$index] ?? ''),
                    'error' => (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE),
                    'size' => (int) ($files['size'][$index] ?? 0),
                ];
            }
        } else {
            $normalizados[] = [
                'name' => (string) $files['name'],
                'tmp_name' => (string) ($files['tmp_name'] ?? ''),
                'error' => (int) ($files['error'] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($files['size'] ?? 0),
            ];
        }

        return array_values(array_filter(
            $normalizados,
            static fn (array $file): bool => $file['error'] !== UPLOAD_ERR_NO_FILE
        ));
    }

    private function validarArquivo(array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Falha no envio de uma das fotos.');
        }

        if ($file['size'] <= 0 || $file['size'] > $this->maxSize) {
            throw new InvalidArgumentException(
                'Cada foto deve ter no máximo ' . number_format($this->maxSize / 1048576, 1, ',', '.') . ' MB.'
            );
        }

        $nome = basename($file['name']);
        $extensao = strtolower(pathinfo($nome, PATHINFO_EXTENSION));

        if (!in_array($extensao, $this->allowedExtensions, true)) {
            throw new InvalidArgumentException('Formato de foto não permitido. Use JPG, PNG ou WebP.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('Arquivo de foto inválido.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $this->allowedMimes, true)) {
            throw new InvalidArgumentException('O arquivo enviado não é uma foto válida.');
        }

        $dimensoes = @getimagesize($file['tmp_name']);

        if ($dimensoes === false || (int) $dimensoes[0] < 1 || (int) $dimensoes[1] < 1) {
            throw new InvalidArgumentException('Não foi possível validar a foto enviada.');
        }
    }

    private function garantirDiretorio(): void
    {
        if (!is_dir($this->path) && !mkdir($this->path, 0775, true) && !is_dir($this->path)) {
            throw new RuntimeException('Não foi possível criar o diretório de fotos dos itens.');
        }

        if (!is_writable($this->path)) {
            throw new RuntimeException('O diretório de fotos dos itens não possui permissão de escrita.');
        }
    }

    private function deletarArquivo(string $caminhoRelativo): void
    {
        if ($caminhoRelativo === '') {
            return;
        }

        $arquivo = $this->caminhoAbsoluto($caminhoRelativo);

        if (is_file($arquivo) && !@unlink($arquivo)) {
            error_log('Falha ao excluir foto de item: ' . $arquivo);
        }
    }
}
