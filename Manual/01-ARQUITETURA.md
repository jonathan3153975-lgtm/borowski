# 01 — Arquitetura

## Visão geral

O projeto utiliza uma arquitetura **MVC própria**, sem framework completo, com separação clara entre entrada HTTP, roteamento, controllers, services, models, views e infraestrutura. A pasta `public/` é o único document root permitido.

## Estrutura de diretórios

```text
app/
├── Core/            Controller, Database, Model, Router e View
├── Controllers/     Auth, Dashboard, User, Configuracao,
│                    Loja/{Entrada,Estoque,ItemImagem}
├── Middleware/      AuthMiddleware e GuestMiddleware
├── Models/          User, Configuracao e Loja/*
├── Services/        AuditService, UserService, ConfiguracaoService e Loja/*
├── Views/           layouts, auth, dashboard, users, configuracoes,
│                    loja e errors
└── Helpers/         functions.php

config/              app.php e database.php
database/            migrations/001... até 007... e seeds/
Manual/              Documentação técnica
public/              index.php, .htaccess e assets (css, js, img)
routes/              web.php, admin.php, loja.php e configuracoes.php
scripts/             migrate.php, create-super-admin.php e testar-calculo-custos.php
storage/             logs/, temp/ e uploads/loja_itens/
vendor/              Dependências geradas pelo Composer
```

## Responsabilidade de cada camada

- **public/index.php:** única entrada HTTP. Carrega autoload e `.env`, configura timezone, erros, sessão, CSRF e roteador.
- **Core/Router:** registra rotas por método HTTP, resolve parâmetros `{id}`, executa middlewares e responde 404/405.
- **Core/View:** renderiza views internas com dados explícitos e suporta layouts. O caminho é resolvido dentro de `app/Views`, impedindo inclusão arbitrária de arquivos.
- **Core/Database:** fornece a conexão PDO única com charset `utf8mb4`, exceções e prepared statements sem emulação.
- **Core/Model:** classe base mínima que compartilha a conexão PDO. Não há ORM.
- **Core/Controller:** base para controllers, com renderização de view, redirecionamento e verificação de super-admin.
- **Controllers:** validam a requisição, chamam services e devolvem views ou redirecionamentos. O módulo Loja usa `App\Controllers\Loja`; configurações usam `ConfiguracaoController`.
- **Services:** concentram regras de negócio. `UserService` cuida de administradores; `ConfiguracaoService` mantém os cadastros auxiliares; o módulo Loja usa `EntradaService`, `PrevisaoService`, `EstoqueService` e `CalculoService`; `AuditService` centraliza a auditoria.
- **Models:** executam consultas SQL específicas com PDO e prepared statements. O catálogo auxiliar usa `Configuracao`; o módulo Loja usa `App\Models\Loja`.
- **Middleware:** bloqueiam visitantes em rotas administrativas (`AuthMiddleware`) e redirecionam usuários autenticados para fora do login (`GuestMiddleware`).
- **Views/layouts:** apresentação HTML com escape de saída, Bootstrap e CSS próprio. O layout administrativo aceita `extraStyles` e `extraScripts` por página.
- **CalculoService:** converte valores monetários em milésimos inteiros, rateia custos adicionais e distribui resíduos de arredondamento com 3 casas decimais.
- **ItemImagemService:** valida e grava fotos de itens em `storage/uploads/loja_itens`, controla a primeira foto como principal e remove arquivos ao excluir itens/entradas. A rota autenticada `/admin/loja/itens/imagem/{id}` serve as fotos.

## Fluxo de uma requisição

1. O servidor web encaminha a requisição para `public/index.php`.
2. O autoload do Composer é carregado.
3. O `.env` é lido com `vlucas/phpdotenv`.
4. Timezone, exibição de erros e log são configurados.
5. A sessão é iniciada com nome, cookie `HttpOnly`, `SameSite=Lax`, `use_strict_mode` e `Secure` quando aplicável.
6. Em requisições `POST`, o token CSRF é validado globalmente; token inválido retorna 419.
7. As rotas de `routes/web.php`, `routes/admin.php`, `routes/loja.php` e `routes/configuracoes.php` são registradas. `routes/produtos.php` não é mais carregado.
8. O `Router` normaliza o caminho, encontra a rota, executa middlewares e chama o controller.
9. O controller chama o service/model conforme necessário.
10. A view é renderizada dentro do layout escolhido e enviada ao navegador.
11. Exceções são registradas em `storage/logs/app.log`; em produção o usuário recebe uma página 500 sem detalhes internos.

## Estratégia de rotas

- Métodos suportados: `GET` e `POST`.
- Parâmetros de rota no formato `{id}`, convertidos em grupos regex.
- Rotas inexistentes retornam **404**.
- Caminho existente com método incorreto retorna **405** com cabeçalho `Allow`.
- Middlewares são declarados por rota.
- O caminho base configurado em `APP_URL` é removido da URL antes da comparação, permitindo instalação em subpasta.

## Convenções de código

- `declare(strict_types=1)` em todos os arquivos PHP próprios.
- Namespace `App\` mapeado para `app/` via PSR-4.
- Nomes de classes em PascalCase; métodos e variáveis em camelCase.
- SQL sempre com prepared statements e parâmetros nomeados.
- Saída HTML sempre escapada com `e()`.
- Mensagens de feedback via `flash()`.
- Repopulação de formulários via `flash_input()` e `old()`.
- Senhas somente com `password_hash()`/`password_verify()`.
- Nenhuma credencial em código-fonte; tudo via `.env`.

## Decisões arquiteturais

1. **Núcleo MVC próprio:** atende ao requisito de não usar Laravel ou framework completo.
2. **PDO sem ORM:** mantém o projeto simples, explícito e adequado ao tamanho atual.
3. **phpdotenv:** permite configurar banco, URL, sessão e instalação sem alterar código.
4. **Sessão nativa do PHP com opções seguras:** reduz dependências e mantém compatibilidade.
5. **CSRF global para POST:** qualquer alteração de estado exige token válido.
6. **Bootstrap por CDN com versão fixa:** evita build de frontend nesta etapa.
7. **Imagens fora de `public/`:** fotos de itens ficam em `storage/uploads/loja_itens` e são servidas por rota autenticada.
8. **Auditoria em tabela própria:** preserva eventos mesmo após exclusão de administradores. O `AuditService` é reutilizado pelos módulos.
9. **Somente um super-admin:** garantido no serviço de instalação e nas regras de gerenciamento.
10. **Cálculo monetário em milésimos:** valores usam `DECIMAL(12,3)` e cálculos inteiros para evitar erros de ponto flutuante.
11. **Módulo Loja separado por subpastas:** controllers, models, services e views do módulo ficam agrupados em `Loja/`, mantendo o núcleo enxuto.
12. **Uma oferta selecionada por item de previsão:** o custo base é definido pela oferta escolhida; sem seleção, o custo fica indefinido.
13. **Migrations com controle:** `schema_migrations` registra o que foi aplicado, permitindo `ALTER TABLE` sem reexecução.
14. **Cadastros auxiliares em tabelas próprias:** categorias, marcas, fabricantes, coleções e escalas são reutilizáveis e protegidos contra exclusão com vínculos.
15. **Módulo de produtos removido:** o cadastro de produtos, suas rotas e componentes foram retirados da aplicação. As tabelas históricas foram preservadas no banco; as informações de catálogo agora ficam diretamente em `loja_itens`.
16. **jQuery para cadastro rápido:** o AJAX de configurações usa jQuery 3.7.1; a máscara monetária de 3 casas fica em `masks.js`.
