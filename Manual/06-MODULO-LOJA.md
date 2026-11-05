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
- Valor (maior que zero, máximo 9.999.999,99).

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
- Preço de custo (`DECIMAL(12,2)`).
- Observações (opcional, até 255 caracteres).
- Indicador de seleção.

Regras:

- Não é permitido repetir a mesma URL no mesmo item.
- Apenas uma oferta por item pode estar selecionada.
- Selecionar uma oferta define o custo base e o custo real do item.
- Sem oferta selecionada, o item exibe **Custo a definir** e custos zerados.
- Excluir a oferta selecionada limpa os custos do item.
- O item permanece em `pre_venda` nesta etapa. A conversão futura em compra direta não foi implementada.

## Cálculo do custo real

### Compra direta

Todos os cálculos são feitos em centavos inteiros. O rateio dos custos adicionais é proporcional ao subtotal base de cada item:

```text
subtotal_base_item = custo_unitario_base × quantidade
parcela_item = total_adicionais × subtotal_base_item ÷ subtotal_geral
custo_total_real_item = subtotal_base_item + parcela_item
custo_unitario_real = custo_total_real_item ÷ quantidade
```

Se o subtotal geral for zero, o rateio é proporcional à quantidade.

O resíduo de arredondamento é distribuído centavo a centavo, priorizando:

1. maior subtotal base;
2. menor `id` em caso de empate;
3. quando o subtotal geral é zero, maior quantidade e depois menor `id`.

A soma das parcelas é sempre igual ao total de custos adicionais.

### Exemplo

- Item A: quantidade 2, custo base 100,00 → subtotal 200,00.
- Item B: quantidade 1, custo base 50,00 → subtotal 50,00.
- Subtotal geral: 250,00.
- Custos adicionais: frete 30,00 + imposto 20,00 = 50,00.

Rateio:

- Item A: 50,00 × 200 ÷ 250 = 40,00 → total real 240,00 → unitário 120,00.
- Item B: 50,00 × 50 ÷ 250 = 10,00 → total real 60,00 → unitário 60,00.

Total real da entrada: 300,00.

### Previsão

- Oferta selecionada: custo base e unitário real iguais ao preço da oferta; total real = preço × quantidade.
- Sem oferta selecionada: custos `0,00` e interface exibe **Custo a definir**.

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

Detalhes de campos, índices e relacionamentos em [03-BANCO-DE-DADOS.md](03-BANCO-DE-DADOS.md).

## Arquivos implementados

- `app/Controllers/Loja/EntradaController.php`
- `app/Controllers/Loja/EstoqueController.php`
- `app/Models/Loja/Entrada.php`
- `app/Models/Loja/EntradaCusto.php`
- `app/Models/Loja/ItemEstoque.php`
- `app/Models/Loja/ItemFornecedor.php`
- `app/Services/AuditService.php`
- `app/Services/Loja/CalculoService.php`
- `app/Services/Loja/EntradaService.php`
- `app/Services/Loja/EstoqueService.php`
- `app/Services/Loja/PrevisaoService.php`
- `app/Views/loja/partials/tabs.php`
- `app/Views/loja/partials/pagination.php`
- `app/Views/loja/partials/item-edit-modal.php`
- `app/Views/loja/entradas/index.php`
- `app/Views/loja/entradas/criar.php`
- `app/Views/loja/entradas/detalhe.php`
- `app/Views/loja/estoque/index.php`
- `database/migrations/002_create_loja_tables.sql`
- `routes/loja.php`
- `public/assets/css/loja.css`
- `public/assets/js/loja.js`
- `scripts/testar-calculo-custos.php`

Arquivos alterados:

- `public/index.php` — carrega `routes/loja.php`.
- `app/Views/layouts/admin.php` — menu Loja e suporte a `extraStyles`/`extraScripts`.
- `app/Services/UserService.php` — passa a usar `AuditService`.
- `app/Controllers/AuthController.php` — passa a usar `AuditService`.
- `app/Helpers/functions.php` — helpers de moeda e situação.
- `scripts/migrate.php` — executa todas as migrations em ordem.
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
- Valores monetários não negativos, convertidos para centavos.
- Datas e valores persistidos com `DECIMAL(12,2)`.
- Prepared statements em todas as consultas com dados externos.
- CSRF global em `POST`.
- Escape de saída HTML.
- Confirmação em exclusões.
- Transações em operações compostas.
- Nenhuma senha, token ou URL sensível na auditoria.

## Testes realizados

- `php -l`: 52 arquivos PHP, 0 erros.
- `scripts/testar-calculo-custos.php`: 27 verificações, todas passaram.
- Roteiro HTTP do módulo: 60 verificações, todas passaram, cobrindo:
  - proteção de rotas;
  - criação de compra direta;
  - custos adicionais e recálculo;
  - itens e custos reais;
  - recebimento e filtros;
  - previsão, ofertas, seleção e exclusão;
  - CSRF, 404 e exclusão em cascata.
- Paginação do estoque com 21 itens: 9 verificações, todas passaram.
- Regressão do gerenciamento de usuários e auditoria: 11 verificações, todas passaram.

## Limitações desta etapa

- Não há conversão automática de item de pré-venda em compra direta.
- Custos adicionais não são aplicados a previsões.
- Não há imagens, categorias, fornecedores como entidade, movimentações de estoque ou integração com transportadoras.
- Não há baixa de estoque por venda, pois o módulo de vendas ainda não existe.
- A paginação é fixa em 20 registros.
- Não há relatórios exportáveis.
