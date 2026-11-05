# 02 — Instalação

## Requisitos

- PHP **8.2 ou superior** com as extensões `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` e `json`.
- Composer **2.x**.
- MySQL **8** ou MariaDB compatível.
- Apache com `mod_rewrite` (opcional; o servidor embutido do PHP também funciona para desenvolvimento).

Verificação rápida:

```bash
php -v
php -m
composer --version
```

## 1. Dependências PHP

Na raiz do projeto:

```bash
composer install
```

Dependências instaladas nesta entrega:

- `vlucas/phpdotenv` 5.8.0
- dependências transitivas: `phpoption/phpoption`, `graham-campbell/result-type`, `symfony/polyfill-ctype`, `symfony/polyfill-mbstring`, `symfony/polyfill-php80`

O autoload PSR-4 mapeia `App\` para `app/` e carrega `app/Helpers/functions.php`.

## 2. Variáveis de ambiente

Copie o modelo e ajuste os valores:

```bash
cp .env.example .env
```

Variáveis principais:

| Variável | Descrição | Exemplo |
|---|---|---|
| `APP_NAME` | Nome exibido da loja | `Loja de Carrinhos` |
| `APP_ENV` | Ambiente | `local` ou `production` |
| `APP_DEBUG` | Exibe detalhes de erro | `true` em local, `false` em produção |
| `APP_URL` | URL base sem barra final | `http://127.0.0.1:8090` |
| `APP_TIMEZONE` | Timezone do PHP | `America/Sao_Paulo` |
| `DB_HOST` | Host do banco | `127.0.0.1` |
| `DB_PORT` | Porta do banco | `3306` |
| `DB_DATABASE` | Nome do banco | `loja_carrinhos` |
| `DB_USERNAME` | Usuário do banco | `loja_app` |
| `DB_PASSWORD` | Senha do banco | senha forte |
| `SESSION_NAME` | Nome do cookie de sessão | `loja_carrinhos_session` |
| `SESSION_SECURE_COOKIE` | Exige HTTPS no cookie | `false` em local |
| `INSTALL_KEY` | Chave reservada para instalação protegida | aleatória e longa |

Regras:

- `.env` nunca deve ser versionado.
- `.env.example` pode ser versionado, sem segredos.
- Em produção: `APP_DEBUG=false`, HTTPS e `SESSION_SECURE_COOKIE=true`.
- Não reutilize a `INSTALL_KEY` de exemplo.

> **Importante neste workspace:** o `.env` local foi configurado para uma instância MariaDB isolada de testes na porta **3307**, pois a conta administrativa do serviço MariaDB do sistema usa autenticação GSSAPI incompatível com PDO. Para usar seu banco definitivo, altere `DB_PORT`, `DB_USERNAME` e `DB_PASSWORD`.

## 3. Banco de dados

O nome padrão é `loja_carrinhos`, com `utf8mb4`, collation `utf8mb4_unicode_ci` e engine InnoDB.

### Opção A — Migration cria o banco e as tabelas

Use credenciais com permissão de criação de banco:

```bash
php scripts/migrate.php
```

### Opção B — Banco e usuário criados manualmente

Com uma conta administrativa do MySQL/MariaDB:

```sql
CREATE DATABASE loja_carrinhos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'loja_app'@'127.0.0.1' IDENTIFIED BY 'SUBSTITUA_POR_UMA_SENHA_FORTE';
GRANT ALL PRIVILEGES ON loja_carrinhos.* TO 'loja_app'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Depois, execute a migration com esse usuário:

```bash
php scripts/migrate.php
```

As migrations usam `CREATE TABLE IF NOT EXISTS` e podem ser executadas novamente para as tabelas existentes. O runner aplica todos os arquivos `database/migrations/*.sql` em ordem.

Migrations atuais:

- `001_create_initial_tables.sql` — `usuarios` e `logs_auditoria`.
- `002_create_loja_tables.sql` — `loja_entradas`, `loja_entrada_custos`, `loja_itens` e `loja_item_fornecedores`.

Alterações futuras devem ser feitas em novas migrations. Detalhes completos em [03-BANCO-DE-DADOS.md](03-BANCO-DE-DADOS.md).

## 4. Primeiro super-administrador

Com as tabelas criadas:

```bash
php scripts/create-super-admin.php
```

O script:

1. Confirma a conexão e a existência das tabelas.
2. Verifica se já existe super-administrador e interrompe se existir.
3. Solicita nome, e-mail e senha (senha oculta).
4. Valida e-mail e força mínima da senha.
5. Gera o hash com `password_hash()`.
6. Cria o usuário como `super_admin` e ativo.
7. Registra a instalação na auditoria, sem armazenar a senha.

Regras da senha: 8 a 72 caracteres, com pelo menos uma letra e um número.

Para automação controlada, o script aceita:

```bash
php scripts/create-super-admin.php --nome="Administrador" --email="admin@exemplo.com"
```

E, opcionalmente, as variáveis `SUPER_ADMIN_NAME`, `SUPER_ADMIN_EMAIL` e `SUPER_ADMIN_PASSWORD`. Use a variável de senha apenas em ambiente controlado e não a mantenha exportada.

## 5. Testes do módulo Loja

Após configurar o banco e criar o super-administrador, valide o cálculo de custos:

```bash
php scripts/testar-calculo-custos.php
```

Resultado esperado: todos os testes passam e o código de saída é `0`.

As rotas do módulo ficam em:

- `http://127.0.0.1:8090/admin/loja/entradas`
- `http://127.0.0.1:8090/admin/loja/estoque`

## 6. Execução em desenvolvimento

Servidor embutido do PHP, com `public/` como document root:

```bash
php -S 127.0.0.1:8090 -t public public/index.php
```

Acesse:

- Login: `http://127.0.0.1:8090/login`
- Painel: `http://127.0.0.1:8090/admin`

A porta `8090` foi escolhida porque já havia outro sistema utilizando `localhost:8000` no ambiente de validação. Se sua porta estiver livre, você pode usar outra, ajustando também `APP_URL`.

## 7. Apache

Configure o `DocumentRoot` para a pasta `public/` e permita reescrita:

```apache
<VirtualHost *:80>
    ServerName loja.local
    DocumentRoot "C:/caminho/do/projeto/public"

    <Directory "C:/caminho/do/projeto/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

O `.htaccess` em `public/` encaminha requisições inexistentes para `index.php`. As pastas `app/`, `config/`, `database/`, `Manual/`, `storage/`, `scripts/` e `vendor/` não devem ser expostas pelo servidor web.

## 8. Produção

Checklist mínimo:

- `APP_ENV=production`
- `APP_DEBUG=false`
- HTTPS ativo
- `SESSION_SECURE_COOKIE=true`
- Usuário de banco com permissões apenas no banco da aplicação
- `.env` fora do controle de versão
- `INSTALL_KEY` removida ou substituída após a instalação
- Backup do banco e dos logs
- `storage/logs/` com permissão de escrita apenas para o processo PHP

## 9. Erros comuns

| Sintoma | Causa provável | Solução |
|---|---|---|
| `Não foi possível conectar ao banco de dados.` | Serviço parado, porta/credenciais erradas | Verifique `DB_*` no `.env` e o serviço MySQL/MariaDB |
| `The server requested authentication method unknown to the client [auth_gssapi_client]` | Conta usa plugin GSSAPI | Crie um usuário de aplicação com senha (`CREATE USER ... IDENTIFIED BY ...`) |
| Página 419 ao enviar formulário | Sessão expirada ou token ausente | Recarregue a página e envie novamente |
| CSS/JS não carregam | `APP_URL` diferente do endereço acessado | Ajuste `APP_URL` |
| Erro ao executar a migration | Banco inexistente ou sem permissão | Crie o banco/usuário ou use credenciais administrativas |
| 404 em todas as rotas com Apache | `mod_rewrite` ou `AllowOverride` desativado | Ative o módulo e permita `.htaccess` |

## 10. Ambiente de validação desta entrega

- PHP 8.4.12.
- Composer 2.8.11.
- MariaDB 12.0.2 em instância isolada na porta `3307`.
- Servidor PHP em `http://127.0.0.1:8090`.
- Super-admin de teste: `admin@lojacarrinhos.test` / `Admin2026`.

Essas credenciais são **exclusivas para validação local**. Substitua-as em qualquer ambiente real.
