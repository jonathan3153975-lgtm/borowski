<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Models\Loja\ItemEstoque;
use App\Models\Loja\ItemPublicado;
use App\Models\Loja\Reserva;
use App\Services\AuditService;
use InvalidArgumentException;

class PublicacaoService
{
    public const CANAL_PADRAO = 'loja';

    private ItemPublicado $publicacoes;
    private ItemEstoque $itens;
    private Reserva $reservas;
    private AuditService $audit;

    public function __construct()
    {
        $this->publicacoes = new ItemPublicado();
        $this->itens = new ItemEstoque();
        $this->reservas = new Reserva();
        $this->audit = new AuditService();
    }

    public function canais(): array
    {
        return $this->publicacoes->canais();
    }

    public function canalId(string $slug): ?int
    {
        $canal = $this->publicacoes->canalPorSlug($slug);

        return $canal === null ? null : (int) $canal['id'];
    }

    public function listar(string $busca = ''): array
    {
        return [
            'publicacoes' => $this->publicacoes->listar(null, $busca),
            'itens' => $this->itens->listarDisponiveis(),
            'mapa' => $this->publicacoes->mapaPorItem(),
            'quantidade_publicada' => $this->publicacoes->quantidadePublicadaPorItem(),
            'canais' => $this->publicacoes->canais(),
        ];
    }

    public function mapaPorItem(): array
    {
        return $this->publicacoes->mapaPorItem();
    }

    public function quantidadePublicadaPorItem(): array
    {
        return $this->publicacoes->quantidadePublicadaPorItem();
    }

    public function idsPublicados(string $canalSlug = self::CANAL_PADRAO): array
    {
        $canalId = $this->canalId($canalSlug);

        return $canalId === null ? [] : $this->publicacoes->idsPublicados($canalId);
    }

    /**
     * Publica o mesmo item em um ou mais canais, com quantidade por canal.
     *
     * @return int[] IDs das publicacoes criadas.
     */
    public function publicarEmCanais(array $data, array $actor): array
    {
        $canaisSelecionados = $data['canais'] ?? null;

        if (!is_array($canaisSelecionados) || $canaisSelecionados === []) {
            $canalUnico = trim((string) ($data['canal'] ?? ''));
            $canaisSelecionados = $canalUnico !== '' ? [$canalUnico] : [self::CANAL_PADRAO];
        }

        $canaisSelecionados = array_values(array_unique(array_filter(
            array_map(static fn ($slug): string => trim((string) $slug), $canaisSelecionados),
            static fn (string $slug): bool => $slug !== ''
        )));

        if ($canaisSelecionados === []) {
            throw new InvalidArgumentException('Selecione ao menos um canal de publicação.');
        }

        $itemId = (int) ($data['item_id'] ?? 0);
        $item = $this->itens->find($itemId);

        if ($item === null) {
            throw new InvalidArgumentException('Selecione um item válido do estoque.');
        }

        $statusItem = (string) $item['status'];

        if (!in_array($statusItem, ['disponivel', 'pre_venda'], true)) {
            throw new InvalidArgumentException('Somente itens disponíveis ou em pré-venda podem ser publicados.');
        }

        $preco = $this->parseMoney($data['preco_venda'] ?? null);

        if ($preco <= 0) {
            throw new InvalidArgumentException('Informe um valor de venda maior que zero.');
        }

        $precoReserva = null;

        if ($statusItem === 'pre_venda') {
            $precoReserva = $this->parseMoney($data['preco_reserva'] ?? null);

            if ($precoReserva <= 0) {
                throw new InvalidArgumentException('Informe o valor de reserva para itens em pré-venda.');
            }

            if ($precoReserva > $preco) {
                throw new InvalidArgumentException('O valor de reserva não pode ser maior que o valor de venda.');
            }
        }

        $quantidades = is_array($data['quantidades'] ?? null) ? $data['quantidades'] : [];
        $quantidadePadrao = (int) ($data['quantidade'] ?? 0);

        $plano = [];

        foreach ($canaisSelecionados as $canalSlug) {
            $canal = $this->publicacoes->canalPorSlug($canalSlug);

            if ($canal === null) {
                throw new InvalidArgumentException('Canal de publicação inválido: ' . $canalSlug . '.');
            }

            $canalId = (int) $canal['id'];

            if ($this->publicacoes->exists($itemId, $canalId)) {
                throw new InvalidArgumentException(
                    'Este item já está publicado no canal ' . $canal['nome'] . '.'
                );
            }

            $quantidade = (int) ($quantidades[$canalSlug] ?? $quantidadePadrao);

            if ($quantidade < 1) {
                throw new InvalidArgumentException(
                    'Informe a quantidade para o canal ' . $canal['nome'] . '.'
                );
            }

            $plano[] = [
                'canal' => $canal,
                'canal_id' => $canalId,
                'quantidade' => $quantidade,
            ];
        }

        $totalItem = (int) $item['quantidade'];
        $comprometido = $this->reservas->comprometidoPorItem($itemId);
        $disponivel = max(0, $totalItem - $comprometido);

        if ($disponivel <= 0) {
            throw new InvalidArgumentException('Não há unidades disponíveis deste item para publicar.');
        }

        foreach ($plano as $entrada) {
            if ($entrada['quantidade'] > $disponivel) {
                throw new InvalidArgumentException(
                    'A quantidade do canal ' . $entrada['canal']['nome']
                    . ' não pode ser maior que o disponível ('
                    . $disponivel . ' de ' . $totalItem . ').'
                );
            }
        }

        $criados = [];

        foreach ($plano as $entrada) {
            $id = $this->publicacoes->create([
                'item_id' => $itemId,
                'canal_id' => $entrada['canal_id'],
                'preco_venda' => number_format($preco, 3, '.', ''),
                'preco_reserva' => $precoReserva !== null ? number_format($precoReserva, 3, '.', '') : null,
                'quantidade' => $entrada['quantidade'],
                'status' => 'publicado',
                'titulo' => (string) $item['nome'],
                'descricao' => $item['descricao'] ?? null,
                'criado_por' => $actor['id'] ?? null,
            ]);

            $criados[] = ['id' => $id, 'canal' => $entrada['canal']];
        }

        foreach ($criados as $criado) {
            if ((string) $criado['canal']['slug'] !== 'telegram') {
                continue;
            }

            try {
                (new TelegramService())->publicarNoGrupo((int) $criado['id']);
            } catch (\RuntimeException $exception) {
                foreach ($criados as $remover) {
                    $this->publicacoes->delete((int) $remover['id']);
                }

                throw new InvalidArgumentException($exception->getMessage());
            }
        }

        $nomesCanais = implode(', ', array_map(
            static fn (array $entrada): string => (string) $entrada['canal']['nome'],
            $plano
        ));

        $this->audit->log(
            'loja_item_publicado',
            'Item publicado em ' . $nomesCanais . ': ' . $item['nome']
                . ' (item ' . $itemId . ', publicações ' . implode(', ', array_column($criados, 'id')) . ').',
            isset($actor['id']) ? (int) $actor['id'] : null
        );

        return array_map(static fn (array $criado): int => (int) $criado['id'], $criados);
    }

    public function publicar(array $data, array $actor): int
    {
        $ids = $this->publicarEmCanais($data, $actor);

        return (int) ($ids[0] ?? 0);
    }

    public function excluir(int $id, array $actor): void
    {
        $publicacao = $this->publicacoes->findPublicacao($id);

        if ($publicacao === null) {
            throw new InvalidArgumentException('Publicação não encontrada.');
        }

        if ($this->reservas->reservasAbertasPorPublicacao($id) > 0) {
            throw new InvalidArgumentException(
                'Não é possível excluir: existem reservas em aberto para esta publicação.'
            );
        }

        $this->publicacoes->delete($id);

        $this->audit->log(
            'loja_publicacao_excluida',
            'Publicação removida: ' . $publicacao['item_nome']
                . ' (' . $publicacao['canal_nome'] . ', publicação ' . $id . ').',
            isset($actor['id']) ? (int) $actor['id'] : null
        );
    }

    /**
     * Converte o valor mascarado (ex.: "1.234,567") em float.
     */
    private function parseMoney(mixed $value): float
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        if ($digits === '') {
            return 0.0;
        }

        return ((int) $digits) / 1000;
    }
}
