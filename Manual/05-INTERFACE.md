# 05 — Interface

## Identidade visual

A identidade usa azul escuro institucional como base, superfícies brancas e amarelo marca-texto em ações principais e destaques. O amarelo é usado com moderação e nunca em textos longos.

### Paleta

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
| Sucesso | `#1F8A5B` |
| Perigo | `#B42318` |

As variáveis ficam centralizadas em `:root` em `public/assets/css/style.css`.

### Tipografia

- Fonte principal: **Inter** (Google Fonts), com fallback para `Segoe UI`, `system-ui` e `sans-serif`.
- Títulos com peso 700/800 e leve espaçamento negativo.
- Textos de apoio em tamanho reduzido e cor secundária.

## Estrutura do layout administrativo

Arquivo: `app/Views/layouts/admin.php`.

- **Sidebar** azul escuro com marca provisória `LC`, nome configurável, menu e botão Sair.
- **Topbar** branca com botão de recolher/abrir menu, breadcrumb e identificação do usuário autenticado.
- **Conteúdo** em fundo cinza claro, com título de página, mensagens flash e cards.
- **Menu:** Dashboard e Administradores (somente super-admin), além de Sair.
- **Recolhimento no desktop:** o botão da topbar reduz a sidebar para ícones e persiste a preferência em `localStorage`.
- **Mobile:** a sidebar é ocultada e substituída por um offcanvas do Bootstrap.
- **Sair:** formulário `POST` com token CSRF.
- **Nenhum link falso:** apenas destinos implementados aparecem no menu.

## Tela de login

Arquivo: `app/Views/layouts/auth.php` + `app/Views/auth/login.php`.

- Composição em duas colunas no desktop: marca em azul escuro e card de formulário.
- No mobile, a marca vira um cabeçalho compacto e o card se sobrepõe levemente.
- Campos de e-mail e senha com foco visível.
- Botão de mostrar/ocultar senha acessível por teclado.
- Botão principal amarelo com estado de carregamento no envio.
- Mensagens de sucesso e erro em alerts do Bootstrap.
- Espaço reservado para marca provisória; não há logotipo definitivo nesta etapa.

## Dashboard inicial

Arquivo: `app/Views/dashboard/index.php`.

- Saudação com o nome do usuário autenticado.
- Cards demonstrativos:
  - Vendas do mês — aguardando módulo de vendas.
  - Pedidos recebidos — aguardando módulo de pedidos.
  - Produtos cadastrados — aguardando módulo de produtos.
  - Estoque baixo — aguardando módulo de estoque.
- Os cards exibem `—` e a etiqueta **Demonstrativo**, sem números fictícios apresentados como reais.
- Área de gráfico com estado vazio.
- Área de últimos pedidos com estado vazio.
- Aviso explícito de que os dados são ilustrativos.

## Gerenciamento de administradores

Arquivos: `app/Views/users/index.php`, `create.php` e `edit.php`.

- Pesquisa por nome ou e-mail via `GET`.
- Botão **Novo administrador**.
- Tabela responsiva com nome, e-mail, perfil, status, último acesso, cadastro e ações.
- Super-admin identificado com selo **Superadministrador** e indicador **Protegido**, sem ações.
- Administradores comuns podem ser editados, ativados/inativados e excluídos.
- Ações destrutivas exigem confirmação.
- Formulários com validação HTML complementar e validação obrigatória no backend.
- Feedback de sucesso e erro via alerts.
- Repopulação de nome/e-mail após erro de validação.
- Senha nunca é preenchida em formulários.

## Módulo Loja

Arquivos: `app/Views/loja/**`, `public/assets/css/loja.css` e `public/assets/js/loja.js`.

### Abas

- **Entrada de produtos** (`/admin/loja/entradas`)
- **Estoque** (`/admin/loja/estoque`)

As abas usam pills do Bootstrap com estado ativo em azul escuro.

### Entrada de produtos

- Toolbar com pesquisa, filtros por modo e recebimento, botão **Nova entrada**.
- Tabela com descrição, modo, situação, itens, custos adicionais, total real, data e ações.
- Estado vazio orientando a primeira entrada.

### Nova entrada

- Seleção visual entre **Compra direta** e **Previsão de compra**.
- Campos de link, recebimento e rastreio aparecem apenas no modo direto.
- JavaScript complementar oculta e desabilita os campos não aplicáveis.
- Validação obrigatória no backend e repopulação em caso de erro.

### Detalhes da entrada

- Cabeçalho com modo, situação, descrição, criador, link, rastreio e observações.
- Ações de editar dados, alternar recebimento e excluir entrada.
- Cards de resumo: total base, custos adicionais, custo real total e itens.
- Seção de custos adicionais com inclusão e exclusão.
- Seção de itens com inclusão, edição em modal e exclusão.
- Para previsão, cada item possui cards com ofertas de fornecedores, indicação de menor preço, oferta selecionada, seleção e exclusão.

### Estoque

- Filtros em pills: Todos, Disponível em estoque, Em transporte, Em pré-venda.
- Pesquisa por item, código ou origem.
- Cards de resumo com dados reais.
- Tabela com item, origem, modo, situação, quantidade, custo real, fornecedor selecionado ou menor referência, cadastro e ação para abrir a entrada.
- Paginação preservando filtros.

### Situações visuais

- Disponível em estoque → verde (`status-pill-active`).
- Em transporte → azul (`status-pill-transit`).
- Em pré-venda → amarelo (`status-pill-pre`).
- Custo a definir → selo neutro.

## Componentes reutilizáveis

- Botão principal `.btn-brand`.
- Botões de ícone `.btn-icon`.
- Selos `.badge-soft` e `.badge-soft-yellow`.
- Status `.status-pill` com variantes ativa/inativa/transporte/pré-venda.
- Cards `.metric-card`, `.panel-card` e `.loja-summary-card`.
- Estados vazios `.empty-state`.
- Toolbar de pesquisa `.toolbar`.
- Tabela `.admin-table` e `.loja-table`.
- Ações de tabela `.table-actions`.
- Abas `.loja-tabs`, filtros `.loja-filter-pill` e paginação `.loja-pagination`.
- Alertas flash do Bootstrap.
- Páginas de erro 403, 404, 405, 419 e 500.

## Responsividade

- Grid do Bootstrap e breakpoints padrão (`sm`, `md`, `lg`, `xl`).
- Sidebar oculta abaixo de `992px` e acessível via offcanvas.
- Login em coluna única abaixo de `992px`.
- Cards adaptam de 1 para 2 e 4 colunas conforme a largura.
- Formulários empilham campos no mobile.
- Tabelas usam rolagem horizontal.
- Botões de ação ocupam largura total em telas muito pequenas.

## Acessibilidade

- Labels associados aos campos.
- `aria-label`, `aria-pressed`, `aria-current` e `aria-hidden` nos componentes interativos.
- Foco visível em campos e botões.
- Contraste adequado entre texto e fundo.
- Navegação por teclado nos formulários e no offcanvas.
- Confirmação de ações destrutivas.
- Funcionamento básico sem animações.

## Bibliotecas frontend e versões

| Biblioteca | Versão | Origem |
|---|---|---|
| Bootstrap CSS/JS | 5.3.3 | jsDelivr CDN |
| Bootstrap Icons | 1.11.3 | jsDelivr CDN |
| Inter | Google Fonts (sem versão fixada) | Google Fonts |
| JavaScript próprio | — | `public/assets/js/app.js` e `admin.js` |

O CSS próprio (`style.css`, `auth.css`, `admin.css`) complementa o Bootstrap e não substitui sua estrutura responsiva.
