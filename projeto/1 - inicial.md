# ETAPA 01 — Fundação do Sistema, Layout e Autenticação

## 1. Instruções gerais para a IA

Você atuará como desenvolvedor responsável pela implementação da primeira etapa de um sistema próprio de gerenciamento de loja virtual de carrinhos colecionáveis.

Implemente o projeto diretamente no workspace aberto do Visual Studio Code, respeitando as decisões, padrões e requisitos deste documento.

### Regras obrigatórias de execução

1. Antes de criar ou alterar arquivos, inspecione o conteúdo existente do projeto. Não sobrescreva arquivos ou configurações sem verificar se já existem.
2. Se o projeto estiver vazio, crie a estrutura descrita neste documento.
3. Se já houver código, preserve o que estiver correto e adapte-o à arquitetura proposta.
4. Não invente funcionalidades fora do escopo desta etapa.
5. Não deixe trechos fictícios, botões sem ação ou comentários do tipo `TODO` em funcionalidades declaradas como concluídas.
6. Utilize código organizado, orientação a objetos, separação de responsabilidades e nomes consistentes.
7. Não coloque credenciais reais no código-fonte. Utilize variáveis de ambiente.
8. Sempre valide os dados no backend. Validação JavaScript é complementar, nunca substitui a validação PHP.
9. Utilize PDO com prepared statements para todas as consultas que recebam dados externos.
10. Toda operação de criação, alteração ou exclusão deve apresentar feedback adequado ao usuário.
11. Não exponha arquivos internos, configurações, logs ou credenciais pela web.
12. Ao concluir, execute verificações disponíveis, corrija erros e apresente um resumo dos arquivos criados, configurações necessárias e testes realizados.
13. **A documentação é obrigatória em todas as etapas:** para cada etapa de desenvolvimento, crie ou atualize os documentos correspondentes dentro da pasta `Manual/`, na raiz do projeto. Não deixe a documentação para o final.

Se alguma decisão técnica não estiver especificada, escolha a alternativa mais simples, segura, sustentável e compatível com PHP e MySQL. Registre a decisão na documentação.

---

## 2. Visão geral do projeto

Será desenvolvido um sistema próprio de comércio eletrônico para uma loja de carrinhos colecionáveis, com dois ambientes integrados:

- **Painel administrativo:** gerenciamento interno da loja.
- **Loja virtual:** ambiente público para os clientes consultarem produtos e realizarem compras.

Os dois ambientes compartilharão o mesmo banco de dados e as mesmas regras de negócio, mas terão rotas, layouts e controles de acesso separados.

### 2.1. Tecnologias definidas

| Tecnologia | Utilização |
|---|---|
| PHP 8.2 ou superior | Backend |
| MySQL 8 ou compatível | Banco de dados |
| PDO | Acesso ao banco |
| HTML5 | Estrutura das páginas |
| CSS3 | Identidade visual própria |
| JavaScript | Interações do frontend |
| Bootstrap 5 | Base responsiva e componentes |
| Composer | Autoload e dependências PHP |
| Apache com mod_rewrite | URLs amigáveis, quando disponível |

Utilize JavaScript puro nesta primeira etapa, evitando dependências desnecessárias. Não adote Laravel ou outro framework MVC completo: o projeto terá um núcleo MVC próprio, simples e organizado.

### 2.2. Identidade visual

O sistema deverá ter aparência moderna, profissional, minimalista e intuitiva, com linhas retas, espaçamentos consistentes e boa adaptação a celulares, tablets e desktops.

Paleta inicial:

| Elemento | Cor |
|---|---|
| Azul escuro principal | `#101D35` |
| Azul secundário | `#1B2E4D` |
| Amarelo marca-texto | `#EFFF00` |
| Amarelo hover | `#D9E600` |
| Fundo | `#F4F6F9` |
| Superfícies/cards | `#FFFFFF` |
| Texto principal | `#263247` |
| Texto secundário | `#788397` |
| Bordas | `#E2E7EF` |

O azul escuro será predominante no menu lateral e em elementos institucionais. O amarelo deverá ser usado com moderação em botões de ação principal, destaques e indicadores. Garanta contraste e legibilidade; não utilize amarelo em textos longos.

A identidade visual deve ser própria. Não apresente o sistema como um painel Bootstrap padrão. Personalize tipografia, botões, formulários, tabelas, cards, menus, estados de foco e responsividade.

---

## 3. Escopo desta primeira etapa

### 3.1. Entregas obrigatórias

1. Inspecionar o ambiente e preparar o projeto.
2. Criar a estrutura de diretórios MVC.
3. Configurar Composer e autoload PSR-4.
4. Configurar variáveis de ambiente.
5. Criar conexão MySQL utilizando PDO.
6. Implementar núcleo MVC básico.
7. Implementar roteamento e URLs amigáveis.
8. Criar layouts de autenticação e administração.
9. Criar tela de login responsiva.
10. Implementar autenticação, sessão e logout.
11. Implementar os perfis `super_admin` e `admin`.
12. Implementar proteção das rotas administrativas.
13. Criar procedimento seguro para instalação do primeiro super-administrador.
14. Criar dashboard administrativo inicial com dados demonstrativos claramente identificados.
15. Criar gerenciamento básico de administradores.
16. Criar tabelas iniciais do banco de dados.
17. Criar documentação técnica na pasta `Manual/`.

### 3.2. Fora do escopo desta etapa

Não implementar ainda:

- Cadastro e gerenciamento de produtos.
- Categorias e imagens de produtos.
- Fornecedores e compras.
- Estoque e movimentações.
- Clientes e endereços.
- Carrinho e checkout.
- Pedidos e vendas.
- Integração com Mercado Pago ou outros gateways.
- Frete.
- Financeiro e relatórios reais.
- Cupons e promoções.

A arquitetura deverá permitir incorporar esses módulos posteriormente, sem antecipar tabelas ou regras que ainda não foram especificadas.

---

## 4. Estrutura de diretórios

Crie a seguinte estrutura na raiz do projeto:

```text
loja-carrinhos/
│
├── app/
│   ├── Core/
│   │   ├── Controller.php
│   │   ├── Database.php
│   │   ├── Model.php
│   │   ├── Router.php
│   │   └── View.php
│   │
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   └── UserController.php
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   └── GuestMiddleware.php
│   │
│   ├── Models/
│   │   └── User.php
│   │
│   ├── Services/
│   │   └── UserService.php
│   │
│   ├── Views/
│   │   ├── layouts/
│   │   │   ├── admin.php
│   │   │   └── auth.php
│   │   ├── auth/
│   │   │   └── login.php
│   │   ├── dashboard/
│   │   │   └── index.php
│   │   └── users/
│   │       ├── index.php
│   │       ├── create.php
│   │       └── edit.php
│   │
│   └── Helpers/
│       └── functions.php
│
├── config/
│   ├── app.php
│   └── database.php
│
├── database/
│   ├── migrations/
│   │   └── 001_create_initial_tables.sql
│   └── seeds/
│
├── Manual/
│   ├── README.md
│   ├── 01-ARQUITETURA.md
│   ├── 02-INSTALACAO.md
│   ├── 03-BANCO-DE-DADOS.md
│   ├── 04-AUTENTICACAO-E-ACESSO.md
│   ├── 05-INTERFACE.md
│   └── CHANGELOG.md
│
├── public/
│   ├── index.php
│   ├── .htaccess
│   └── assets/
│       ├── css/
│       │   ├── style.css
│       │   ├── auth.css
│       │   └── admin.css
│       ├── js/
│       │   ├── app.js
│       │   └── admin.js
│       └── img/
│
├── routes/
│   ├── web.php
│   └── admin.php
│
├── storage/
│   ├── logs/
│   └── temp/
│
├── vendor/                 # Gerado pelo Composer
├── .env
├── .env.example
├── .gitignore
├── composer.json
└── README.md
```

A pasta `public/` será o document root da aplicação. Somente ela deverá ser acessível diretamente pelo servidor web. `app/`, `config/`, `database/`, `Manual/`, `storage/` e `vendor/` não poderão ser acessados diretamente por HTTP.

Não crie a pasta `public/uploads` nesta etapa. Futuras imagens de produtos deverão ser armazenadas em local não executável e servidas por mecanismo controlado, conforme decisão de segurança da etapa correspondente.

---

## 5. Dependências e configuração do ambiente

### 5.1. Verificações iniciais

Verifique se estão disponíveis:

- PHP 8.2 ou superior.
- Extensões PHP `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` e `json`.
- Composer 2.
- MySQL 8 ou versão compatível.
- Apache com `mod_rewrite`, se o ambiente utilizar Apache.

Caso alguma dependência esteja ausente, informe claramente o que precisa ser instalado. Não tente alterar configurações do sistema operacional sem autorização.

### 5.2. Composer

Crie `composer.json` com:

- Pacote `vlucas/phpdotenv` para leitura de variáveis de ambiente.
- Autoload PSR-4 para o namespace `App\\`, apontando para `app/`.
- Scripts úteis, se necessário.

Execute `composer install` e confirme a geração do autoload.

Exemplo de configuração esperada:

```json
{
    "name": "loja-carrinhos/sistema",
    "description": "Sistema próprio de gerenciamento de loja virtual",
    "type": "project",
    "require": {
        "php": "^8.2",
        "vlucas/phpdotenv": "^5.6"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        },
        "files": [
            "app/Helpers/functions.php"
        ]
    }
}
```

Não copie dependências para dentro de `public/`.

### 5.3. Frontend

Utilize Bootstrap 5 por meio de CDN oficial, com versão estável fixada e documentada. Inclua o bundle JavaScript necessário aos componentes Bootstrap.

Se utilizar Bootstrap Icons, fixe também a versão. Evite carregar bibliotecas duplicadas.

O CSS próprio deverá sobrescrever e complementar o Bootstrap, não substituir sua estrutura responsiva.

### 5.4. Arquivo `.env.example`

Crie um modelo semelhante a:

```dotenv
APP_NAME="Loja de Carrinhos"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/loja-carrinhos/public
APP_TIMEZONE=America/Sao_Paulo

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=loja_carrinhos
DB_USERNAME=root
DB_PASSWORD=

SESSION_NAME=loja_carrinhos_session
SESSION_SECURE_COOKIE=false

# Chave temporária e exclusiva para a instalação inicial.
INSTALL_KEY=SUBSTITUIR_POR_UMA_CHAVE_ALEATORIA_LONGA
```

Crie também o `.env` local a partir do exemplo, quando autorizado. Não substitua valores existentes sem confirmação.

Regras:

- `.env` nunca poderá ser versionado.
- `.env.example` poderá ser versionado, sem segredos.
- Em produção, `APP_DEBUG=false`.
- Em produção, utilizar HTTPS e `SESSION_SECURE_COOKIE=true`.
- Não exibir detalhes de exceções, SQL ou caminhos internos ao usuário final.

### 5.5. `.gitignore`

Ignorar, no mínimo:

```gitignore
/vendor/
/.env
/storage/logs/*
/storage/temp/*
/.DS_Store
Thumbs.db
```

Mantenha arquivos `.gitkeep` em diretórios de armazenamento que precisem existir no repositório.

---

## 6. Banco de dados

### 6.1. Criação

Utilize o banco:

- Nome: `loja_carrinhos`
- Charset: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`
- Engine: InnoDB

Não armazene credenciais do banco no arquivo SQL.

Crie a migration inicial em:

`database/migrations/001_create_initial_tables.sql`

A migration deverá ser idempotente quando possível, utilizando `CREATE TABLE IF NOT EXISTS`. Documente que essa abordagem não substitui um sistema de migrations para alterações futuras.

### 6.2. Tabela `usuarios`

Campos:

| Campo | Tipo | Regra |
|---|---|---|
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| nome | VARCHAR(150) | NOT NULL |
| email | VARCHAR(180) | NOT NULL, UNIQUE |
| senha | VARCHAR(255) | NOT NULL |
| tipo | ENUM('super_admin','admin') | NOT NULL |
| status | ENUM('ativo','inativo') | NOT NULL |
| ultimo_acesso | DATETIME | NULL |
| criado_em | DATETIME | DEFAULT CURRENT_TIMESTAMP |
| atualizado_em | DATETIME | NULL, atualização automática |

O sistema deverá permitir apenas um super-administrador nesta primeira versão. Garanta isso na lógica de instalação e nos serviços, e não dependa exclusivamente de uma validação da interface.

Não permita que um administrador comum crie, edite, bloqueie ou exclua usuários.

O super-administrador poderá cadastrar, editar, ativar, inativar e excluir administradores. Não deverá existir uma ação pela interface para excluir ou rebaixar o próprio super-administrador.

### 6.3. Tabela `logs_auditoria`

Campos:

| Campo | Tipo | Regra |
|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT |
| usuario_id | INT UNSIGNED | FK, NULL |
| acao | VARCHAR(100) | NOT NULL |
| descricao | TEXT | NULL |
| ip | VARCHAR(45) | NULL |
| criado_em | DATETIME | DEFAULT CURRENT_TIMESTAMP |

Relacionamento:

- `usuario_id` referencia `usuarios.id`.
- Ao excluir um administrador, preservar os registros de auditoria e definir `usuario_id` como NULL.
- Criar índices para `usuario_id` e `criado_em`.

Registrar, no mínimo, login bem-sucedido, logout, criação/edição/ativação/inativação/exclusão de administrador e eventos relevantes de segurança. Nunca registrar senhas, tokens de sessão ou segredos.

### 6.4. Regras gerais

- Use InnoDB.
- Use `utf8mb4`.
- Defina chaves primárias e índices adequados.
- Use foreign keys quando houver relacionamentos.
- Não utilize valores monetários com `FLOAT` ou `DOUBLE`; nas futuras tabelas financeiras, utilizar `DECIMAL`.
- Não crie ainda tabelas de produtos, estoque, pedidos ou pagamentos.

---

## 7. Núcleo MVC

Implemente um núcleo pequeno, claro e funcional.

### 7.1. `public/index.php`

Deverá:

1. Carregar o autoload do Composer.
2. Carregar variáveis de ambiente.
3. Configurar timezone.
4. Inicializar sessão com opções seguras.
5. Carregar configurações.
6. Instanciar e executar o roteador.

### 7.2. `Database.php`

Responsabilidades:

- Criar e disponibilizar conexão PDO.
- Utilizar configurações do `.env`.
- Configurar `PDO::ATTR_ERRMODE` para exceções.
- Configurar `PDO::ATTR_DEFAULT_FETCH_MODE` como `PDO::FETCH_ASSOC`.
- Desativar emulação de prepared statements quando compatível.
- Definir charset `utf8mb4`.
- Não imprimir credenciais ou mensagens internas em caso de falha.

### 7.3. `Router.php`

Implementar roteamento por método HTTP e caminho.

Requisitos:

- Suportar GET e POST.
- Permitir associação de rota a Controller e método.
- Permitir middleware por rota ou grupo.
- Retornar 404 para rotas inexistentes.
- Retornar 405 quando o caminho existir, mas o método HTTP não for permitido, se a implementação permitir distinguir esses casos.
- Não executar métodos arbitrários enviados pelo usuário.
- Normalizar o caminho e tratar a URL base configurada.

### 7.4. `Controller.php` e `View.php`

O Controller deverá disponibilizar métodos auxiliares para renderizar views e redirecionar.

A View deverá:

- Carregar arquivos somente de caminhos internos previamente definidos.
- Receber dados explicitamente.
- Permitir layouts.
- Não aceitar nomes de arquivos de view diretamente de parâmetros do usuário.

### 7.5. `Model.php`

Criar uma classe base simples para compartilhar a conexão PDO, sem criar abstrações excessivas ou um ORM próprio.

### 7.6. Helpers

Criar funções auxiliares necessárias, como:

- `e($value)`: escape HTML utilizando `htmlspecialchars` com UTF-8.
- `url($path)`: montar URL interna respeitando `APP_URL`.
- `redirect($path)`: redirecionamento interno.
- Funções de mensagens flash, caso não sejam implementadas em uma classe própria.

Evite funções globais com nomes genéricos que possam conflitar com PHP ou bibliotecas.

---

## 8. Autenticação e controle de acesso

### 8.1. Perfis

Existirão somente dois tipos:

- `super_admin`: proprietário, com acesso ao painel e ao gerenciamento de administradores.
- `admin`: administrador operacional, com acesso ao dashboard, mas sem gerenciamento de usuários administrativos.

Nesta etapa, não criar permissões individuais por módulo.

### 8.2. Login

Criar uma tela de login com:

- Campo de e-mail.
- Campo de senha.
- Botão para entrar.
- Opção para mostrar/ocultar senha.
- Mensagens de validação e erro.
- Layout responsivo e identidade visual definida.
- Estado de carregamento no botão, se houver submissão assíncrona.

O login deverá:

1. Validar os campos no backend.
2. Localizar o usuário pelo e-mail.
3. Verificar se o usuário está ativo.
4. Validar a senha com `password_verify()`.
5. Regenerar o ID da sessão após autenticação.
6. Registrar `ultimo_acesso`.
7. Registrar auditoria de login bem-sucedido.
8. Redirecionar ao dashboard.

Para credenciais inválidas, utilizar mensagem genérica, sem revelar se o e-mail está cadastrado.

Não implementar recuperação de senha nesta etapa; deixar a rota e o recurso para uma etapa posterior.

### 8.3. Sessão

- Configurar `session.use_strict_mode`.
- Utilizar cookies `HttpOnly`.
- Utilizar `SameSite=Lax`.
- Utilizar `Secure` em HTTPS.
- Regenerar o ID da sessão após login.
- Destruir a sessão e invalidar o cookie no logout.
- Não armazenar senha ou hash da senha na sessão.
- Implementar proteção básica contra tentativas repetidas de login, com mecanismo simples e documentado, sem depender exclusivamente de JavaScript.

### 8.4. CSRF

Criar proteção CSRF para todos os formulários que alterem estado:

- Gerar token aleatório criptograficamente seguro.
- Guardar token na sessão.
- Incluir token nos formulários.
- Validar token no backend com comparação segura.
- Rejeitar requisições inválidas com resposta apropriada.

### 8.5. Middleware

`AuthMiddleware` deverá bloquear acesso de visitantes às rotas administrativas.

`GuestMiddleware` deverá impedir que usuários autenticados acessem a tela de login, redirecionando-os ao dashboard.

As rotas de gerenciamento de administradores deverão exigir `super_admin`, com verificação no backend. Ocultar links na interface não é controle de segurança suficiente.

---

## 9. Instalação do primeiro super-administrador

Criar um procedimento seguro para instalação inicial.

Preferência: comando CLI, por exemplo:

```bash
php scripts/create-super-admin.php
```

Se necessário, crie a pasta `scripts/` e documente o comando.

O procedimento deverá:

1. Confirmar que a conexão com o banco funciona.
2. Confirmar que as tabelas iniciais existem.
3. Verificar se já existe um super-administrador.
4. Se já existir, interromper a instalação.
5. Solicitar nome, e-mail e senha de forma segura.
6. Validar e-mail e força mínima da senha.
7. Gerar hash usando `password_hash()`.
8. Criar o usuário como `super_admin` e ativo.
9. Registrar a instalação, sem armazenar a senha em logs.

Se o ambiente não permitir execução CLI, poderá ser criado um instalador web temporário protegido por `INSTALL_KEY`, com as seguintes exigências:

- Disponível somente enquanto não existir super-administrador.
- Exigir chave longa e aleatória.
- Utilizar HTTPS em ambiente remoto.
- Não permitir execução após a instalação.
- Ser removido ou desabilitado automaticamente após sucesso.
- Não expor a chave em logs ou respostas.

Não inserir usuário ou senha padrão no SQL.

---

## 10. Gerenciamento de administradores

Criar uma interface disponível somente ao super-administrador.

Funcionalidades:

- Listar administradores.
- Pesquisar por nome ou e-mail.
- Cadastrar administrador.
- Editar nome e e-mail.
- Ativar ou inativar administrador.
- Excluir administrador, com confirmação.
- Validar e-mail único.
- Não permitir que o super-administrador seja manipulado por essas operações.
- Não permitir exclusão ou inativação do próprio usuário autenticado por meio de uma ação indireta.

A senha deverá ser definida no cadastro. Na edição, permitir alteração somente quando uma nova senha for informada. Nunca preencher formulário com hash ou senha existente.

As operações deverão utilizar POST, CSRF e validação no backend. Registrar as operações em auditoria.

---

## 11. Layout administrativo

### 11.1. Estrutura

Criar layout administrativo com:

- Sidebar azul escuro.
- Cabeçalho superior.
- Área principal em fundo cinza claro.
- Cards brancos.
- Botões de ação em amarelo.
- Ícones consistentes.
- Breadcrumb.
- Menu adaptável para dispositivos móveis.
- Identificação do usuário autenticado.
- Ação de sair.

A sidebar deverá poder ser recolhida no desktop e convertida em menu offcanvas ou equivalente no celular.

### 11.2. Menu inicial

Exibir os seguintes itens:

- Dashboard.
- Administradores (somente super-admin).
- Sair.

Os módulos futuros poderão aparecer posteriormente. Não criar links falsos ou destinos sem implementação.

### 11.3. Dashboard inicial

Criar dashboard visualmente completo, mas sem inventar dados reais.

Pode apresentar cards demonstrativos, desde que identificados claramente como dados ilustrativos ou estados vazios. Não apresentar números fictícios como resultados reais da loja.

Sugestão de componentes:

- Vendas do mês: “Aguardando módulo de vendas”.
- Pedidos recebidos: “Aguardando módulo de pedidos”.
- Produtos cadastrados: “Aguardando módulo de produtos”.
- Estoque baixo: “Aguardando módulo de estoque”.
- Área de gráfico com estado vazio.
- Área de últimos pedidos com estado vazio.

Incluir uma saudação com o nome do usuário autenticado.

---

## 12. Layout de autenticação

Criar uma tela de login com visual independente do painel, mantendo a identidade da marca.

Diretrizes:

- Fundo claro ou composição com azul escuro.
- Card de login com linhas retas e bordas discretas.
- Destaques amarelo marca-texto.
- Campos com foco visual evidente.
- Botão principal destacado.
- Layout responsivo.
- Espaço reservado para logotipo/nome provisório da loja.
- Não inventar logotipo definitivo.

O nome da marca ainda não foi definido. Utilizar “Loja de Carrinhos” como identificação provisória, centralizando o nome em configuração para facilitar alteração futura.

---

## 13. Rotas mínimas

Implemente rotas equivalentes às seguintes:

| Método | Rota | Acesso | Ação |
|---|---|---|---|
| GET | `/login` | Visitante | Exibir login |
| POST | `/login` | Visitante | Autenticar |
| POST | `/logout` | Autenticado | Encerrar sessão |
| GET | `/admin` | Autenticado | Dashboard |
| GET | `/admin/usuarios` | Super-admin | Listar administradores |
| GET | `/admin/usuarios/criar` | Super-admin | Formulário de cadastro |
| POST | `/admin/usuarios` | Super-admin | Criar administrador |
| GET | `/admin/usuarios/{id}/editar` | Super-admin | Formulário de edição |
| POST | `/admin/usuarios/{id}/editar` | Super-admin | Atualizar administrador |
| POST | `/admin/usuarios/{id}/status` | Super-admin | Ativar/inativar |
| POST | `/admin/usuarios/{id}/excluir` | Super-admin | Excluir administrador |

A sintaxe de parâmetros poderá ser adaptada à implementação do Router. O importante é preservar os caminhos, métodos e regras de acesso.

A rota raiz `/` deverá redirecionar para `/login` quando não autenticado e para `/admin` quando autenticado.

---

## 14. Padrões de interface e JavaScript

- Criar CSS separado para estilos gerais, autenticação e administração.
- Centralizar variáveis de cor, espaçamento e dimensões em `:root`.
- Evitar CSS inline, exceto em situações justificadas.
- Criar componentes visuais reutilizáveis.
- Utilizar feedback visual para sucesso, erro, carregamento e ausência de dados.
- Confirmar ações destrutivas.
- Utilizar JavaScript para interações de interface, não para decisões de autorização.
- Evitar dependências JavaScript desnecessárias.
- Garantir funcionamento básico sem animações.
- Testar navegação por teclado e estados de foco.

Não é necessário implementar AJAX em todas as operações. Formulários tradicionais com POST e redirecionamento são aceitáveis nesta etapa, desde que apresentem boa experiência de uso.

---

## 15. Segurança e qualidade

Antes de considerar a etapa concluída, verifique:

- [ ] Senhas armazenadas somente com hash seguro.
- [ ] Prepared statements em consultas com dados externos.
- [ ] Proteção CSRF em operações de alteração.
- [ ] Escape de saída HTML.
- [ ] Proteção de rotas no backend.
- [ ] Sessão regenerada após autenticação.
- [ ] Cookies de sessão configurados corretamente.
- [ ] Arquivos internos fora do document root.
- [ ] `.env` fora do controle de versão.
- [ ] Mensagens de erro sem detalhes internos em produção.
- [ ] Validação no backend.
- [ ] Tratamento de e-mail duplicado.
- [ ] Proteção contra manipulação do super-administrador.
- [ ] Auditoria sem dados sensíveis.
- [ ] Consultas e operações com tratamento de exceções.

Não afirmar que o sistema está seguro apenas porque esses itens foram implementados. Registrar testes realizados e limitações conhecidas.

---

## 16. Documentação obrigatória — pasta `Manual/`

**Toda etapa de desenvolvimento deverá produzir documentação técnica na pasta `Manual/`, localizada na raiz do projeto. Essa regra vale para todas as etapas futuras, não somente para esta primeira entrega.**

A documentação deverá ser escrita em Markdown, em português do Brasil, ser objetiva e refletir exatamente o código implementado.

### Documentos desta etapa

#### `Manual/README.md`

Deverá conter:

- Visão geral do sistema.
- Tecnologias utilizadas.
- Organização da documentação.
- Índice dos documentos.
- Situação atual do projeto.

#### `Manual/01-ARQUITETURA.md`

Deverá conter:

- Descrição da arquitetura MVC.
- Estrutura de diretórios.
- Responsabilidade de cada camada.
- Fluxo de uma requisição.
- Estratégia de rotas.
- Convenções de código.
- Decisões arquiteturais e justificativas.

#### `Manual/02-INSTALACAO.md`

Deverá conter:

- Requisitos do ambiente.
- Instalação de PHP, Composer e MySQL, conforme o ambiente.
- Instalação das dependências.
- Configuração do `.env`.
- Configuração do servidor web e document root.
- Comandos para iniciar o projeto.
- Procedimento de instalação do super-administrador.
- Soluções para erros comuns.

#### `Manual/03-BANCO-DE-DADOS.md`

Deverá conter:

- Nome e configuração do banco.
- Descrição de cada tabela.
- Campos, tipos, índices e relacionamentos.
- Como executar a migration inicial.
- Como atualizar a estrutura futuramente.
- Regras de integridade relevantes.

#### `Manual/04-AUTENTICACAO-E-ACESSO.md`

Deverá conter:

- Perfis de usuário.
- Regras de acesso.
- Fluxo de login e logout.
- Funcionamento dos middlewares.
- Sessões e CSRF.
- Instalação do primeiro super-administrador.
- Registro de auditoria.

#### `Manual/05-INTERFACE.md`

Deverá conter:

- Paleta de cores.
- Diretrizes visuais.
- Estrutura do layout administrativo.
- Estrutura da tela de login.
- Responsividade.
- Componentes reutilizáveis.
- Bibliotecas frontend e versões utilizadas.

#### `Manual/CHANGELOG.md`

Registrar a etapa concluída, data, principais arquivos criados ou alterados, funcionalidades entregues e pendências conhecidas.

### Regra para todas as próximas etapas

Sempre que uma nova funcionalidade for desenvolvida:

1. Criar ou atualizar o documento técnico correspondente em `Manual/`.
2. Atualizar o `Manual/README.md` quando houver novos documentos ou mudanças importantes.
3. Atualizar `Manual/CHANGELOG.md`.
4. Documentar alterações no banco, rotas, serviços, regras de negócio e configurações.
5. Incluir instruções de instalação, execução e teste quando aplicável.
6. Não documentar funcionalidades que não foram implementadas.
7. Não remover documentação anterior sem justificativa.
8. Manter os documentos coerentes com a versão atual do código.

A documentação deverá permitir que outro desenvolvedor ou agente de IA compreenda o projeto e continue o trabalho sem depender do histórico da conversa.

---

## 17. Critérios de aceite

A primeira etapa somente será considerada concluída quando todos os itens abaixo forem atendidos:

### Ambiente e estrutura

- [ ] Projeto organizado na estrutura MVC.
- [ ] Composer instalado e autoload funcional.
- [ ] Dependências documentadas.
- [ ] Arquivo `.env.example` criado.
- [ ] `.gitignore` configurado.
- [ ] Conexão PDO funcional.
- [ ] Servidor direcionado para `public/`.

### Banco

- [ ] Banco `loja_carrinhos` criado ou instruções de criação documentadas.
- [ ] Migration inicial executável.
- [ ] Tabela `usuarios` criada.
- [ ] Tabela `logs_auditoria` criada.
- [ ] Relacionamentos e índices testados.

### Autenticação

- [ ] Instalação segura do primeiro super-administrador.
- [ ] Login funcional.
- [ ] Logout funcional.
- [ ] Sessão protegida.
- [ ] Rotas administrativas protegidas.
- [ ] Administrador comum sem acesso ao gerenciamento de usuários.
- [ ] Super-administrador com acesso ao gerenciamento de administradores.
- [ ] Proteção CSRF funcional.

### Interface

- [ ] Login responsivo.
- [ ] Dashboard responsivo.
- [ ] Sidebar adaptável.
- [ ] Identidade visual aplicada.
- [ ] Feedback de operações.
- [ ] Gerenciamento de administradores funcional.
- [ ] Nenhum link ou botão sem ação implementada.

### Documentação

- [ ] Todos os documentos definidos na seção 16 criados.
- [ ] Instruções de instalação testadas.
- [ ] Estrutura MVC documentada.
- [ ] Banco documentado.
- [ ] Autenticação documentada.
- [ ] Interface documentada.
- [ ] Changelog atualizado.

---

## 18. Entrega final da IA

Ao terminar, apresente no terminal ou na resposta final um relatório contendo:

1. Resumo do que foi implementado.
2. Estrutura de arquivos criada.
3. Dependências instaladas e respectivas versões.
4. Configurações que o desenvolvedor ainda precisa preencher.
5. Comandos para criar o banco e executar o projeto.
6. Procedimento para criar o super-administrador.
7. URLs disponíveis.
8. Testes realizados e resultados.
9. Pendências ou limitações identificadas.
10. Lista dos documentos criados ou atualizados em `Manual/`.

Não declare a etapa concluída se houver critérios de aceite pendentes. Identifique claramente qualquer item que não pôde ser testado por falta de ambiente ou credenciais.

**Inicie inspecionando o workspace e, em seguida, implemente esta primeira etapa de maneira incremental. Não avance para os módulos de produtos, estoque, compras, pedidos ou financeiro.**
