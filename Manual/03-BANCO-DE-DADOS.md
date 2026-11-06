# 03 — Banco de Dados

## Configuração

| Item | Valor |
|---|---|
| Banco | `loja_carrinhos` |
| Charset | `utf8mb4` |
| Collation | `utf8mb4_unicode_ci` |
| Engine | InnoDB |
| Driver | PDO MySQL |
| Prepared statements | Sim, com emulação desativada |
| Fetch padrão | `PDO::FETCH_ASSOC` |

As credenciais vêm exclusivamente do `.env` (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). Nenhum dado de acesso fica no SQL ou no código-fonte.

## Migrations

Arquivos atuais:

- `database/migrations/001_create_initial_tables.sql` — banco, `usuarios` e `logs_auditoria`.
- `database/migrations/002_create_loja_tables.sql` — tabelas do módulo Loja.
- `database/migrations/003_alter_money_precision.sql` — precisão monetária para `DECIMAL(12,3)`.
- `database/migrations/004_create_produtos_tables.sql` — cadastros auxiliares, `produtos` e `produtos_imagens`.
- `database/migrations/005_add_produto_id_to_loja_itens.sql` — vínculo entre itens de estoque e produtos.
- `database/migrations/006_create_loja_item_imagens.sql` — fotos dos itens de entrada/estoque.
- `database/migrations/007_add_catalogo_to_loja_itens.sql` — categoria, marca, fabricante, coleção, escala e ano nos itens.

O runner garante a existência do banco configurado. Cada migration seleciona o banco com `USE` e executa as mudanças necessárias:

- criação de tabelas com `CREATE TABLE IF NOT EXISTS`;
- criação/ajuste de colunas com `ALTER TABLE`;
- criação de índices e foreign keys.

Execução:

```bash
php scripts/migrate.php
```

O script `scripts/migrate.php` conecta sem selecionar banco, garante a existência do banco configurado e cria a tabela de controle `schema_migrations`. Em seguida, lê todos os arquivos `*.sql` de `database/migrations/` em ordem e executa apenas os que ainda não foram registrados.

Cada migration aplicada é registrada em `schema_migrations` com nome e data. Isso permite usar `ALTER TABLE` (como em `003` e `005`) sem reexecutar comandos já aplicados.

> Novas mudanças devem gerar novos arquivos em `database/migrations/`. Não edite migrations já aplicadas em ambientes existentes.

## Tabela `usuarios`

Armazena super-administradores e administradores operacionais.

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `INT UNSIGNED` | PK, auto incremento |
| `nome` | `VARCHAR(150)` | NOT NULL |
| `email` | `VARCHAR(180)` | NOT NULL, UNIQUE |
| `senha` | `VARCHAR(255)` | NOT NULL, hash gerado por `password_hash()` |
| `tipo` | `ENUM('super_admin','admin')` | NOT NULL |
| `status` | `ENUM('ativo','inativo')` | NOT NULL, padrão `ativo` |
| `ultimo_acesso` | `DATETIME` | NULL |
| `criado_em` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |
| `atualizado_em` | `DATETIME` | NULL, atualização automática |

Índices:

- `PRIMARY KEY (id)`
- `UNIQUE KEY uk_usuarios_email (email)`
- `KEY idx_usuarios_tipo (tipo)`
- `KEY idx_usuarios_status (status)`

Regras de negócio associadas:

- Apenas um `super_admin` pode existir; a regra é aplicada no `UserService` e no script de instalação.
- Administradores comuns são sempre criados como `admin` e `ativo`.
- O super-admin não pode ser editado, inativado ou excluído pela interface de gerenciamento.
- Senhas nunca são armazenadas em texto puro e nunca são registradas em logs.
- O e-mail é normalizado para minúsculas antes de validação e persistência.

## Tabela `logs_auditoria`

Registra eventos relevantes de segurança e gestão.

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto incremento |
| `usuario_id` | `INT UNSIGNED` | NULL, FK para `usuarios.id` |
| `acao` | `VARCHAR(100)` | NOT NULL |
| `descricao` | `TEXT` | NULL |
| `ip` | `VARCHAR(45)` | NULL |
| `criado_em` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |

Índices e relacionamento:

- `PRIMARY KEY (id)`
- `KEY idx_logs_auditoria_usuario (usuario_id)`
- `KEY idx_logs_auditoria_criado (criado_em)`
- `CONSTRAINT fk_logs_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE`

A foreign key garante que, ao excluir um administrador, os registros de auditoria sejam preservados com `usuario_id = NULL`.

Ações registradas atualmente:

- `instalacao_super_admin`
- `login_sucesso`
- `login_falha`
- `login_bloqueado`
- `logout`
- `admin_criado`
- `admin_editado`
- `admin_ativado`
- `admin_inativado`
- `admin_excluido`
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
- `produto_criado`
- `produto_atualizado`
- `produto_status`
- `produto_excluido`
- `configuracao_criada`
- `configuracao_atualizada`
- `configuracao_status`
- `configuracao_excluida`
- `imagem_produto_adicionada`
- `imagem_produto_principal`
- `imagem_produto_reordenada`
- `imagem_produto_excluida`
- `imagem_produto_substituida`

Nenhum log registra senhas, hashes de senha, tokens de sessão, a chave de instalação ou URLs completas de fornecedores.

## Tabelas do módulo Loja

### `loja_entradas`

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto incremento |
| `modo` | `ENUM('direta','previsao')` | NOT NULL |
| `descricao_origem` | `VARCHAR(180)` | NOT NULL |
| `link_compra` | `VARCHAR(500)` | NULL |
| `recebido` | `TINYINT(1)` | NOT NULL, padrão 0 |
| `codigo_rastreio` | `VARCHAR(120)` | NULL |
| `observacoes` | `TEXT` | NULL |
| `criado_por` | `INT UNSIGNED` | NULL, FK para `usuarios.id` |
| `criado_em` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |
| `atualizado_em` | `DATETIME` | NULL, atualização automática |

Índices: `modo`, `recebido`, `criado_em`, `criado_por`. Foreign key com `ON DELETE SET NULL`.

### `loja_entrada_custos`

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto incremento |
| `entrada_id` | `BIGINT UNSIGNED` | NOT NULL, FK para `loja_entradas.id` |
| `descricao` | `VARCHAR(150)` | NOT NULL |
| `valor` | `DECIMAL(12,3)` | NOT NULL |
| `criado_em` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |
| `atualizado_em` | `DATETIME` | NULL, atualização automática |

Foreign key com `ON DELETE CASCADE`.

### `loja_itens`

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto incremento |
| `entrada_id` | `BIGINT UNSIGNED` | NOT NULL, FK para `loja_entradas.id` |
| `produto_id` | `BIGINT UNSIGNED` | NULL, FK para `produtos.id` — legado, não utilizado pela interface |
| `categoria_id` | `INT UNSIGNED` | NULL, FK para `categorias.id` |
| `marca_id` | `INT UNSIGNED` | NULL, FK para `marcas.id` |
| `fabricante_id` | `INT UNSIGNED` | NULL, FK para `fabricantes.id` |
| `colecao_id` | `INT UNSIGNED` | NULL, FK para `colecoes.id` |
| `escala_id` | `INT UNSIGNED` | NULL, FK para `escalas.id` |
| `ano_fabricacao` | `SMALLINT UNSIGNED` | NULL |
| `nome` | `VARCHAR(180)` | NOT NULL |
| `descricao` | `TEXT` | NULL |
| `codigo` | `VARCHAR(80)` | NULL |
| `quantidade` | `INT UNSIGNED` | NOT NULL, padrão 1 |
| `custo_unitario_base` | `DECIMAL(12,3)` | NOT NULL, padrão 0,000 |
| `custo_adicional_rateado` | `DECIMAL(12,3)` | NOT NULL, padrão 0,000 |
| `custo_total_real` | `DECIMAL(12,3)` | NOT NULL, padrão 0,000 |
| `custo_unitario_real` | `DECIMAL(12,3)` | NOT NULL, padrão 0,000 |
| `status` | `ENUM('disponivel','em_transporte','pre_venda')` | NOT NULL |
| `observacoes` | `TEXT` | NULL |
| `criado_em` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |
| `atualizado_em` | `DATETIME` | NULL, atualização automática |

Índices: `entrada_id`, `produto_id`, `categoria_id`, `marca_id`, `fabricante_id`, `colecao_id`, `escala_id`, `status`, `nome`, `codigo`.

Foreign keys:

- `entrada_id` com `ON DELETE CASCADE`;
- `produto_id` com `ON DELETE SET NULL` (legado);
- `categoria_id`, `marca_id`, `fabricante_id`, `colecao_id` e `escala_id` com `ON DELETE SET NULL`.

### `loja_item_fornecedores`

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto incremento |
| `item_id` | `BIGINT UNSIGNED` | NOT NULL, FK para `loja_itens.id` |
| `fornecedor` | `VARCHAR(150)` | NOT NULL |
| `url` | `VARCHAR(500)` | NOT NULL |
| `preco_custo` | `DECIMAL(12,3)` | NOT NULL |
| `selecionado` | `TINYINT(1)` | NOT NULL, padrão 0 |
| `observacoes` | `VARCHAR(255)` | NULL |
| `criado_em` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |
| `atualizado_em` | `DATETIME` | NULL, atualização automática |

Índices: `item_id`, `fornecedor`. Foreign key com `ON DELETE CASCADE`. A regra de no máximo uma oferta selecionada por item é aplicada no `PrevisaoService`.

### `loja_item_imagens`

Fotos dos itens de entrada/estoque.

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto incremento |
| `item_id` | `BIGINT UNSIGNED` | NOT NULL, FK para `loja_itens.id` |
| `caminho_imagem` | `VARCHAR(255)` | NOT NULL, caminho relativo em `storage/uploads` |
| `nome_original` | `VARCHAR(180)` | NOT NULL |
| `imagem_principal` | `TINYINT(1)` | NOT NULL, padrão 0 |
| `ordem_exibicao` | `INT UNSIGNED` | NOT NULL, padrão 0 |
| `criado_em` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |

Foreign key com `ON DELETE CASCADE`. Índices por item, principal e ordem. Os arquivos ficam em `storage/uploads/loja_itens/` e são servidos pela rota autenticada `/admin/loja/itens/imagem/{id}`.

## Tabela `schema_migrations`

Tabela de controle criada e gerenciada por `scripts/migrate.php`.

| Campo | Tipo | Regra |
|---|---|---|
| `migration` | `VARCHAR(255)` | PK, nome do arquivo de migration |
| `applied_at` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |

Registra quais migrations já foram aplicadas, permitindo executar o comando de migration repetidamente sem reaplicar `ALTER TABLE`.

## Tabelas legadas do módulo de Produtos

> O cadastro de produtos foi removido da interface. As tabelas `produtos` e `produtos_imagens` permanecem no banco para preservar dados históricos, mas não são mais utilizadas pelas rotas administrativas. As informações de catálogo agora ficam diretamente em `loja_itens`.

### Cadastros auxiliares

As tabelas `categorias`, `marcas`, `fabricantes`, `colecoes` e `escalas` possuem:

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `INT UNSIGNED` | PK, auto incremento |
| `nome` | `VARCHAR(120)` | NOT NULL, UNIQUE |
| `descricao` | `VARCHAR(255)` | NULL |
| `status` | `ENUM('ativo','inativo')` | NOT NULL, padrão `ativo` |
| `criado_em` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |
| `atualizado_em` | `DATETIME` | NULL, atualização automática |

Detalhes e regras de exclusão em [10-CATEGORIAS-MARCAS-COLECOES.md](10-CATEGORIAS-MARCAS-COLECOES.md).

### `produtos`

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto incremento |
| `nome` | `VARCHAR(180)` | NOT NULL |
| `sku` | `VARCHAR(80)` | NULL, UNIQUE |
| `descricao_resumida` | `VARCHAR(255)` | NULL |
| `descricao_completa` | `TEXT` | NULL |
| `categoria_id` | `INT UNSIGNED` | NOT NULL, FK para `categorias.id` |
| `marca_id` | `INT UNSIGNED` | NULL, FK para `marcas.id` |
| `fabricante_id` | `INT UNSIGNED` | NULL, FK para `fabricantes.id` |
| `colecao_id` | `INT UNSIGNED` | NULL, FK para `colecoes.id` |
| `escala_id` | `INT UNSIGNED` | NULL, FK para `escalas.id` |
| `modelo` | `VARCHAR(120)` | NULL |
| `cor_predominante` | `VARCHAR(60)` | NULL |
| `ano_fabricacao` | `SMALLINT UNSIGNED` | NULL |
| `material_predominante` | `VARCHAR(80)` | NULL |
| `codigo_referencia_fabricante` | `VARCHAR(80)` | NULL |
| `status` | `ENUM('ativo','inativo')` | NOT NULL, padrão `ativo` |
| `fabricante_veiculo` | `VARCHAR(120)` | NULL |
| `modelo_veiculo` | `VARCHAR(120)` | NULL |
| `ano_veiculo` | `SMALLINT UNSIGNED` | NULL |
| `tipo_veiculo` | `VARCHAR(60)` | NULL |
| `edicao_especial` | `TINYINT(1)` | NOT NULL, padrão 0 |
| `unidades_edicao` | `INT UNSIGNED` | NULL |
| `lacrado` | `ENUM('lacrado','aberto')` | NULL |
| `embalagem_original` | `TINYINT(1)` | NULL |
| `nivel_conservacao` | `VARCHAR(80)` | NULL |
| `observacoes` | `TEXT` | NULL |
| `preco_venda` | `DECIMAL(12,3)` | NULL |
| `preco_visivel` | `TINYINT(1)` | NOT NULL, padrão 0 |
| `destaque` | `TINYINT(1)` | NOT NULL, padrão 0 |
| `criado_em` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |
| `atualizado_em` | `DATETIME` | NULL, atualização automática |

Foreign keys:

- `categoria_id` → `categorias.id` com `ON DELETE RESTRICT`.
- `marca_id`, `fabricante_id`, `colecao_id`, `escala_id` com `ON DELETE SET NULL` como salvaguarda; a exclusão é bloqueada no service quando há produtos.

### `produtos_imagens`

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto incremento |
| `produto_id` | `BIGINT UNSIGNED` | NOT NULL, FK para `produtos.id` |
| `caminho_imagem` | `VARCHAR(255)` | NOT NULL, caminho relativo em `storage/uploads` |
| `nome_original` | `VARCHAR(180)` | NOT NULL |
| `imagem_principal` | `TINYINT(1)` | NOT NULL, padrão 0 |
| `ordem_exibicao` | `INT UNSIGNED` | NOT NULL, padrão 0 |
| `criado_em` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |

Foreign key com `ON DELETE CASCADE`. Índices por produto, principal e ordem. A regra de uma única imagem principal é aplicada no `ProdutoImagemService`.

## Integridade e consistência

- Engine InnoDB em todas as tabelas.
- Charset e collation uniformes.
- E-mail único no banco e validado no service.
- Foreign key com `ON DELETE SET NULL` para auditoria.
- Operações de criação, edição, status e exclusão são validadas no backend antes da persistência.
- Todas as consultas que recebem dados externos usam prepared statements.
- Valores monetários usam `DECIMAL(12,3)` e cálculos internos em milésimos inteiros.
- Imagens de produto ficam fora do document root e são servidas por rota controlada.
- Migrations aplicadas são registradas em `schema_migrations`.

## Verificação rápida

```sql
USE loja_carrinhos;
SHOW TABLES;
DESCRIBE usuarios;
DESCRIBE logs_auditoria;
DESCRIBE loja_entradas;
DESCRIBE loja_entrada_custos;
DESCRIBE loja_itens;
DESCRIBE loja_item_fornecedores;
DESCRIBE loja_item_imagens;
DESCRIBE categorias;
DESCRIBE marcas;
DESCRIBE fabricantes;
DESCRIBE colecoes;
DESCRIBE escalas;
DESCRIBE produtos;
DESCRIBE produtos_imagens;
DESCRIBE schema_migrations;
SHOW CREATE TABLE produtos_imagens\G
```

Resultado esperado: tabelas `usuarios`, `logs_auditoria`, `loja_entradas`, `loja_entrada_custos`, `loja_itens`, `loja_item_fornecedores`, `loja_item_imagens`, `categorias`, `marcas`, `fabricantes`, `colecoes`, `escalas`, `produtos`, `produtos_imagens` e `schema_migrations`, todas em InnoDB/utf8mb4.

As colunas monetárias do módulo Loja e o `preco_venda` do produto devem aparecer como `decimal(12,3)`.

## Dados iniciais

Não há seeds nesta etapa. O super-administrador é criado exclusivamente pelo script CLI:

```bash
php scripts/create-super-admin.php
```

Nenhum usuário ou senha padrão é inserido pelo SQL.
