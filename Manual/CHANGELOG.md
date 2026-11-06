# Changelog

## [Ajustes de Catálogo nos Itens, Nova Entrada em Modal e Estoque] — 2026-11-05

### Entregue

- Cadastro de produtos removido da interface:
  - menu **Produtos** removido;
  - `routes/produtos.php` não é mais carregado;
  - vínculo `produto_id` removido do formulário de item;
  - tabelas históricas mantidas no banco; código do módulo removido.
- Informações de catálogo diretamente nos itens:
  - categoria, marca, fabricante, coleção, escala e ano de fabricação;
  - campos opcionais validados no backend;
  - exibidos como metadados nas listas de itens e no estoque.
- Nova entrada de produtos:
  - formulário convertido para **modal** na listagem;
  - repopulação e reabertura automática em caso de erro;
  - rota/página antiga de criação removida.
- Detalhes da entrada:
  - botões de ação do cabeçalho e de adicionar item exibem apenas ícones no mobile;
  - cards de resumo em uma coluna no mobile.
- Tela de Estoque:
  - mesmo padrão de topo da Entrada de produtos;
  - pesquisa por texto sem botão **Pesquisar**;
  - filtros em pills e cards informativos acima da lista;
  - topo fixo e modo compacto no mobile, mantendo apenas a busca.
- Nova migration `007_add_catalogo_to_loja_itens.sql`.
- Configurações auxiliares agora bloqueiam exclusão com base em **itens** vinculados, não em produtos.

### Arquivos principais

- `database/migrations/007_add_catalogo_to_loja_itens.sql`
- `app/Views/loja/entradas/index.php`
- `app/Views/loja/entradas/detalhe.php`
- `app/Views/loja/estoque/index.php`
- `app/Views/loja/partials/entrada-form.php`
- `app/Views/loja/partials/item-edit-modal.php`
- `app/Models/Loja/ItemEstoque.php`
- `app/Services/Loja/EntradaService.php`
- `app/Controllers/Loja/EntradaController.php`
- `app/Models/Configuracao.php`
- `app/Services/ConfiguracaoService.php`
- `app/Views/layouts/admin.php`
- `public/index.php`
- `routes/loja.php`
- `public/assets/css/loja.css`
- `public/assets/js/loja.js`

### Testes realizados

- Ajustes de catálogo, modal, filtros e estoque: **57 verificações HTTP**, todas passaram.
- Módulo Loja após os ajustes: **62 verificações**, todas passaram.
- 64 arquivos PHP verificados com `php -l`, sem erros.
- Migrations 001 a 007 controladas por `schema_migrations`.
- Fotos de itens salvas, servidas e removidas corretamente.
- Exclusão de categoria vinculada a item bloqueada.
- Dados existentes do ambiente preservados.

### Limitações

- As tabelas do módulo de produtos permanecem no banco como histórico; não há mais código nem rotas ativas.
- As fotos dos itens não possuem tela posterior para reordenar, substituir ou alterar a principal.
- A tela de Estoque mantém tabela responsiva; a conversão em cards mobile foi aplicada à listagem de Entradas.
- O ambiente local continua apontando para o MariaDB de teste na porta `3307`.

## [Ajustes de UX da Entrada de produtos e Fotos de Itens] — 2026-11-05

### Entregue

- Listagem de **Entrada de produtos**:
  - pesquisa por texto no topo, ocupando a maior parte da linha;
  - filtros de modo em pills;
  - filtro de recebimento em pills, habilitado apenas em compra direta;
  - pills de recebimento desabilitadas em previsão;
  - botão **Filtrar** removido;
  - controller ignora `recebido` quando o modo não é direto;
  - barra superior fixa, rolando apenas a lista;
  - comportamento compacto no mobile ao rolar, mantendo apenas a busca;
  - tabela convertida em cards modernos no mobile.
- Cadastro de item:
  - formulário convertido para modal completo;
  - vínculo com produto, identificação, quantidade, custo/oferta e observações;
  - upload múltiplo de fotos com pré-visualização;
  - validação das fotos antes da criação do item;
  - fechamento do modal e atualização da lista após salvar;
  - reabertura do modal em caso de erro de validação;
  - fotos exibidas como miniaturas nas listas e no estoque.
- Fotos de itens:
  - nova tabela `loja_item_imagens`;
  - armazenamento em `storage/uploads/loja_itens/`;
  - rota autenticada `/admin/loja/itens/imagem/{id}`;
  - exclusão dos arquivos ao excluir item ou entrada;
  - limites configuráveis por `ITEM_IMAGE_MAX_SIZE` e `ITEM_IMAGE_MAX_IMAGES`.
- Nova migration `006_create_loja_item_imagens.sql`.
- Documentação atualizada em `Manual/02`, `Manual/03`, `Manual/05` e `Manual/06`.

### Arquivos principais

- `database/migrations/006_create_loja_item_imagens.sql`
- `app/Models/Loja/ItemImagem.php`
- `app/Services/Loja/ItemImagemService.php`
- `app/Controllers/Loja/ItemImagemController.php`
- `app/Views/loja/entradas/index.php`
- `app/Views/loja/entradas/detalhe.php`
- `app/Views/loja/estoque/index.php`
- `app/Models/Loja/ItemEstoque.php`
- `app/Services/Loja/EntradaService.php`
- `app/Controllers/Loja/EntradaController.php`
- `app/Helpers/functions.php`
- `routes/loja.php`
- `public/assets/css/loja.css`
- `public/assets/css/style.css`
- `public/assets/js/app.js`
- `public/assets/js/loja.js`
- `config/app.php`, `.env`, `.env.example` e `.gitignore`

### Testes realizados

- 71 arquivos PHP da pasta `app` verificados com `php -l`, sem erros.
- Ajustes de UX e fotos de itens: 32 verificações HTTP, todas passaram.
- Módulo Loja após os ajustes: 62 verificações, todas passaram.
- Validação de upload inválido sem criar item.
- Rota de foto de item retornando imagem autenticada.
- Exclusão de fotos ao excluir entrada.
- Filtros de modo/recebimento e cards mobile validados.
- Migrations 001 a 006 controladas por `schema_migrations`.

### Limitações

- As fotos dos itens não possuem tela de gerenciamento posterior (definir principal, reordenar, substituir) nesta rodada.
- O vínculo com produto continua opcional.
- A loja pública e os módulos de vendas/pedidos ainda não existem.
- O ambiente local continua apontando para o MariaDB de teste na porta `3307`.

## [Módulo de Produtos e Precisão Monetária] — 2026-11-05

### Entregue

- Precisão monetária de **3 casas decimais** em todo o sistema:
  - colunas do módulo Loja e `preco_venda` em `DECIMAL(12,3)`;
  - cálculos internos em milésimos inteiros;
  - máscara `1.234,567` nos campos monetários;
  - exibição padronizada em `R$ 1.234,567`.
- Cadastro completo de produtos:
  - informações principais, características e dados comerciais;
  - SKU único;
  - preço de venda com autorização de exibição;
  - destaque para linhas da loja.
- Cadastros auxiliares:
  - categorias, marcas, fabricantes, coleções e escalas;
  - CRUD no novo menu **Configurações**;
  - cadastro rápido via AJAX no formulário de produto.
- Gerenciador de imagens:
  - upload múltiplo com pré-visualização;
  - armazenamento seguro em `storage/uploads/produtos`;
  - imagem principal, ordenação, substituição e exclusão;
  - carrossel com indicadores, setas e miniaturas;
  - rota pública controlada `/produto-imagem/{id}`.
- Componentes visuais reutilizáveis:
  - card de produto;
  - linha horizontal com setas, rolagem por toque e quantidade configurável de cards;
  - carrossel de imagens.
- Integração com o módulo Loja:
  - coluna `produto_id` em `loja_itens`;
  - seleção de produto no cadastro de item;
  - saldo, custo médio real e histórico calculados a partir das entradas;
  - exclusão de produto bloqueada quando há vínculo com estoque.
- Migration runner com tabela `schema_migrations`.
- Novas migrations:
  - `003_alter_money_precision.sql`;
  - `004_create_produtos_tables.sql`;
  - `005_add_produto_id_to_loja_itens.sql`.
- jQuery 3.7.1 e `masks.js` adicionados ao painel administrativo.

### Arquivos principais criados

- `app/Controllers/ProdutoController.php`
- `app/Controllers/ProdutoImagemController.php`
- `app/Controllers/ConfiguracaoController.php`
- `app/Models/Produto.php`
- `app/Models/ProdutoImagem.php`
- `app/Models/Configuracao.php`
- `app/Services/ProdutoService.php`
- `app/Services/ProdutoImagemService.php`
- `app/Services/ConfiguracaoService.php`
- `app/Views/produtos/**`
- `app/Views/configuracoes/**`
- `routes/produtos.php`
- `routes/configuracoes.php`
- `public/assets/css/produtos.css`
- `public/assets/css/configuracoes.css`
- `public/assets/js/produtos.js`
- `public/assets/js/masks.js`
- `database/migrations/003_alter_money_precision.sql`
- `database/migrations/004_create_produtos_tables.sql`
- `database/migrations/005_add_produto_id_to_loja_itens.sql`

### Arquivos alterados

- `app/Core/Controller.php` — resposta JSON.
- `app/Models/Configuracao.php`, `Produto.php`, `ProdutoImagem.php` — novos models.
- `app/Services/Loja/CalculoService.php` — milésimos e 3 casas.
- `app/Services/Loja/EntradaService.php` — vínculo com produto e 3 casas.
- `app/Services/Loja/PrevisaoService.php` — 3 casas.
- `app/Models/Loja/*` — precisão e integração com produto.
- `app/Views/layouts/admin.php` — menu Produtos/Configurações, jQuery, máscara e assets extras.
- `app/Views/loja/**` — máscara, 3 casas e select de produto.
- `app/Helpers/functions.php` — formatação monetária com 3 casas e URL de imagem.
- `scripts/migrate.php` — controle de migrations aplicadas.
- `config/app.php`, `.env` e `.env.example` — configurações de upload.
- `.gitignore` — uploads fora do versionamento.
- `README.md` e documentação em `Manual/`.

### Testes realizados

- 79 arquivos PHP verificados com `php -l`, sem erros.
- Cálculo monetário: 29 verificações, todas passaram.
- Módulo de produtos: 74 verificações HTTP, todas passaram.
- Módulo Loja após a refatoração: 62 verificações, todas passaram.
- Paginação do estoque: 9 verificações, todas passaram.
- Regressão de autenticação, usuários e auditoria: 11 verificações, todas passaram.
- Migrations 001 a 005 aplicadas e controladas por `schema_migrations`.
- Upload inválido rejeitado; rota pública servindo apenas imagens.
- Exclusão de produto e categoria bloqueada quando há vínculos.
- Dados de teste removidos ao final, preservando registros existentes do ambiente.

### Limitações conhecidas

- A loja pública ainda não foi implementada.
- Não há carrinho, checkout, pedidos ou pagamentos.
- Não há variações de produto, código de barras, importação/exportação ou exclusão em massa.
- Não há conversão automática de item em pré-venda para compra direta.
- O vínculo do item ao produto depende da seleção no formulário do módulo Loja.
- O ambiente local continua apontando para a instância MariaDB de teste na porta `3307`.

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
