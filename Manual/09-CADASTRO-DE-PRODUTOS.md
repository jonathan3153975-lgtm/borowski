# 09 — Cadastro de Produtos

> **Módulo removido da aplicação na etapa de ajustes do módulo Loja.** O código e as rotas foram removidos; as tabelas históricas permanecem no banco. Este documento é mantido como registro histórico. O cadastro de itens agora preenche categoria, marca, fabricante, coleção, escala e ano de fabricação diretamente no item.

## Visão geral

O cadastro de produtos é a entidade central do catálogo da loja. Cada produto é único no sistema e pode ser vinculado a diferentes entradas de compra, itens de estoque e futuras vendas.

O módulo foi preparado para alimentar a vitrine pública, mas nesta etapa a administração ocorre no painel e a loja pública ainda não foi implementada.

## Formulário de cadastro

O formulário é dividido em abas:

1. **Informações principais**
2. **Características**
3. **Comercial e estoque**
4. **Imagens** (seção separada, disponível após o produto ser salvo)

### Informações principais

| Campo | Obrigatório | Regra |
|---|---|---|
| Nome do produto | Sim | 2 a 180 caracteres |
| SKU / código interno | Não | Até 80 caracteres, único quando informado |
| Descrição resumida | Não | Até 255 caracteres |
| Descrição completa | Não | Até 5000 caracteres |
| Categoria | Sim | Deve existir em `categorias` |
| Marca | Não | Deve existir em `marcas` |
| Fabricante | Não | Deve existir em `fabricantes` |
| Coleção | Não | Deve existir em `colecoes` |
| Modelo | Não | Até 120 caracteres |
| Escala | Não | Deve existir em `escalas` |
| Cor predominante | Não | Até 60 caracteres |
| Ano de fabricação | Não | Inteiro entre 1900 e 2100 |
| Material predominante | Não | Até 80 caracteres |
| Código de referência do fabricante | Não | Até 80 caracteres |
| Status do cadastro | Sim | `ativo` ou `inativo` |

Os campos de categoria, marca, fabricante, coleção e escala possuem botão de cadastro rápido. O novo registro é criado por AJAX, fica ativo e é selecionado automaticamente, sem perder os demais dados do formulário.

### Características do item

| Campo | Regra |
|---|---|
| Fabricante do veículo original | Até 120 caracteres |
| Modelo do veículo original | Até 120 caracteres |
| Ano do veículo representado | Inteiro entre 1900 e 2100 |
| Tipo de veículo | Até 60 caracteres |
| Edição especial ou limitada | Booleano |
| Número de unidades da edição | Inteiro entre 1 e 1.000.000, quando informado |
| Lacre | `lacrado`, `aberto` ou não informado |
| Embalagem original | Sim, não ou não informado |
| Nível de conservação | Até 80 caracteres, com sugestões no formulário |
| Observações adicionais | Até 5000 caracteres |

Todos os campos são opcionais.

### Comercial e estoque

| Campo | Regra |
|---|---|
| Preço de venda | Opcional, `DECIMAL(12,3)`, com 3 casas decimais |
| Autorizar exibição do preço | Booleano; exige preço informado |
| Destaque nas linhas da loja | Booleano |

O preço de venda é separado do custo de aquisição. O custo é obtido do histórico de compras e não é editável no cadastro do produto.

O estoque **não é editável** no formulário. O saldo é calculado a partir dos itens de `loja_itens` vinculados ao produto (`produto_id`) e das situações registradas no módulo Loja.

## Integração com o módulo Loja

- `loja_itens` recebeu a coluna opcional `produto_id`.
- Ao incluir um item em uma entrada, o usuário pode selecionar um produto cadastrado.
- A seleção preenche automaticamente nome e código do item, que ainda podem ser ajustados.
- O saldo do produto é somado por situação:
  - `disponivel` → quantidade disponível;
  - `em_transporte` → quantidade em transporte;
  - `pre_venda` → quantidade em pré-venda.
- O custo médio real é calculado como `SUM(custo_total_real) / SUM(quantidade)` dos itens vinculados.
- O histórico de entradas lista as compras e previsões que possuem itens do produto.
- A exclusão do produto é bloqueada quando existe item de estoque vinculado. Nesse caso, o produto deve ser inativado.

## Imagens

### Upload

- Múltiplas imagens por envio.
- Formatos aceitos: JPG, JPEG, PNG e WebP.
- Tamanho máximo padrão: 5 MB por imagem.
- Limite padrão: 10 imagens por produto.
- Os limites podem ser alterados por `PRODUCT_IMAGE_MAX_SIZE` e `PRODUCT_IMAGE_MAX_IMAGES`.

### Armazenamento

- Os arquivos são salvos em `storage/uploads/produtos/`, fora do document root.
- O banco armazena o caminho relativo (`produtos/arquivo.ext`), nome original, indicador de imagem principal, ordem de exibição e data.
- Nomes de arquivos são gerados com `random_bytes`, sem depender do nome enviado pelo usuário.
- Os arquivos não são executáveis pelo servidor web.

### Exibição

As imagens são servidas pela rota pública:

```text
GET /produto-imagem/{id}
```

A rota valida o caminho real dentro de `storage/uploads`, verifica o MIME com `finfo` e envia apenas `image/jpeg`, `image/png` ou `image/webp`, com `X-Content-Type-Options: nosniff`.

### Gerenciamento

Na tela de edição do produto é possível:

- visualizar o carrossel com indicadores, setas e miniaturas;
- definir a imagem principal;
- reordenar as imagens para cima/baixo;
- substituir uma imagem;
- excluir uma imagem;
- visualizar a prévia de múltiplos arquivos antes do envio.

Regras:

- A primeira imagem enviada vira principal automaticamente.
- Ao excluir a imagem principal, a primeira imagem restante na ordem vira principal.
- Apenas uma imagem por produto pode ser principal (regra no service).
- A capa do produto usa a imagem principal; na ausência dela, a primeira imagem da ordem.

## Listagem administrativa

A listagem apresenta:

- miniatura da imagem principal;
- nome e SKU;
- categoria, marca, coleção e escala;
- quantidade disponível, em transporte e em pré-venda;
- status do cadastro;
- ações de ver, editar, ativar/inativar e excluir.

Recursos:

- pesquisa por nome e SKU;
- filtros por categoria, marca, coleção, escala e status;
- ordenação por mais recentes, mais antigos, nome A-Z, nome Z-A e categoria;
- paginação de 20 registros;
- tabela em telas maiores e cards em dispositivos menores;
- prévia das linhas da loja com as coleções "Novidades" e "Destaques".

## Tela de detalhes

A tela de detalhes apresenta:

- carrossel principal com miniaturas;
- nome, descrições, categoria, marca, fabricante, coleção, modelo e escala;
- informações de conservação, lacre, embalagem, edição limitada e observações;
- preço de venda com indicação de visibilidade;
- cards de estoque e custo médio real;
- histórico de entradas;
- prévia do card reutilizável da loja.

A tela reutiliza os mesmos componentes de carrossel, card e linha preparados para a futura vitrine pública.

## Rotas administrativas

| Método | Rota | Ação |
|---|---|---|
| GET | `/admin/produtos` | Listagem com filtros |
| GET | `/admin/produtos/criar` | Formulário de cadastro |
| POST | `/admin/produtos` | Criar produto |
| GET | `/admin/produtos/{id}` | Detalhes |
| GET | `/admin/produtos/{id}/editar` | Edição + imagens |
| POST | `/admin/produtos/{id}` | Atualizar produto |
| POST | `/admin/produtos/{id}/status` | Ativar/inativar |
| POST | `/admin/produtos/{id}/excluir` | Excluir com verificação de vínculos |
| POST | `/admin/produtos/{id}/imagens` | Enviar imagens |
| POST | `/admin/produtos/{id}/imagens/{imagemId}/principal` | Definir principal |
| POST | `/admin/produtos/{id}/imagens/{imagemId}/mover` | Reordenar |
| POST | `/admin/produtos/{id}/imagens/{imagemId}/excluir` | Excluir imagem |
| POST | `/admin/produtos/{id}/imagens/{imagemId}/substituir` | Substituir imagem |
| GET | `/produto-imagem/{id}` | Servir imagem (pública, somente imagens) |

Todas as rotas administrativas exigem autenticação. A rota de imagem é pública e somente leitura.

## Tabelas

- `produtos`
- `produtos_imagens`
- `categorias`
- `marcas`
- `fabricantes`
- `colecoes`
- `escalas`
- `loja_itens.produto_id` (nova coluna de integração)

Detalhes em [03-BANCO-DE-DADOS.md](03-BANCO-DE-DADOS.md).

## Arquivos

- `app/Controllers/ProdutoController.php`
- `app/Controllers/ProdutoImagemController.php`
- `app/Models/Produto.php`
- `app/Models/ProdutoImagem.php`
- `app/Services/ProdutoService.php`
- `app/Services/ProdutoImagemService.php`
- `app/Views/produtos/**`
- `routes/produtos.php`
- `public/assets/css/produtos.css`
- `public/assets/js/produtos.js`
- `database/migrations/004_create_produtos_tables.sql`
- `database/migrations/005_add_produto_id_to_loja_itens.sql`

## Segurança

- Validação de todos os campos no backend.
- CSRF em todas as operações de escrita.
- Preço e custo em `DECIMAL(12,3)`.
- Upload validado por extensão, MIME real, tamanho e `getimagesize`.
- Nomes de arquivos aleatórios e seguros.
- Arquivos fora de `public/` e sem execução.
- Exclusão bloqueada quando há vínculo com estoque.
- Auditoria de criação, edição, status, exclusão, upload, principal, reordenação, substituição e exclusão de imagens.

## Testes realizados

- `php -l` em todos os arquivos PHP.
- 74 verificações HTTP do módulo de produtos, cobrindo cadastro, edição, SKU duplicado, imagens, carrossel, filtros, integração com estoque e bloqueio de exclusão.
- 29 verificações do cálculo monetário com 3 casas decimais.
- 62 verificações do módulo Loja após a refatoração monetária.
- Regressão de autenticação, usuários e auditoria.

## Limitações desta etapa

- A vitrine pública ainda não foi implementada; os componentes estão preparados para reutilização.
- Não há variações de produto, código de barras, lotes ou múltiplos preços.
- Não há exclusão em massa, importação/exportação ou relatórios.
- Não há integração com checkout, carrinho ou pagamentos.
- O vínculo com estoque depende de o item ser associado ao produto no formulário do módulo Loja.
