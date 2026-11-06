# 06 — Módulo Loja

## Visão geral

O módulo Loja gerencia as entradas de produtos e o estoque da loja. Ele possui duas abas:

1. **Entrada de produtos** — compras diretas e previsões de compra.
2. **Estoque** — itens cadastrados, com filtros por situação.

Ambos os perfis administrativos (`admin` e `super_admin`) possuem acesso ao módulo. Todas as rotas exigem autenticação.

## Estrutura de acesso

| Método | Rota | Ação |
|---|---|---|
| GET | `/admin/loja` | Redireciona para `/admin/loja/entradas` |
| GET | `/admin/loja/entradas` | Lista entradas |
| GET | `/admin/loja/entradas/criar` | Formulário de nova entrada |
| POST | `/admin/loja/entradas` | Cria compra direta ou previsão |
| GET | `/admin/loja/entradas/{id}` | Detalhes da entrada |
| POST | `/admin/loja/entradas/{id}` | Atualiza dados da entrada |
| POST | `/admin/loja/entradas/{id}/recebido` | Alterna recebimento |
| POST | `/admin/loja/entradas/{id}/excluir` | Exclui entrada |
| POST | `/admin/loja/entradas/{id}/custos` | Adiciona custo adicional |
| POST | `/admin/loja/entradas/{id}/custos/{custoId}/excluir` | Exclui custo |
| POST | `/admin/loja/entradas/{id}/itens` | Adiciona item |
| POST | `/admin/loja/entradas/{id}/itens/{itemId}/atualizar` | Atualiza item |
| POST | `/admin/loja/itens/{itemId}/excluir` | Exclui item |
| POST | `/admin/loja/itens/{itemId}/fornecedores` | Adiciona oferta de fornecedor |
| POST | `/admin/loja/itens/{itemId}/fornecedores/{ofertaId}/selecionar` | Seleciona oferta |
| POST | `/admin/loja/itens/{itemId}/fornecedores/{ofertaId}/excluir` | Exclui oferta |
| GET | `/admin/loja/estoque` | Lista estoque com filtros |

Todas as operações de escrita usam `POST`, CSRF e validação no backend.

## Compra direta

Campos da entrada:

- Descrição de origem (obrigatória, 3 a 180 caracteres).
- Link de compra (obrigatório, `http`/`https`, até 500 caracteres).
- Recebido (booleano).
- Código de rastreio (opcional, até 120 caracteres).
- Observações (opcional, até 2000 caracteres).

Regras:

- Entrada não recebida → itens em `em_transporte`.
- Entrada recebida → itens em `disponivel`.
- Alternar o recebimento atualiza todos os itens que estejam em `disponivel` ou `em_transporte`.
- Itens podem ser incluídos, editados e excluídos após a criação da entrada.
- Custos adicionais podem ser incluídos e excluídos após a criação da entrada.
- Excluir a entrada remove custos, itens e ofertas em cascata.

## Custos adicionais

Permitidos apenas em compras diretas nesta etapa.

Campos:

- Descrição (2 a 150 caracteres).
- Valor (maior que zero, máximo 999.999.999,999), com 3 casas decimais.

Toda alteração de custo dispara o recálculo dos itens da entrada.

## Previsão de compra

Campos da entrada:

- Descrição da previsão (obrigatória, 3 a 180 caracteres).
- Observações (opcional, até 2000 caracteres).

A previsão não possui link de compra nem recebimento. Todos os itens entram como `pre_venda`.

Cada item de previsão:

- Nome, código/SKU, quantidade, descrição e observações.
- Pelo menos uma oferta de fornecedor na criação.
- Pode receber várias ofertas adicionais.

Cada oferta:

- Fornecedor (2 a 150 caracteres).
- URL (`http`/`https`, até 500 caracteres).
- Preço de custo (`DECIMAL(12,3)`).
- Observações (opcional, até 255 caracteres).
- Indicador de seleção.

Regras:

- Não é permitido repetir a mesma URL no mesmo item.
- Apenas uma oferta por item pode estar selecionada.
- Selecionar uma oferta define o custo base e o custo real do item.
- Sem oferta selecionada, o item exibe **Custo a definir** e custos zerados.
- Excluir a oferta selecionada limpa os custos do item.
- O item permanece em `pre_venda` nesta etapa. A conversão futura em compra direta não foi implementada.

## Informações de catálogo do item

O cadastro do item inclui, diretamente:

- Categoria
- Marca
- Fabricante
- Coleção
- Escala
- Ano de fabricação

Todos os campos são opcionais e usam os cadastros auxiliares do menu **Configurações**. Não existe mais vínculo com produto cadastrado; o antigo módulo de produtos foi removido da interface. A exclusão de uma opção auxiliar é bloqueada enquanto houver item vinculado.

## Listagem e filtros de Entrada de produtos

- A pesquisa por texto permanece no topo e ocupa a maior parte da linha.
- O botão **Filtrar** foi removido; a pesquisa é enviada pelo teclado ou pelo ícone do campo.
- O filtro de **modo** usa pills: Todos, Compra direta e Previsão.
- O filtro de **recebimento** também usa pills, mas só fica habilitado quando o modo é **Compra direta**.
- Em previsão, as pills de recebimento aparecem desabilitadas.
- O controller ignora o parâmetro `recebido` quando o modo não é `direta`.
- A **Nova entrada** é aberta em modal, com o mesmo formulário de compra direta/previsão.
- A barra superior (abas, pesquisa e filtros) fica fixa; apenas a lista rola.
- No mobile, ao rolar a lista, a barra reduz e mantém apenas o campo de busca. Ao voltar ao topo, abas, filtros e botão de nova entrada reaparecem.
- A tabela vira cards no mobile, com descrição, modo, situação, itens, custos, total real e ações.

## Cadastro de item em modal

O cadastro de item da compra é feito em um modal completo, aberto pelo botão **Adicionar item**:

- Categoria, marca, fabricante, coleção, escala e ano de fabricação (opcionais).
- Nome, código/SKU, quantidade, descrição e observações.
- Custo unitário base na compra direta.
- Primeira oferta de fornecedor na previsão.
- **Fotos do item** com upload múltiplo e pré-visualização.
- Ao salvar, o modal fecha e a lista de itens é atualizada.

Regras:

- As fotos são validadas antes da criação do item.
- Se uma foto for inválida, nenhum item é criado.
- A primeira foto vira principal.
- As fotos são exibidas como miniaturas nas listas de itens e no estoque.
- Ao excluir um item ou a entrada, os arquivos das fotos são removidos.
- Se houver erro de validação, o modal reabre com os dados preenchidos; as fotos precisam ser selecionadas novamente.

As fotos ficam em `storage/uploads/loja_itens/` e são servidas pela rota autenticada:

```text
GET /admin/loja/itens/imagem/{id}
```

Limites configuráveis:

- `ITEM_IMAGE_MAX_SIZE` (padrão 5 MB por foto).
- `ITEM_IMAGE_MAX_IMAGES` (padrão 6 fotos por item).

## Cálculo do custo real

### Compra direta

Todos os cálculos são feitos em milésimos inteiros (3 casas decimais). O rateio dos custos adicionais é proporcional ao subtotal base de cada item:

```text
subtotal_base_item = custo_unitario_base × quantidade
parcela_item = total_adicionais × subtotal_base_item ÷ subtotal_geral
custo_total_real_item = subtotal_base_item + parcela_item
custo_unitario_real = custo_total_real_item ÷ quantidade
```

Se o subtotal geral for zero, o rateio é proporcional à quantidade.

O resíduo de arredondamento é distribuído milésimo a milésimo, priorizando:

1. maior subtotal base;
2. menor `id` em caso de empate;
3. quando o subtotal geral é zero, maior quantidade e depois menor `id`.

A soma das parcelas é sempre igual ao total de custos adicionais.

### Exemplo

- Item A: quantidade 2, custo base 100,000 → subtotal 200,000.
- Item B: quantidade 1, custo base 50,000 → subtotal 50,000.
- Subtotal geral: 250,000.
- Custos adicionais: frete 30,000 + imposto 20,000 = 50,000.

Rateio:

- Item A: 50,000 × 200 ÷ 250 = 40,000 → total real 240,000 → unitário 120,000.
- Item B: 50,000 × 50 ÷ 250 = 10,000 → total real 60,000 → unitário 60,000.

Total real da entrada: 300,000.

### Previsão

- Oferta selecionada: custo base e unitário real iguais ao preço da oferta; total real = preço × quantidade.
- Sem oferta selecionada: custos `0,000` e interface exibe **Custo a definir**.

### Recálculo

O recálculo ocorre ao:

- incluir, editar ou excluir item;
- incluir, editar ou excluir custo adicional;
- selecionar ou excluir oferta de previsão;
- alterar quantidade ou custo base.

As operações compostas usam transação PDO.

## Aba Estoque

Filtros:

- Todos.
- Disponível em estoque (`disponivel`).
- Em transporte (`em_transporte`).
- Em pré-venda (`pre_venda`).
- Pesquisa por nome do item, código ou descrição da entrada.

Resumo calculado do banco:

- itens cadastrados;
- unidades totais;
- unidades disponíveis;
- unidades em transporte;
- unidades em pré-venda;
- custo real total.

A listagem exibe item, origem/entrada, modo, situação, quantidade, custo real, fornecedor selecionado (ou menor referência) e data de cadastro.

Paginação fixa de 20 registros, preservando os filtros.

## Tabelas

- `loja_entradas`
- `loja_entrada_custos`
- `loja_itens`
- `loja_item_fornecedores`
- `loja_item_imagens`

Detalhes de campos, índices e relacionamentos em [03-BANCO-DE-DADOS.md](03-BANCO-DE-DADOS.md).

## Arquivos implementados

- `app/Controllers/Loja/EntradaController.php`
- `app/Controllers/Loja/EstoqueController.php`
- `app/Controllers/Loja/ItemImagemController.php`
- `app/Models/Loja/Entrada.php`
- `app/Models/Loja/EntradaCusto.php`
- `app/Models/Loja/ItemEstoque.php`
- `app/Models/Loja/ItemFornecedor.php`
- `app/Models/Loja/ItemImagem.php`
- `app/Services/AuditService.php`
- `app/Services/Loja/CalculoService.php`
- `app/Services/Loja/EntradaService.php`
- `app/Services/Loja/EstoqueService.php`
- `app/Services/Loja/PrevisaoService.php`
- `app/Services/Loja/ItemImagemService.php`
- `app/Views/loja/partials/tabs.php`
- `app/Views/loja/partials/pagination.php`
- `app/Views/loja/partials/item-edit-modal.php`
- `app/Views/loja/partials/entrada-form.php`
- `app/Views/loja/entradas/index.php`
- `app/Views/loja/entradas/detalhe.php`
- `app/Views/loja/estoque/index.php`
- `database/migrations/002_create_loja_tables.sql`
- `database/migrations/003_alter_money_precision.sql`
- `database/migrations/006_create_loja_item_imagens.sql`
- `database/migrations/007_add_catalogo_to_loja_itens.sql`
- `routes/loja.php`
- `public/assets/css/loja.css`
- `public/assets/js/loja.js`
- `scripts/testar-calculo-custos.php`

Arquivos alterados:

- `public/index.php` — carrega `routes/loja.php`.
- `app/Views/layouts/admin.php` — menu Loja e suporte a `extraStyles`/`extraScripts`.
- `app/Services/UserService.php` — passa a usar `AuditService`.
- `app/Controllers/AuthController.php` — passa a usar `AuditService`.
- `app/Helpers/functions.php` — helpers de moeda, situação e URL de foto do item.
- `scripts/migrate.php` — executa todas as migrations em ordem.
- `app/Views/layouts/admin.php` — menu sem o cadastro de produtos e com Configurações.
- Documentação em `Manual/`.

## Auditoria

O `AuditService` centraliza o registro em `logs_auditoria`. O `UserService` foi refatorado para utilizá-lo.

Ações do módulo Loja:

- `loja_entrada_criada`
- `loja_entrada_atualizada`
- `loja_entrada_excluida`
- `loja_entrada_recebida`
- `loja_entrada_reaberta`
- `loja_custo_adicionado`
- `loja_custo_excluido`
- `loja_item_adicionado`
- `loja_item_atualizado`
- `loja_item_excluido`
- `loja_fornecedor_adicionado`
- `loja_fornecedor_excluido`
- `loja_fornecedor_selecionado`

URLs completas de fornecedores não são registradas na auditoria.

## Validações e segurança

- Campos obrigatórios e tamanhos máximos validados no backend.
- URL restrita a `http`/`https`.
- Quantidade inteira entre 1 e 999.999.
- Valores monetários não negativos, convertidos para milésimos.
- Datas e valores persistidos com `DECIMAL(12,3)`.
- Máscara monetária de 3 casas decimais nos campos de valor.
- Prepared statements em todas as consultas com dados externos.
- CSRF global em `POST`.
- Escape de saída HTML.
- Confirmação em exclusões.
- Transações em operações compostas.
- Nenhuma senha, token ou URL sensível na auditoria.

## Testes realizados

- `php -l`: 52 arquivos PHP, 0 erros.
- `scripts/testar-calculo-custos.php`: 29 verificações, todas passaram.
- Roteiro HTTP do módulo: 62 verificações, todas passaram, cobrindo:
  - proteção de rotas;
  - criação de compra direta;
  - custos adicionais e recálculo;
  - itens e custos reais;
  - recebimento e filtros;
  - previsão, ofertas, seleção e exclusão;
  - CSRF, 404 e exclusão em cascata.
- Paginação do estoque com 21 itens: 9 verificações, todas passaram.
- Ajustes de UX, catálogo nos itens e fotos: 57 verificações, todas passaram.
- Regressão do gerenciamento de usuários e auditoria: 11 verificações, todas passaram.

## Limitações desta etapa

- Não há conversão automática de item de pré-venda em compra direta.
- Custos adicionais não são aplicados a previsões.
- Não há imagens, categorias, fornecedores como entidade, movimentações de estoque ou integração com transportadoras.
- Não há baixa de estoque por venda, pois o módulo de vendas ainda não existe.
- A paginação é fixa em 20 registros.
- Não há relatórios exportáveis.
