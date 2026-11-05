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

Cada arquivo:

1. Garante a existência do banco com `CREATE DATABASE IF NOT EXISTS`.
2. Seleciona o banco com `USE`.
3. Cria as tabelas com `CREATE TABLE IF NOT EXISTS`.
4. Define chaves, índices e foreign keys.

Execução:

```bash
php scripts/migrate.php
```

O script `scripts/migrate.php` conecta sem selecionar banco, lê todos os arquivos `*.sql` de `database/migrations/` em ordem, remove comentários de linha e executa cada comando com PDO. Utiliza as configurações do `.env`. Migrations já aplicadas podem ser repetidas com segurança para as tabelas existentes.

> A idempotência com `CREATE TABLE IF NOT EXISTS` é suficiente para a criação inicial, mas **não substitui** um sistema de migrations versionadas para alterações futuras. Novas mudanças devem gerar novos arquivos em `database/migrations/`.

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
| `valor` | `DECIMAL(12,2)` | NOT NULL |
| `criado_em` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |
| `atualizado_em` | `DATETIME` | NULL, atualização automática |

Foreign key com `ON DELETE CASCADE`.

### `loja_itens`

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto incremento |
| `entrada_id` | `BIGINT UNSIGNED` | NOT NULL, FK para `loja_entradas.id` |
| `nome` | `VARCHAR(180)` | NOT NULL |
| `descricao` | `TEXT` | NULL |
| `codigo` | `VARCHAR(80)` | NULL |
| `quantidade` | `INT UNSIGNED` | NOT NULL, padrão 1 |
| `custo_unitario_base` | `DECIMAL(12,2)` | NOT NULL, padrão 0,00 |
| `custo_adicional_rateado` | `DECIMAL(12,2)` | NOT NULL, padrão 0,00 |
| `custo_total_real` | `DECIMAL(12,2)` | NOT NULL, padrão 0,00 |
| `custo_unitario_real` | `DECIMAL(12,2)` | NOT NULL, padrão 0,00 |
| `status` | `ENUM('disponivel','em_transporte','pre_venda')` | NOT NULL |
| `observacoes` | `TEXT` | NULL |
| `criado_em` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |
| `atualizado_em` | `DATETIME` | NULL, atualização automática |

Índices: `entrada_id`, `status`, `nome`, `codigo`. Foreign key com `ON DELETE CASCADE`.

### `loja_item_fornecedores`

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto incremento |
| `item_id` | `BIGINT UNSIGNED` | NOT NULL, FK para `loja_itens.id` |
| `fornecedor` | `VARCHAR(150)` | NOT NULL |
| `url` | `VARCHAR(500)` | NOT NULL |
| `preco_custo` | `DECIMAL(12,2)` | NOT NULL |
| `selecionado` | `TINYINT(1)` | NOT NULL, padrão 0 |
| `observacoes` | `VARCHAR(255)` | NULL |
| `criado_em` | `DATETIME` | NOT NULL, padrão `CURRENT_TIMESTAMP` |
| `atualizado_em` | `DATETIME` | NULL, atualização automática |

Índices: `item_id`, `fornecedor`. Foreign key com `ON DELETE CASCADE`. A regra de no máximo uma oferta selecionada por item é aplicada no `PrevisaoService`.

## Integridade e consistência

- Engine InnoDB em todas as tabelas.
- Charset e collation uniformes.
- E-mail único no banco e validado no service.
- Foreign key com `ON DELETE SET NULL` para auditoria.
- Operações de criação, edição, status e exclusão são validadas no backend antes da persistência.
- Todas as consultas que recebem dados externos usam prepared statements.

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
SHOW CREATE TABLE loja_item_fornecedores\G
```

Resultado esperado: tabelas `usuarios`, `logs_auditoria`, `loja_entradas`, `loja_entrada_custos`, `loja_itens` e `loja_item_fornecedores`, todas em InnoDB/utf8mb4.

## Dados iniciais

Não há seeds nesta etapa. O super-administrador é criado exclusivamente pelo script CLI:

```bash
php scripts/create-super-admin.php
```

Nenhum usuário ou senha padrão é inserido pelo SQL.
