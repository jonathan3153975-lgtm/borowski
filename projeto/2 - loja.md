# ETAPA 02 — Módulo Loja: Entrada de Produtos e Estoque

## 0. Instruções gerais para a IA

Você atuará como desenvolvedor responsável pela implementação do **módulo Loja** do sistema de gerenciamento da loja de carrinhos colecionáveis, sobre a base já entregue na Etapa 01.

Implemente o projeto diretamente no workspace, respeitando a arquitetura existente.

### Regras obrigatórias de execução

1. Antes de criar ou alterar arquivos, inspecione o código existente.
2. Preserve tudo o que estiver correto na Etapa 01: autenticação, sessão, CSRF, rotas, layouts, permissões, auditoria e scripts.
3. Não crie um segundo sistema de autenticação, sessão, CSRF ou conexão com banco. Reutilize o núcleo existente.
4. Não invente funcionalidades fora do escopo desta etapa.
5. Não deixe botões sem ação, links falsos, trechos fictícios ou `TODO` em funcionalidades declaradas como concluídas.
6. Utilize orientação a objetos, separação de responsabilidades, nomes consistentes e `declare(strict_types=1)`.
7. Toda validação crítica deve ocorrer no backend. Validação JavaScript é complementar.
8. Use PDO com prepared statements em todas as consultas com dados externos.
9. Toda operação de criação, alteração ou exclusão deve apresentar feedback ao usuário.
10. Toda operação que altere estado deve usar `POST` e token CSRF.
11. Toda alteração relevante deve ser registrada em auditoria, sem dados sensíveis.
12. Valores monetários devem usar `DECIMAL(12,2)`. Nunca use `FLOAT` ou `DOUBLE` para dinheiro.
13. Ao concluir, execute as verificações disponíveis, corrija erros e apresente um relatório final.
14. Atualize a documentação obrigatória em `Manual/` durante a implementação, não apenas ao final.

Se alguma decisão não estiver especificada, escolha a alternativa mais simples, segura e sustentável, registre a decisão na documentação e não avance para módulos fora do escopo.

---

## 1. Visão geral

O **módulo Loja** é a área administrativa responsável por catalogar as compras da loja e organizar as mercadorias que entrarão no estoque.

Nesta etapa existirão duas abas principais:

1. **Entrada de produtos** — cadastro e acompanhamento das compras.
2. **Estoque** — consulta dos itens registrados, com filtros por situação.

A aba **Entrada de produtos** terá dois modos de cadastro:

1. **Compra direta** — compra já realizada, com dados de origem, link, recebimento, rastreio, custos adicionais e mercadorias.
2. **Previsão de compra** — itens disponibilizados para pré-venda, cadastrados item a item, com vários links de fornecedores e seus respectivos preços de custo.

Todos os itens cadastrados, em qualquer modo, aparecerão na aba **Estoque** com a situação correspondente:

- **Disponível em estoque**
- **Em transporte**
- **Em pré-venda**

### 1.1. Objetivos da etapa

- Registrar compras diretas e previsões de compra.
- Calcular o custo real da mercadoria, incluindo custos adicionais.
- Organizar itens de pré-venda com múltiplas opções de fornecedores.
- Centralizar a consulta de itens no estoque com filtros por situação.
- Manter auditoria das operações.

### 1.2. Fora do escopo desta etapa

Não implementar ainda:

- Loja virtual pública.
- Carrinho, checkout, pedidos ou vendas.
- Clientes e endereços.
- Gestão de fornecedores como entidade própria.
- Cadastro de categorias.
- Imagens de produtos.
- Movimentações de estoque por venda.
- Integração com transportadoras ou rastreamento automático.
- Integração com gateways de pagamento.
- Relatórios financeiros, cupons ou promoções.
- Conversão automática de item em pré-venda para compra direta.
- Controle de estoque mínimo, inventário ou baixa manual.

Esses itens poderão ser tratados em etapas futuras, sem antecipar tabelas ou regras não especificadas.

---

## 2. Conceitos e nomenclatura

| Conceito | Definição |
|---|---|
| Entrada | Registro de uma compra direta ou de uma previsão de compra |
| Compra direta | Entrada de mercadoria já adquirida, recebida ou em transporte |
| Previsão de compra | Entrada de itens para pré-venda, ainda sem compra consolidada |
| Custo adicional | Frete, imposto, taxa ou outro valor que compõe o custo real da entrada |
| Item de estoque | Mercadoria vinculada a uma entrada |
| Oferta de fornecedor | Link de fornecedor com preço de custo, vinculado a um item de previsão |
| Custo real | Custo do item considerando o custo base e a parcela dos custos adicionais |
| Estoque | Lista consolidada de itens com situação `disponivel`, `em_transporte` ou `pre_venda` |

---

## 3. Abas e navegação

### 3.1. Menu administrativo

Adicionar ao menu lateral a seção **Loja**, disponível para usuários autenticados dos perfis `admin` e `super_admin`, com os links:

- **Entrada de produtos** → `/admin/loja/entradas`
- **Estoque** → `/admin/loja/estoque`

A rota `/admin/loja` deve redirecionar para `/admin/loja/entradas`.

### 3.2. Abas internas

As páginas do módulo devem exibir abas no topo:

- **Entrada de produtos**
- **Estoque**

A aba ativa deve ser destacada visualmente. As abas devem funcionar em desktop e mobile, utilizando os componentes do Bootstrap já adotados.

### 3.3. Permissões

- Visitantes não autenticados: redirecionar para `/login`.
- `admin`: acesso completo ao módulo Loja.
- `super_admin`: acesso completo ao módulo Loja.
- Nenhuma rota do módulo Loja exige perfil `super_admin`.

---

## 4. Modo 1 — Compra direta

### 4.1. Cadastro da entrada

Campos:

| Campo | Obrigatório | Regra |
|---|---|---|
| Descrição de origem | Sim | Texto de 3 a 180 caracteres. Ex.: fornecedor, marketplace ou identificação do lote |
| Link de compra | Sim | URL `http` ou `https`, até 500 caracteres |
| Recebido | Não | Booleano. Padrão: não recebido |
| Código de rastreio | Não | Texto até 120 caracteres |
| Observações | Não | Texto livre |

Regras:

- A entrada pode ser criada antes dos itens.
- O link de compra deve ser validado no backend.
- O código de rastreio pode ser informado mesmo antes do recebimento.
- Ao criar uma compra direta não recebida, os itens adicionados depois entram como `em_transporte`.
- Ao marcar a entrada como recebida, todos os itens da entrada passam para `disponivel`.
- Ao desmarcar o recebimento, todos os itens ainda em `disponivel` ou `em_transporte` voltam para `em_transporte`.
- A ação de alternar recebimento deve ser `POST`, com CSRF e auditoria.

### 4.2. Custos adicionais

Após criar a entrada, o usuário poderá adicionar custos adicionais como:

- frete;
- imposto;
- taxa;
- embalagem;
- outro custo.

Cada custo possui:

| Campo | Obrigatório | Regra |
|---|---|---|
| Descrição | Sim | Texto de 2 a 150 caracteres |
| Valor | Sim | `DECIMAL(12,2)`, maior que zero, máximo 9.999.999,99 |

Regras:

- Custos adicionais são permitidos apenas em compras diretas nesta etapa.
- A inclusão, edição ou exclusão de um custo deve disparar o recálculo do custo real de todos os itens da entrada.
- A entrada deve exibir o total de custos adicionais.
- Excluir custo exige `POST`, CSRF e confirmação.

### 4.3. Itens da compra direta

Após a criação da entrada, o usuário poderá incluir as mercadorias.

Cada item possui:

| Campo | Obrigatório | Regra |
|---|---|---|
| Nome | Sim | Texto de 2 a 180 caracteres |
| Descrição | Não | Texto livre |
| Código interno/SKU | Não | Texto até 80 caracteres |
| Quantidade | Sim | Inteiro entre 1 e 999.999 |
| Custo unitário base | Sim | `DECIMAL(12,2)`, maior ou igual a zero |
| Observações | Não | Texto livre |

Regras:

- O item herda o vínculo com a entrada.
- A situação inicial do item é derivada da entrada:
  - entrada não recebida → `em_transporte`;
  - entrada recebida → `disponivel`.
- A inclusão, alteração ou exclusão de item deve disparar o recálculo do rateio dos custos adicionais.
- A exclusão de item exige `POST`, CSRF e confirmação.
- A exclusão da entrada remove em cascata custos e itens (e ofertas, quando existirem).
- A entrada deve exibir:
  - total base;
  - total de custos adicionais;
  - total real;
  - custo real por item.

---

## 5. Modo 2 — Previsão de compra

### 5.1. Cadastro da previsão

Campos:

| Campo | Obrigatório | Regra |
|---|---|---|
| Descrição da previsão | Sim | Texto de 3 a 180 caracteres. Ex.: campanha, coleção ou lote |
| Observações | Não | Texto livre |

Regras:

- A previsão não exige link de compra no cabeçalho.
- O recebimento não se aplica à previsão.
- Os itens serão adicionados um a um na página de detalhes da previsão.
- Todos os itens de uma previsão entram no estoque com situação `pre_venda`.

### 5.2. Itens da previsão

Cada item possui:

| Campo | Obrigatório | Regra |
|---|---|---|
| Nome | Sim | Texto de 2 a 180 caracteres |
| Descrição | Não | Texto livre |
| Código interno/SKU | Não | Texto até 80 caracteres |
| Quantidade | Sim | Inteiro entre 1 e 999.999 |
| Observações | Não | Texto livre |
| Primeira oferta de fornecedor | Sim | Fornecedor, URL e preço de custo |

Cada oferta de fornecedor possui:

| Campo | Obrigatório | Regra |
|---|---|---|
| Fornecedor | Sim | Texto de 2 a 150 caracteres |
| URL | Sim | URL `http` ou `https`, até 500 caracteres |
| Preço de custo | Sim | `DECIMAL(12,2)`, maior ou igual a zero |
| Observações | Não | Texto até 255 caracteres |

Regras:

- Um item pode possuir vários links de fornecedores.
- Não permitir dois links idênticos para o mesmo item.
- Ofertar um item não significa compra confirmada.
- Apenas uma oferta por item pode ser marcada como **selecionada**.
- Ao selecionar uma oferta:
  - o `custo_unitario_base` do item passa a ser o preço da oferta selecionada;
  - o custo real do item é recalculado;
  - a oferta anteriormente selecionada, se existir, é desmarcada.
- Se nenhuma oferta estiver selecionada:
  - o custo base e o custo real do item permanecem zerados;
  - a interface exibe **Custo a definir**;
  - a menor oferta pode ser exibida como referência, sem ser tratada como custo real.
- Ao excluir a oferta selecionada, a seleção e os custos do item devem ser limpos.
- A exclusão de oferta exige `POST`, CSRF e confirmação.
- O item de previsão permanece com situação `pre_venda` nesta etapa. A conversão em compra direta fica para etapa futura.

---

## 6. Cálculo do custo real

### 6.1. Regras gerais

- Todos os cálculos devem ser feitos em **centavos inteiros** para evitar erros de ponto flutuante.
- Persistir valores em `DECIMAL(12,2)`.
- O custo real de um item é composto por:
  - custo base do item;
  - parcela rateada dos custos adicionais da entrada.

### 6.2. Rateio dos custos adicionais

Para compras diretas:

1. Calcular o subtotal base de cada item:
   `subtotal_base = custo_unitario_base × quantidade`.
2. Calcular o subtotal base geral da entrada:
   `subtotal_geral = soma dos subtotais_base`.
3. Calcular o total de custos adicionais:
   `total_adicionais = soma dos valores dos custos adicionais`.
4. Se `subtotal_geral > 0`:
   - a parcela de cada item é proporcional ao seu subtotal base;
   - fórmula base: `parcela = total_adicionais × subtotal_base_item ÷ subtotal_geral`.
5. Se `subtotal_geral = 0` e houver itens:
   - ratear proporcionalmente à quantidade de cada item.
6. Arredondar para centavos.
7. Distribuir a diferença de arredondamento, centavo a centavo:
   - primeiro para os itens com maior subtotal base;
   - em caso de empate, para o menor `id`.
   - quando o subtotal geral for zero, ordenar por maior quantidade e depois por menor `id`.
8. Garantir que a soma das parcelas seja exatamente igual ao total de custos adicionais.

### 6.3. Custo real do item

- `custo_total_real_item = subtotal_base_item + parcela_rateada_item`.
- `custo_unitario_real = custo_total_real_item ÷ quantidade`, arredondado para 2 casas.
- O valor total real do item deve ser mantido em centavos exatos; o valor unitário é uma representação arredondada.

### 6.4. Exemplo

Entrada:

- Item A: quantidade 2, custo unitário base 100,00 → subtotal 200,00.
- Item B: quantidade 1, custo unitário base 50,00 → subtotal 50,00.
- Subtotal geral: 250,00.
- Custos adicionais: frete 30,00 + imposto 20,00 = 50,00.

Rateio:

- Item A: 50,00 × 200 ÷ 250 = 40,00.
- Item B: 50,00 × 50 ÷ 250 = 10,00.

Custo real:

- Item A: total real 240,00; unitário real 120,00.
- Item B: total real 60,00; unitário real 60,00.
- Total real da entrada: 300,00 = 250,00 + 50,00.

### 6.5. Previsão de compra

- Não há custos adicionais nesta etapa.
- Se uma oferta estiver selecionada:
  - `custo_unitario_base = preço da oferta`;
  - `custo_unitario_real = preço da oferta`;
  - `custo_total_real = preço da oferta × quantidade`.
- Se nenhuma oferta estiver selecionada:
  - todos os custos permanecem `0,00`;
  - a interface exibe **Custo a definir**.

### 6.6. Recálculo obrigatório

Recalcular o custo real sempre que ocorrer:

- inclusão, edição ou exclusão de item;
- inclusão, edição ou exclusão de custo adicional;
- seleção ou remoção de oferta de fornecedor;
- alteração de quantidade ou custo base.

O recálculo deve ocorrer em transação quando mais de uma tabela for alterada.

---

## 7. Aba Estoque

### 7.1. Listagem

A aba Estoque deve listar todos os itens cadastrados no módulo, independentemente do modo de entrada.

Colunas mínimas:

- Nome do item.
- Código/SKU.
- Origem/entrada.
- Modo da entrada (`Compra direta` / `Previsão`).
- Situação.
- Quantidade.
- Custo unitário real (ou **Custo a definir**).
- Fornecedor selecionado ou menor referência, quando for pré-venda.
- Data de cadastro.
- Ação para abrir a entrada de origem.

### 7.2. Filtros

Filtros obrigatórios:

- **Todos**
- **Disponível em estoque** (`disponivel`)
- **Em transporte** (`em_transporte`)
- **Em pré-venda** (`pre_venda`)

Filtros complementares:

- Pesquisa por nome do item ou código/SKU.
- Pesquisa por descrição da entrada.

### 7.3. Resumo

Exibir cards calculados a partir dos dados reais:

- total de itens cadastrados;
- total de unidades;
- unidades disponíveis;
- unidades em transporte;
- unidades em pré-venda;
- valor total de custo real dos itens em estoque.

Não exibir números fictícios.

### 7.4. Paginação e ordenação

- Paginação de 20 itens por página.
- Ordenação padrão: itens mais recentes primeiro.
- Preservar filtros na paginação.

### 7.5. Estados vazios

Exibir mensagem adequada quando:

- não houver itens cadastrados;
- o filtro não retornar resultados;
- a pesquisa não encontrar itens.

---

## 8. Modelo de dados

Criar a migration `database/migrations/002_create_loja_tables.sql`, idempotente com `CREATE TABLE IF NOT EXISTS`, utilizando InnoDB, `utf8mb4` e `utf8mb4_unicode_ci`.

### 8.1. Tabela `loja_entradas`

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT |
| `modo` | `ENUM('direta','previsao')` | NOT NULL |
| `descricao_origem` | `VARCHAR(180)` | NOT NULL |
| `link_compra` | `VARCHAR(500)` | NULL |
| `recebido` | `TINYINT(1)` | NOT NULL, DEFAULT 0 |
| `codigo_rastreio` | `VARCHAR(120)` | NULL |
| `observacoes` | `TEXT` | NULL |
| `criado_por` | `INT UNSIGNED` | NULL, FK → `usuarios.id` |
| `criado_em` | `DATETIME` | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| `atualizado_em` | `DATETIME` | NULL, ON UPDATE CURRENT_TIMESTAMP |

Índices:

- `PRIMARY KEY (id)`
- `KEY idx_loja_entradas_modo (modo)`
- `KEY idx_loja_entradas_recebido (recebido)`
- `KEY idx_loja_entradas_criado_em (criado_em)`
- `KEY idx_loja_entradas_criado_por (criado_por)`
- `CONSTRAINT fk_loja_entradas_usuario FOREIGN KEY (criado_por) REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE`

### 8.2. Tabela `loja_entrada_custos`

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT |
| `entrada_id` | `BIGINT UNSIGNED` | NOT NULL, FK → `loja_entradas.id` |
| `descricao` | `VARCHAR(150)` | NOT NULL |
| `valor` | `DECIMAL(12,2)` | NOT NULL |
| `criado_em` | `DATETIME` | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| `atualizado_em` | `DATETIME` | NULL, ON UPDATE CURRENT_TIMESTAMP |

Índices:

- `PRIMARY KEY (id)`
- `KEY idx_loja_custos_entrada (entrada_id)`
- `CONSTRAINT fk_loja_custos_entrada FOREIGN KEY (entrada_id) REFERENCES loja_entradas (id) ON DELETE CASCADE ON UPDATE CASCADE`

### 8.3. Tabela `loja_itens`

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT |
| `entrada_id` | `BIGINT UNSIGNED` | NOT NULL, FK → `loja_entradas.id` |
| `nome` | `VARCHAR(180)` | NOT NULL |
| `descricao` | `TEXT` | NULL |
| `codigo` | `VARCHAR(80)` | NULL |
| `quantidade` | `INT UNSIGNED` | NOT NULL, DEFAULT 1 |
| `custo_unitario_base` | `DECIMAL(12,2)` | NOT NULL, DEFAULT 0.00 |
| `custo_adicional_rateado` | `DECIMAL(12,2)` | NOT NULL, DEFAULT 0.00 |
| `custo_total_real` | `DECIMAL(12,2)` | NOT NULL, DEFAULT 0.00 |
| `custo_unitario_real` | `DECIMAL(12,2)` | NOT NULL, DEFAULT 0.00 |
| `status` | `ENUM('disponivel','em_transporte','pre_venda')` | NOT NULL |
| `observacoes` | `TEXT` | NULL |
| `criado_em` | `DATETIME` | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| `atualizado_em` | `DATETIME` | NULL, ON UPDATE CURRENT_TIMESTAMP |

Índices:

- `PRIMARY KEY (id)`
- `KEY idx_loja_itens_entrada (entrada_id)`
- `KEY idx_loja_itens_status (status)`
- `KEY idx_loja_itens_nome (nome)`
- `KEY idx_loja_itens_codigo (codigo)`
- `CONSTRAINT fk_loja_itens_entrada FOREIGN KEY (entrada_id) REFERENCES loja_entradas (id) ON DELETE CASCADE ON UPDATE CASCADE`

### 8.4. Tabela `loja_item_fornecedores`

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT |
| `item_id` | `BIGINT UNSIGNED` | NOT NULL, FK → `loja_itens.id` |
| `fornecedor` | `VARCHAR(150)` | NOT NULL |
| `url` | `VARCHAR(500)` | NOT NULL |
| `preco_custo` | `DECIMAL(12,2)` | NOT NULL |
| `selecionado` | `TINYINT(1)` | NOT NULL, DEFAULT 0 |
| `observacoes` | `VARCHAR(255)` | NULL |
| `criado_em` | `DATETIME` | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| `atualizado_em` | `DATETIME` | NULL, ON UPDATE CURRENT_TIMESTAMP |

Índices:

- `PRIMARY KEY (id)`
- `KEY idx_loja_ofertas_item (item_id)`
- `KEY idx_loja_ofertas_fornecedor (fornecedor)`
- `CONSTRAINT fk_loja_ofertas_item FOREIGN KEY (item_id) REFERENCES loja_itens (id) ON DELETE CASCADE ON UPDATE CASCADE`

Regra adicional: no máximo uma oferta com `selecionado = 1` por item. A regra deve ser garantida no service e validada em transação.

---

## 9. Rotas

Criar `routes/loja.php` e carregá-lo em `public/index.php` junto com `web.php` e `admin.php`.

Todas as rotas exigem `AuthMiddleware`. A autorização por perfil não é necessária além da autenticação.

| Método | Rota | Ação |
|---|---|---|
| GET | `/admin/loja` | Redirecionar para `/admin/loja/entradas` |
| GET | `/admin/loja/entradas` | Listar entradas |
| GET | `/admin/loja/entradas/criar` | Formulário de nova entrada |
| POST | `/admin/loja/entradas` | Criar compra direta ou previsão |
| GET | `/admin/loja/entradas/{id}` | Detalhes da entrada |
| POST | `/admin/loja/entradas/{id}` | Atualizar dados da entrada |
| POST | `/admin/loja/entradas/{id}/recebido` | Alternar recebimento |
| POST | `/admin/loja/entradas/{id}/excluir` | Excluir entrada |
| POST | `/admin/loja/entradas/{id}/custos` | Adicionar custo adicional |
| POST | `/admin/loja/entradas/{id}/custos/{custoId}/excluir` | Excluir custo adicional |
| POST | `/admin/loja/entradas/{id}/itens` | Adicionar item |
| POST | `/admin/loja/itens/{itemId}/excluir` | Excluir item |
| POST | `/admin/loja/itens/{itemId}/fornecedores` | Adicionar oferta de fornecedor |
| POST | `/admin/loja/itens/{itemId}/fornecedores/{ofertaId}/selecionar` | Selecionar oferta |
| POST | `/admin/loja/itens/{itemId}/fornecedores/{ofertaId}/excluir` | Excluir oferta |
| GET | `/admin/loja/estoque` | Listar estoque com filtros |

Observações:

- O formulário de criação deve permitir escolher o modo `direta` ou `previsao`.
- Para `direta`, exibir campos de link, recebimento e rastreio.
- Para `previsao`, ocultar esses campos e exibir apenas descrição e observações.
- A criação de item de previsão deve incluir a primeira oferta de fornecedor.
- Todas as rotas `POST` usam o CSRF global já existente.

---

## 10. Estrutura de arquivos

Criar:

```text
app/
├── Controllers/
│   └── Loja/
│       ├── EntradaController.php
│       └── EstoqueController.php
├── Models/
│   └── Loja/
│       ├── Entrada.php
│       ├── EntradaCusto.php
│       ├── ItemEstoque.php
│       └── ItemFornecedor.php
├── Services/
│   ├── AuditService.php
│   └── Loja/
│       ├── EntradaService.php
│       ├── EstoqueService.php
│       └── PrevisaoService.php
└── Views/
    └── loja/
        ├── partials/
        │   └── tabs.php
        ├── entradas/
        │   ├── index.php
        │   ├── criar.php
        │   └── detalhe.php
        └── estoque/
            └── index.php

database/migrations/002_create_loja_tables.sql
routes/loja.php
public/assets/css/loja.css
public/assets/js/loja.js
Manual/06-MODULO-LOJA.md
scripts/testar-calculo-custos.php
```

Alterar:

- `public/index.php` — carregar `routes/loja.php`.
- `app/Views/layouts/admin.php` — incluir menu Loja e suporte a estilos/scripts adicionais por página.
- `app/Services/UserService.php` — reutilizar o novo `AuditService` em vez de manter lógica de auditoria duplicada.
- `Manual/README.md`, `01-ARQUITETURA.md`, `03-BANCO-DE-DADOS.md`, `05-INTERFACE.md` e `CHANGELOG.md`.
- `README.md` da raiz.

Não criar pastas de uploads, imagens de produto ou módulos de vendas nesta etapa.

---

## 11. Interface e experiência do usuário

### 11.1. Aba Entrada de produtos

- Cabeçalho com botão **Nova entrada**.
- Filtros por modo (`Todos`, `Compra direta`, `Previsão`) e por recebimento (`Todos`, `Recebidos`, `Não recebidos`).
- Pesquisa por descrição de origem, link ou código de rastreio.
- Tabela responsiva com:
  - descrição/origem;
  - modo;
  - situação de recebimento;
  - quantidade de itens;
  - total de custos adicionais;
  - total real;
  - data;
  - ações (abrir, excluir).
- Paginação de 20 registros.
- Estado vazio orientando a criar a primeira entrada.

### 11.2. Nova entrada

- Seleção visual do modo: **Compra direta** e **Previsão de compra**.
- Campos exibidos dinamicamente conforme o modo, com JavaScript complementar.
- Botões **Salvar** e **Cancelar**.
- Feedback de validação no backend com repopulação dos campos.

### 11.3. Detalhes da entrada

Cabeçalho:

- descrição/origem;
- modo;
- data de criação;
- usuário criador;
- situação;
- link de compra (quando direto);
- código de rastreio (quando direto);
- ações: editar dados, alternar recebimento, excluir entrada.

Resumo financeiro (compra direta):

- total base;
- total de custos adicionais;
- total real;
- quantidade de itens.

Seção de custos adicionais (somente compra direta):

- formulário para adicionar descrição e valor;
- lista de custos com ação de excluir;
- total de custos adicionais.

Seção de itens:

- formulário de inclusão;
- lista com nome, código, quantidade, custo base, custo rateado, custo real e situação;
- ação de excluir;
- para previsão, exibir ofertas de fornecedores por item.

Para previsão, cada item deve exibir:

- lista de ofertas com fornecedor, URL, preço e indicação de selecionada;
- destaque para a menor oferta;
- ação de selecionar oferta;
- ação de excluir oferta;
- formulário para adicionar nova oferta;
- indicação **Custo a definir** quando nenhuma oferta estiver selecionada.

### 11.4. Aba Estoque

- Cards de resumo com dados reais.
- Filtros em pills ou abas: Todos, Disponível em estoque, Em transporte, Em pré-venda.
- Campo de pesquisa.
- Tabela responsiva com as colunas definidas na seção 7.
- Paginação preservando filtros.
- Estado vazio por ausência de dados ou por filtro sem resultado.

### 11.5. Identidade visual

- Manter a paleta e os componentes da Etapa 01.
- Azul escuro em cabeçalhos e elementos institucionais.
- Amarelo em ações principais, com moderação.
- Cards brancos, bordas discretas e espaçamentos consistentes.
- Badges para situações:
  - Disponível → verde;
  - Em transporte → azul;
  - Em pré-venda → amarelo.
- Layout responsivo, com tabelas roláveis e formulários empilhados no mobile.
- Confirmação para exclusões.
- Nenhum link ou botão sem ação implementada.

---

## 12. Validação e segurança

Validar no backend:

- campos obrigatórios;
- tamanhos máximos;
- URLs `http`/`https`;
- quantidades inteiras e positivas;
- valores monetários não negativos;
- formato monetário;
- modo válido;
- existência da entrada, item, custo e oferta;
- oferta pertencente ao item informado;
- item pertencente à entrada informada;
- unicidade de URL por item;
- limite de uma oferta selecionada por item;
- proibição de custos adicionais em previsão nesta etapa;
- proibição de recebimento em previsão.

Segurança:

- Todas as consultas com dados externos usam prepared statements.
- Todo `POST` exige CSRF válido.
- Toda saída HTML é escapada com `e()`.
- Não expor caminhos internos, SQL ou stack traces em produção.
- Não registrar URLs completas na auditoria, pois podem conter parâmetros sensíveis.
- Não registrar dados de cartão, senhas ou tokens.
- Operações de exclusão usam `POST` e confirmação.
- Não confiar em campos ocultos ou validações JavaScript.
- Utilizar transações PDO em operações que alteram entrada, itens e custos em conjunto.

---

## 13. Auditoria

Criar `App\Services\AuditService` com um método reutilizável de registro e refatorar o `UserService` para usá-lo.

Registrar, no mínimo:

- `loja_entrada_criada`
- `loja_entrada_atualizada`
- `loja_entrada_excluida`
- `loja_entrada_recebida`
- `loja_entrada_reaberta`
- `loja_custo_adicionado`
- `loja_custo_excluido`
- `loja_item_adicionado`
- `loja_item_excluido`
- `loja_fornecedor_adicionado`
- `loja_fornecedor_excluido`
- `loja_fornecedor_selecionado`

As descrições devem conter IDs e nomes, sem URLs completas e sem dados sensíveis.

---

## 14. Documentação obrigatória

Criar `Manual/06-MODULO-LOJA.md` contendo:

- visão geral do módulo;
- conceitos;
- modos de entrada;
- regras de custo e rateio com exemplo;
- regras de previsão e ofertas;
- situação dos itens e filtros do estoque;
- tabelas e relacionamentos;
- rotas;
- permissões;
- telas e componentes;
- auditoria;
- limitações;
- testes realizados.

Atualizar:

- `Manual/README.md` — índice e situação atual.
- `Manual/01-ARQUITETURA.md` — novos controllers, models, services, rotas e views.
- `Manual/03-BANCO-DE-DADOS.md` — novas tabelas, índices e regras.
- `Manual/05-INTERFACE.md` — abas, telas, componentes e badges.
- `Manual/CHANGELOG.md` — etapa, arquivos, funcionalidades, testes e pendências.
- `README.md` — resumo do módulo e comandos, se necessário.

A documentação deve refletir exatamente o código implementado e não deve documentar funcionalidades inexistentes.

---

## 15. Critérios de aceite

### Estrutura e integração

- [ ] Menu Loja adicionado com as abas Entrada de produtos e Estoque.
- [ ] Rotas carregadas e protegidas por autenticação.
- [ ] Migration `002` executável e idempotente.
- [ ] Tabelas e relacionamentos criados corretamente.
- [ ] Núcleo da Etapa 01 preservado.

### Compra direta

- [ ] Cadastro com descrição de origem, link, recebimento e rastreio.
- [ ] Alternância de recebimento atualiza a situação dos itens.
- [ ] Custos adicionais podem ser incluídos e excluídos.
- [ ] Rateio de custos adicionais calculado corretamente.
- [ ] Custo unitário real exibido por item.
- [ ] Total base, adicionais e real exibidos na entrada.
- [ ] Itens entram como `em_transporte` ou `disponivel` conforme a entrada.

### Previsão de compra

- [ ] Cadastro de previsão sem link/recebimento.
- [ ] Itens cadastrados individualmente.
- [ ] Primeira oferta obrigatória na criação do item.
- [ ] Múltiplos fornecedores por item.
- [ ] Seleção de uma única oferta por item.
- [ ] Custo base/real atualizado ao selecionar oferta.
- [ ] Estado **Custo a definir** quando não houver seleção.
- [ ] Itens aparecem como `pre_venda`.

### Estoque

- [ ] Filtros Todos, Disponível, Em transporte e Pré-venda.
- [ ] Pesquisa por nome/código.
- [ ] Resumo com dados reais.
- [ ] Paginação preservando filtros.
- [ ] Estados vazios.
- [ ] Ação para abrir a entrada de origem.

### Segurança e qualidade

- [ ] CSRF em todas as operações de escrita.
- [ ] Validação de backend em todos os formulários.
- [ ] Prepared statements em todas as consultas com dados externos.
- [ ] Escape de saída HTML.
- [ ] Exclusões com confirmação e `POST`.
- [ ] Auditoria registrada sem dados sensíveis.
- [ ] Transações nas operações compostas.
- [ ] Nenhum botão ou link sem ação.

### Documentação

- [ ] `Manual/06-MODULO-LOJA.md` criado.
- [ ] Documentos existentes atualizados.
- [ ] Changelog atualizado.
- [ ] Instruções de execução e teste documentadas.

---

## 16. Testes mínimos

### 16.1. Cálculo de custos

Criar `scripts/testar-calculo-custos.php` para validar, no mínimo:

1. Rateio proporcional ao subtotal.
2. Rateio quando o subtotal geral é zero (por quantidade).
3. Distribuição do resíduo de arredondamento.
4. Soma das parcelas igual ao total de adicionais.
5. Item com quantidade maior que 1.
6. Entrada sem custos adicionais.
7. Entrada sem itens.

O script deve executar via CLI e retornar código de saída diferente de zero em caso de falha.

### 16.2. Testes HTTP

Validar no navegador ou por script HTTP:

- Acesso ao módulo sem autenticação → redireciona para login.
- Acesso autenticado como `admin` e como `super_admin`.
- Criação de compra direta.
- Edição de entrada.
- Alternância de recebimento.
- Inclusão e exclusão de custo.
- Inclusão e exclusão de item.
- Verificação dos status no estoque.
- Criação de previsão.
- Inclusão de item com oferta.
- Inclusão de segunda oferta.
- Seleção de oferta.
- Exclusão da oferta selecionada.
- Filtros do estoque.
- Pesquisa.
- Paginação.
- CSRF ausente → 419.
- Rotas inexistentes → 404.
- Exclusão de entrada remove itens e custos.

### 16.3. Verificações finais

- `php -l` em todos os arquivos PHP.
- `composer validate`.
- Execução da migration em banco limpo.
- Execução da migration novamente para verificar idempotência.
- Conferência de logs e auditoria.

---

## 17. Ordem de implementação sugerida

1. Criar a migration `002` e executá-la em banco de teste.
2. Criar ou extrair o `AuditService` e ajustar o `UserService`.
3. Criar os models do módulo.
4. Implementar os services de entrada, previsão, estoque e cálculo.
5. Criar o script de teste de cálculo.
6. Criar controllers e rotas.
7. Carregar `routes/loja.php` no `index.php`.
8. Criar views, partials, CSS e JS.
9. Atualizar o layout administrativo e o menu.
10. Aplicar validações, transações e auditoria.
11. Executar testes manuais e automatizados.
12. Atualizar toda a documentação em `Manual/`.
13. Apresentar o relatório final.

---

## 18. Decisões registradas

1. **Custo adicional somente em compra direta nesta etapa.** A previsão ainda não representa compra consolidada.
2. **Rateio proporcional ao subtotal base.** Quando o subtotal for zero, o rateio será proporcional à quantidade.
3. **Cálculo em centavos inteiros.** Evita erros de arredondamento com ponto flutuante.
4. **Uma única oferta selecionada por item.** A seleção define o custo base e o custo real do item de previsão.
5. **Previsão permanece em `pre_venda` nesta etapa.** A conversão em compra direta será tratada futuramente.
6. **Fornecedor como texto na oferta.** Não haverá cadastro de fornecedor como entidade nesta etapa.
7. **Sem imagens, categorias ou movimentações de estoque.** Esses itens ficam para etapas futuras.
8. **Ambos os perfis administrativos acessam o módulo Loja.** Não há restrição exclusiva ao super-admin.
9. **Paginação fixa de 20 registros.** Não será criada configuração adicional por ambiente.
10. **A URL completa da oferta não será registrada na auditoria.** Apenas nome do fornecedor, item e IDs.

---

## 19. Entrega final da IA

Ao concluir a implementação, apresentar:

1. Resumo do que foi implementado.
2. Arquivos criados e alterados.
3. Estrutura de banco criada.
4. Rotas disponíveis.
5. Regras de cálculo implementadas e exemplos validados.
6. Permissões aplicadas.
7. Testes realizados e resultados.
8. Pendências ou limitações.
9. Documentos criados ou atualizados em `Manual/`.
10. Comandos necessários para executar migrations, testes e servidor.

Não declarar a etapa concluída se houver critérios de aceite pendentes. Identificar claramente qualquer item não testado por falta de ambiente ou credenciais.
