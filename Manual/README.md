# Manual do Sistema — Loja de Carrinhos

## Visão geral

Este manual documenta as **Etapas 01 e 02** do sistema próprio de gerenciamento da loja virtual de carrinhos colecionáveis. A Etapa 01 entregou a fundação técnica: estrutura MVC, configuração de ambiente, banco inicial, autenticação, controle de acesso, layout administrativo e gerenciamento de administradores. A Etapa 02 entregou o módulo Loja, com entrada de produtos e estoque.

A loja virtual pública e os módulos de vendas, pedidos, pagamentos, financeiro e frete **não fazem parte destas etapas**. A arquitetura foi preparada para receber esses módulos posteriormente.

## Tecnologias utilizadas

| Tecnologia | Uso |
|---|---|
| PHP 8.2+ | Backend e núcleo MVC próprio |
| MySQL 8 ou MariaDB compatível | Banco de dados |
| PDO | Acesso ao banco com prepared statements |
| Composer | Autoload PSR-4 e dependências |
| vlucas/phpdotenv | Leitura de variáveis de ambiente |
| Bootstrap 5.3.3 | Base responsiva e componentes |
| Bootstrap Icons 1.11.3 | Ícones da interface |
| HTML5, CSS3 e JavaScript puro | Interface e interações |

## Organização da documentação

| Documento | Conteúdo |
|---|---|
| [01-ARQUITETURA.md](01-ARQUITETURA.md) | Arquitetura MVC, camadas, fluxo de requisição e decisões |
| [02-INSTALACAO.md](02-INSTALACAO.md) | Requisitos, instalação, configuração e execução |
| [03-BANCO-DE-DADOS.md](03-BANCO-DE-DADOS.md) | Estrutura do banco, tabelas, índices e migrations |
| [04-AUTENTICACAO-E-ACESSO.md](04-AUTENTICACAO-E-ACESSO.md) | Perfis, login, sessão, CSRF, middlewares e auditoria |
| [05-INTERFACE.md](05-INTERFACE.md) | Identidade visual, layouts, componentes e responsividade |
| [06-MODULO-LOJA.md](06-MODULO-LOJA.md) | Entrada de produtos, previsão de compra, custos, estoque e rotas |
| [CHANGELOG.md](CHANGELOG.md) | Registro das etapas, testes e pendências |

## Situação atual

Concluído nesta etapa:

- Estrutura de diretórios MVC.
- Composer, autoload e variáveis de ambiente.
- Conexão PDO com MySQL/MariaDB.
- Núcleo de rotas, controllers, views e models.
- Layout de autenticação e layout administrativo.
- Tela de login responsiva.
- Autenticação, sessão segura, logout e CSRF.
- Perfis `super_admin` e `admin`.
- Proteção de rotas administrativas no backend.
- Procedimento CLI para o primeiro super-administrador.
- Dashboard inicial com estados vazios claramente identificados.
- Gerenciamento de administradores restrito ao super-admin.
- Migration inicial e documentação técnica.
- Módulo Loja com abas Entrada de produtos e Estoque.
- Compra direta com link, recebimento, rastreio, custos adicionais e custo real.
- Previsão de compra com múltiplos fornecedores por item e seleção de oferta.
- Estoque com filtros por disponível, em transporte e em pré-venda.
- Auditoria centralizada em `AuditService`.

## Limitações conhecidas desta etapa

- Não há recuperação de senha.
- Não há paginação na listagem de administradores.
- O bloqueio por tentativas de login é baseado na sessão, não em IP global.
- Não há conversão automática de item em pré-venda para compra direta.
- Não há módulos de vendas, pedidos, pagamentos, frete ou loja virtual pública.
- O ambiente desta entrega usou uma instância MariaDB isolada na porta `3307` porque a conta administrativa do serviço local utiliza autenticação GSSAPI. O `.env` local reflete essa configuração de teste; para uso definitivo, ajuste as credenciais.
