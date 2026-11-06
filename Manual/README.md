# Manual do Sistema — Loja de Carrinhos

## Visão geral

Este manual documenta as **Etapas 01 e 02** e os ajustes do módulo Loja do sistema próprio de gerenciamento da loja virtual de carrinhos colecionáveis. A Etapa 01 entregou a fundação técnica: estrutura MVC, configuração de ambiente, banco inicial, autenticação, controle de acesso, layout administrativo e gerenciamento de administradores. A Etapa 02 entregou o módulo Loja, com entrada de produtos e estoque. Os ajustes posteriores adicionaram informações de catálogo diretamente nos itens, fotos dos itens, nova entrada em modal e padronização da tela de Estoque.

O cadastro de produtos foi removido da interface; os documentos 09 e 11 são mantidos apenas como registro histórico. A loja virtual pública e os módulos de vendas, pedidos, pagamentos, financeiro e frete **não fazem parte destas entregas**.

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
| jQuery 3.7.1 | Máscaras monetárias e AJAX |
| HTML5, CSS3 e JavaScript | Interface, máscaras e interações |

## Organização da documentação

| Documento | Conteúdo |
|---|---|
| [01-ARQUITETURA.md](01-ARQUITETURA.md) | Arquitetura MVC, camadas, fluxo de requisição e decisões |
| [02-INSTALACAO.md](02-INSTALACAO.md) | Requisitos, instalação, configuração e execução |
| [03-BANCO-DE-DADOS.md](03-BANCO-DE-DADOS.md) | Estrutura do banco, tabelas, índices e migrations |
| [04-AUTENTICACAO-E-ACESSO.md](04-AUTENTICACAO-E-ACESSO.md) | Perfis, login, sessão, CSRF, middlewares e auditoria |
| [05-INTERFACE.md](05-INTERFACE.md) | Identidade visual, layouts, componentes e responsividade |
| [06-MODULO-LOJA.md](06-MODULO-LOJA.md) | Entrada de produtos, previsão de compra, custos, estoque e rotas |
| [09-CADASTRO-DE-PRODUTOS.md](09-CADASTRO-DE-PRODUTOS.md) | Histórico do cadastro de produtos removido da interface |
| [10-CATEGORIAS-MARCAS-COLECOES.md](10-CATEGORIAS-MARCAS-COLECOES.md) | Cadastros auxiliares usados pelos itens e menu Configurações |
| [11-COMPONENTES-VISUAIS-PRODUTOS.md](11-COMPONENTES-VISUAIS-PRODUTOS.md) | Histórico dos componentes visuais de produtos |
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
- Nova entrada de produtos em modal.
- Itens com categoria, marca, fabricante, coleção, escala e ano de fabricação.
- Fotos dos itens com upload múltiplo, pré-visualização e miniaturas nas listas.
- Cadastros auxiliares de categorias, marcas, fabricantes, coleções e escalas no menu Configurações.
- Listagem de Entrada de produtos com pesquisa, filtros em pills, topo fixo e cards no mobile.
- Tela de Estoque com topo fixo, pesquisa compacta no scroll mobile e cards informativos acima da lista.
- Botões de ação em ícone no mobile na tela de detalhes da entrada.
- Precisão monetária de 3 casas decimais com máscara nos formulários.

## Limitações conhecidas

- Não há recuperação de senha.
- Não há paginação na listagem de administradores.
- O bloqueio por tentativas de login é baseado na sessão, não em IP global.
- Não há conversão automática de item em pré-venda para compra direta.
- A loja pública ainda não foi implementada.
- Não há módulos de vendas, pedidos, pagamentos, frete ou carrinho.
- Não há variações de item, código de barras, importação/exportação ou exclusão em massa.
- Não há tela posterior para gerenciar as fotos de um item já cadastrado.
- O ambiente desta entrega usou uma instância MariaDB isolada na porta `3307` porque a conta administrativa do serviço local utiliza autenticação GSSAPI. O `.env` local reflete essa configuração de teste; para uso definitivo, ajuste as credenciais.
