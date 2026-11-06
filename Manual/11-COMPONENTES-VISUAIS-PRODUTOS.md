# 11 — Componentes Visuais de Produtos

> **Componentes removidos junto com o código do módulo de produtos.** Este documento é mantido como registro histórico. Os cards, linhas e carrossel de produtos não são mais carregados nas rotas administrativas.

## Visão geral

O módulo de produtos possui três componentes reutilizáveis, preparados para a futura vitrine pública:

1. **Card de produto** (`card.php`).
2. **Linha horizontal de produtos** (`linha.php`).
3. **Carrossel de imagens** (`carousel.php`).

Os componentes recebem dados já carregados e não executam consultas próprias. Isso permite reutilizá-los no painel administrativo e, futuramente, na loja pública.

## Card de produto

Arquivo: `app/Views/produtos/partials/card.php`.

### Conteúdo

- Imagem principal do produto.
- Nome.
- Marca e coleção.
- Categoria e escala.
- Indicador de disponibilidade.
- Preço de venda, somente quando cadastrado e autorizado para exibição.

### Indicador de disponibilidade

O componente usa as quantidades calculadas a partir do estoque:

| Condição | Indicador |
|---|---|
| `qtd_disponivel > 0` | Disponível |
| senão, `qtd_em_transporte > 0` | Em transporte |
| senão, `qtd_pre_venda > 0` | Em pré-venda |
| caso contrário | Indisponível |

### Preço

O preço só é exibido quando:

- `preco_venda` está preenchido; e
- `preco_visivel = 1`.

Caso contrário, o espaço do preço fica vazio, sem inventar valor.

### Imagem

- Usa `produto_imagem_url($produto['imagem_id'])`.
- Quando não há imagem, exibe um placeholder com ícone.
- A imagem usa `object-fit: contain` para não distorcer.
- A capa prioriza a imagem marcada como principal.

### Link

O partial aceita `$link`. Por padrão, aponta para `/admin/produtos/{id}`. Na futura loja pública, basta passar a URL pública do produto.

### Exemplo de uso

```php
<?php
$produto = $item;
$link = url('/admin/produtos/' . (int) $item['id']);
require __DIR__ . '/partials/card.php';
?>
```

## Linha horizontal de produtos

Arquivo: `app/Views/produtos/partials/linha.php`.

### Recursos

- Cards de tamanho padronizado.
- Imagens proporcionais.
- Navegação por setas laterais.
- Rolagem horizontal por toque no mobile.
- Espaçamento uniforme.
- Quantidade de cards por vez configurável entre 2 e 5.
- Navegação independente para cada linha.
- Estado vazio quando não há produtos.

### Configuração

O partial aceita:

| Variável | Descrição |
|---|---|
| `$linha` | Lista de produtos |
| `$titulo` | Título da seção |
| `$linhaId` | Identificador único da linha |
| `$cardsPorVez` | Cards visíveis por vez (2 a 5) |
| `$linkVerTodos` | URL opcional do link "Ver todos" |

O tamanho dos cards é controlado por classes CSS `produto-linha-track--2` até `--5`, sem depender de estilos inline.

### Conjuntos de produtos

O componente é genérico. No admin, a listagem de produtos exibe duas linhas de exemplo:

- **Novidades** — produtos ativos mais recentes.
- **Destaques** — produtos ativos marcados com `destaque = 1`.

Futuramente, a loja pública poderá alimentar o componente com:

- novidades;
- mais recentes;
- por categoria;
- por marca;
- por coleção;
- destaques;
- disponíveis em estoque.

### Exemplo de uso

```php
<?php
$linha = $produtos;
$titulo = 'Novidades';
$linhaId = 'novidades';
$cardsPorVez = 4;
$linkVerTodos = null;
require __DIR__ . '/partials/linha.php';
?>
```

### Navegação

O JavaScript `public/assets/js/produtos.js` percorre cada `[data-product-row]` e controla:

- setas anterior/próxima;
- rolagem suave;
- desabilitação das setas nos limites;
- atualização ao redimensionar a janela.

Cada linha possui seu próprio container e navegação independente.

## Carrossel de imagens

Arquivo: `app/Views/produtos/partials/carousel.php`.

### Recursos

- Bootstrap Carousel.
- Indicadores de posição.
- Setas anterior/próxima.
- Miniaturas clicáveis.
- Destaque visual para a imagem principal.
- Placeholder quando não há imagens.
- Uso no formulário de edição e na tela de detalhes.

### Exemplo de uso

```php
<?php
$imagens = $detail['imagens'];
$carouselId = 'produtoShowCarousel';
$produtoNome = $produto['nome'];
require __DIR__ . '/partials/carousel.php';
?>
```

O `$carouselId` deve ser único na página.

## Estilos e responsividade

Arquivo: `public/assets/css/produtos.css`.

- Cards com altura consistente.
- Imagens sem distorção.
- Linhas com rolagem horizontal e snap.
- Setas ocultas no mobile, mantendo rolagem por toque.
- Layout de detalhes em duas colunas no desktop e uma no mobile.
- Carrossel com altura máxima e fundo neutro.
- Miniaturas com foco visível.
- Formulários e gerenciador de imagens empilhados no mobile.

## Acessibilidade

- Links de card com `aria-label`.
- Setas com `aria-label`.
- Miniaturas com `aria-label`.
- Indicadores do carrossel com rótulos.
- Imagens com texto alternativo.
- Estado vazio com mensagem textual.
- Foco visível nos botões e miniaturas.
- Navegação por teclado suportada pelos componentes do Bootstrap.

## Dependências frontend

| Biblioteca | Versão | Origem |
|---|---|---|
| Bootstrap | 5.3.3 | jsDelivr |
| Bootstrap Icons | 1.11.3 | jsDelivr |
| jQuery | 3.7.1 | code.jquery.com |
| JavaScript próprio | — | `produtos.js` e `masks.js` |

O jQuery é usado pelo cadastro rápido via AJAX e o `masks.js` aplica a máscara monetária de 3 casas decimais.

## Limitações

- Os componentes não consultam o banco; recebem dados prontos.
- A loja pública ainda não foi implementada.
- Não há carrinho, checkout ou pagamento.
- Não há filtros ou paginação internos nos componentes de linha; isso é responsabilidade da página que os utiliza.
