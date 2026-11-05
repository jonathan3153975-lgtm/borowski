# Loja de Carrinhos

Sistema próprio de gerenciamento para uma loja virtual de carrinhos colecionáveis, com painel administrativo e loja virtual previstos para evolução em etapas. Esta entrega corresponde às **Etapas 01 e 02**: fundação do sistema, autenticação e módulo Loja (entrada de produtos e estoque).

## Situação atual

- Estrutura MVC própria em PHP, sem framework completo.
- Composer com autoload PSR-4 e `vlucas/phpdotenv`.
- Conexão MySQL/MariaDB via PDO.
- Autenticação com sessão segura, CSRF e bloqueio temporário por tentativas.
- Perfis `super_admin` e `admin`.
- Dashboard administrativo com estados vazios identificados.
- Gerenciamento de administradores restrito ao super-administrador.
- Módulo Loja com abas **Entrada de produtos** e **Estoque**.
- Compra direta com custos adicionais, itens, recebimento e custo real calculado.
- Previsão de compra com múltiplos fornecedores por item e seleção de oferta.
- Estoque com filtros por disponível, em transporte e em pré-venda.
- Migrations iniciais e script CLI para criar o primeiro super-administrador.
- Documentação técnica em [`Manual/`](Manual/README.md).

## Tecnologias

| Tecnologia | Versão / uso |
|---|---|
| PHP | 8.2 ou superior (validado com 8.4.12) |
| MySQL/MariaDB | MySQL 8 ou compatível (validado com MariaDB 12.0.2) |
| PDO | Acesso ao banco com prepared statements |
| Composer | 2.x (validado com 2.8.11) |
| vlucas/phpdotenv | 5.8.0 |
| Bootstrap | 5.3.3 via CDN |
| Bootstrap Icons | 1.11.3 via CDN |
| JavaScript | Puro, sem dependências adicionais |

## Início rápido

```bash
composer install
cp .env.example .env
```

Edite o `.env` com as credenciais do seu banco e a URL local. Depois:

```bash
php scripts/migrate.php
php scripts/create-super-admin.php
php scripts/testar-calculo-custos.php
php -S 127.0.0.1:8090 -t public public/index.php
```

Acesse `http://127.0.0.1:8090/login`.

Rotas principais do módulo Loja:

- `http://127.0.0.1:8090/admin/loja/entradas`
- `http://127.0.0.1:8090/admin/loja/estoque`

> **Ambiente desta entrega:** o `.env` local está apontando para uma instância MariaDB isolada de testes na porta `3307`, criada apenas para validação. Para usar seu MySQL/MariaDB definitivo, ajuste `DB_PORT`, `DB_USERNAME` e `DB_PASSWORD`, recrie o banco/migration e crie um novo super-administrador.

## Estrutura resumida

```text
app/            Núcleo MVC, controllers, models, services, middlewares, views e helpers
config/         Configurações de aplicação e banco
database/       Migrations, incluindo as tabelas do módulo Loja, e pasta de seeds
Manual/         Documentação técnica obrigatória
public/         Document root (index.php, .htaccess e assets)
routes/         Rotas web, administrativas e do módulo Loja
scripts/        Migrations, super-administrador e testes de cálculo
storage/        Logs e arquivos temporários
```

## Documentação

- [Manual/README.md](Manual/README.md)
- [Manual/01-ARQUITETURA.md](Manual/01-ARQUITETURA.md)
- [Manual/02-INSTALACAO.md](Manual/02-INSTALACAO.md)
- [Manual/03-BANCO-DE-DADOS.md](Manual/03-BANCO-DE-DADOS.md)
- [Manual/04-AUTENTICACAO-E-ACESSO.md](Manual/04-AUTENTICACAO-E-ACESSO.md)
- [Manual/05-INTERFACE.md](Manual/05-INTERFACE.md)
- [Manual/06-MODULO-LOJA.md](Manual/06-MODULO-LOJA.md)
- [Manual/CHANGELOG.md](Manual/CHANGELOG.md)
