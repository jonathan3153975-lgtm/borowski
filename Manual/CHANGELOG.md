# Changelog

## [Etapa 02] — 2026-11-05

### Entregue

- Módulo Loja com abas **Entrada de produtos** e **Estoque**.
- Menu administrativo atualizado com a seção Loja.
- Compra direta:
  - descrição de origem, link, recebimento, rastreio e observações;
  - custos adicionais;
  - itens com quantidade e custo base;
  - recálculo automático do custo real;
  - alternância de recebimento com atualização de status dos itens.
- Previsão de compra:
  - itens individuais com primeira oferta obrigatória;
  - múltiplas ofertas de fornecedores por item;
  - seleção de uma única oferta;
  - custo base/real definido pela oferta selecionada;
  - estado **Custo a definir** sem seleção.
- Estoque:
  - filtros Todos, Disponível em estoque, Em transporte e Em pré-venda;
  - pesquisa por item, código ou origem;
  - cards de resumo com dados reais;
  - paginação de 20 registros preservando filtros.
- Cálculo de custos em centavos com rateio proporcional e distribuição de resíduo.
- Auditoria centralizada no novo `AuditService`; `UserService` e `AuthController` refatorados.
- Migration `002_create_loja_tables.sql`.
- Runner `scripts/migrate.php` atualizado para aplicar todas as migrations em ordem.
- Script `scripts/testar-calculo-custos.php`.
- Layout administrativo com suporte a `extraStyles` e `extraScripts`.
- Documentação `Manual/06-MODULO-LOJA.md` e atualizações nos demais documentos.

### Tabelas criadas

- `loja_entradas`
- `loja_entrada_custos`
- `loja_itens`
- `loja_item_fornecedores`

### Arquivos principais

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
- `app/Views/loja/**`
- `database/migrations/002_create_loja_tables.sql`
- `routes/loja.php`
- `public/assets/css/loja.css`
- `public/assets/js/loja.js`
- `scripts/testar-calculo-custos.php`

### Testes realizados

- Sintaxe: 52 arquivos PHP verificados com `php -l`, sem erros.
- Cálculo de custos: 27 verificações, todas passaram.
- Roteiro HTTP do módulo Loja: 60 verificações, todas passaram.
- Paginação do estoque com 21 itens: 9 verificações, todas passaram.
- Regressão de autenticação/gerenciamento de usuários/auditoria: 11 verificações, todas passaram.
- Migration executada e tabelas conferidas.
- Exclusão em cascata validada.
- CSRF ausente retornando 419.

### Limitações conhecidas

- Não há conversão automática de item em pré-venda para compra direta.
- Custos adicionais são exclusivos de compras diretas.
- Não há imagens, categorias, fornecedores como entidade, movimentações de estoque ou integração com transportadoras.
- Não há módulo de vendas/pedidos para dar baixa no estoque.
- Paginação fixa em 20 registros.

## [Etapa 01] — 2026-11-04

### Entregue

- Estrutura MVC própria em PHP 8.2+.
- Composer com autoload PSR-4 e `vlucas/phpdotenv`.
- Carregamento de `.env`, timezone, tratamento de erros e log em `storage/logs/app.log`.
- Conexão PDO MySQL/MariaDB com `utf8mb4`, exceções e prepared statements sem emulação.
- Núcleo MVC:
  - `Router` com GET/POST, parâmetros `{id}`, middleware por rota, 404 e 405.
  - `View` com layouts e restrição de caminhos internos.
  - `Controller`, `Model` e `Database`.
- Helpers de escape, URL, redirect, flash, CSRF, autenticação, IP e formatação.
- Sessão segura com `HttpOnly`, `SameSite=Lax`, `use_strict_mode`, `use_only_cookies` e regeneração após login.
- CSRF global em requisições `POST`.
- Autenticação com mensagem genérica de erro e bloqueio temporário após 5 tentativas.
- Perfis `super_admin` e `admin`, com verificação no backend.
- Middlewares `AuthMiddleware` e `GuestMiddleware`.
- Layout de autenticação e layout administrativo responsivos.
- Tela de login com mostrar/ocultar senha e estado de carregamento.
- Dashboard inicial com estados vazios e dados demonstrativos identificados.
- Gerenciamento de administradores:
  - listagem e pesquisa;
  - cadastro;
  - edição;
  - ativação/inativação;
  - exclusão com confirmação;
  - proteção do super-admin.
- Script CLI `scripts/create-super-admin.php` com senha oculta e auditoria.
- Migration inicial com as tabelas `usuarios` e `logs_auditoria`.
- Script `scripts/migrate.php` para executar a migration.
- Documentação técnica completa em `Manual/`.
- `README.md` na raiz.

### Arquivos principais criados

- `composer.json`, `.env`, `.env.example`, `.gitignore`
- `app/Core/Database.php`, `Router.php`, `View.php`, `Model.php`, `Controller.php`
- `app/Controllers/AuthController.php`, `DashboardController.php`, `UserController.php`
- `app/Middleware/AuthMiddleware.php`, `GuestMiddleware.php`
- `app/Models/User.php`
- `app/Services/UserService.php`
- `app/Helpers/functions.php`
- `app/Views/layouts/admin.php`, `auth.php`
- `app/Views/auth/login.php`
- `app/Views/dashboard/index.php`
- `app/Views/users/index.php`, `create.php`, `edit.php`
- `app/Views/errors/403.php`, `404.php`, `405.php`, `419.php`, `500.php`
- `config/app.php`, `config/database.php`
- `database/migrations/001_create_initial_tables.sql`
- `routes/web.php`, `routes/admin.php`
- `public/index.php`, `public/.htaccess`
- `public/assets/css/style.css`, `auth.css`, `admin.css`
- `public/assets/js/app.js`, `admin.js`
- `scripts/migrate.php`, `scripts/create-super-admin.php`
- `Manual/README.md`, `01-ARQUITETURA.md`, `02-INSTALACAO.md`, `03-BANCO-DE-DADOS.md`, `04-AUTENTICACAO-E-ACESSO.md`, `05-INTERFACE.md`, `CHANGELOG.md`

### Dependências

- `vlucas/phpdotenv` 5.8.0
- `phpoption/phpoption` 1.10.0
- `graham-campbell/result-type` 1.2.0
- `symfony/polyfill-ctype` 1.37.0
- `symfony/polyfill-mbstring` 1.43.3
- `symfony/polyfill-php80` 1.43.0

### Testes realizados

Ambiente: PHP 8.4.12, Composer 2.8.11, MariaDB 12.0.2 isolada na porta 3307 e servidor PHP em `127.0.0.1:8090`.

- Sintaxe: 32 arquivos PHP verificados com `php -l`, sem erros.
- Migration executada e tabelas conferidas.
- Super-admin criado via CLI e auditoria confirmada.
- Roteiro HTTP automatizado com 39 verificações:
  - redirecionamentos públicos;
  - login válido e inválido;
  - CSRF ausente (419);
  - dashboard;
  - CRUD completo de administradores;
  - proteção do super-admin;
  - 403 para administrador comum;
  - logout;
  - bloqueio após tentativas;
  - 404 e 405.
- Resultado: **todos os testes passaram**.

### Pendências e limitações conhecidas

- Não há recuperação de senha.
- Não há paginação na lista de administradores.
- O bloqueio de login é por sessão; não é global por IP.
- O token CSRF é rotacionado no login, mas não a cada formulário.
- Não há instalador web; a `INSTALL_KEY` está reservada para etapa futura.
- Módulos de produtos, categorias, estoque, clientes, carrinho, pedidos, pagamentos, frete e financeiro estão fora do escopo.
- O `.env` local aponta para a instância temporária de testes na porta 3307. Para uso real, ajuste as credenciais e recrie banco/super-admin.
- O ambiente do serviço MariaDB do sistema usa autenticação GSSAPI; o PDO exige um usuário com senha (por exemplo, `mysql_native_password`).
- Credenciais de teste locais: `admin@lojacarrinhos.test` / `Admin2026`. Devem ser substituídas em qualquer ambiente real.
