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

- Barra de pesquisa por texto ocupando a maior parte da linha, alinhada ao botão **Nova entrada**.
- Botão **Filtrar** removido; a pesquisa é enviada pelo teclado ou pelo ícone do campo.
- Filtro de **modo** em pills: Todos, Compra direta e Previsão.
- Filtro de **recebimento** em pills, habilitado apenas quando o modo é Compra direta.
- Em previsão, as pills de recebimento ficam desabilitadas.
- A área superior (abas, pesquisa e filtros) fica fixa; apenas a lista rola.
- No mobile, ao rolar a lista, a barra reduz e mantém apenas o campo de busca. Ao voltar ao topo, o restante reaparece.
- Tabela no desktop e cards modernos no mobile, com descrição, modo, situação, itens, custos, total real, data e ações.
- Estado vazio orientando a primeira entrada.

### Nova entrada

- Formulário aberto em **modal** a partir do botão **Nova entrada**.
- Seleção visual entre **Compra direta** e **Previsão de compra**.
- Campos de link, recebimento e rastreio aparecem apenas no modo direto.
- JavaScript complementar oculta e desabilita os campos não aplicáveis.
- Validação obrigatória no backend e repopulação em caso de erro; o modal reabre automaticamente.

### Detalhes da entrada

- Cabeçalho com modo, situação, descrição, criador, link, rastreio e observações.
- Ações de editar dados, alternar recebimento e excluir entrada.
- No mobile, os botões de ação do cabeçalho mostram apenas os ícones.
- Cards de resumo: total base, custos adicionais, custo real total e itens. No mobile, cada card ocupa uma linha.
- Seção de custos adicionais com inclusão e exclusão.
- Cadastro de item em modal completo: categoria, marca, fabricante, coleção, escala, ano de fabricação, identificação, quantidade, descrição, observações, custo/oferta e **fotos do item**.
- Upload múltiplo de fotos com pré-visualização antes de salvar.
- Após salvar, o modal fecha e a lista de itens é atualizada.
- Se houver erro de validação, o modal reabre com os dados preenchidos.
- Fotos dos itens aparecem como miniaturas nas listas e no estoque.
- Seção de itens com edição em modal e exclusão.
- Para previsão, cada item possui cards com ofertas de fornecedores, indicação de menor preço, oferta selecionada, seleção e exclusão.

### Estoque

- Mesma configuração de topo da Entrada de produtos.
- Barra de pesquisa por item, código ou origem, sem o botão **Pesquisar**.
- Filtros em pills: Todos, Disponível em estoque, Em transporte, Em pré-venda.
- Cards informativos com dados reais acima da lista.
- Área superior fixa; apenas a lista rola.
- No mobile, ao rolar a lista, a barra reduz e mantém apenas a busca.
- Tabela com miniatura da foto do item, nome, origem, modo, situação, quantidade, custo real, fornecedor selecionado ou menor referência, cadastro e ação para abrir a entrada.
- Paginação preservando filtros.

### Situações visuais

- Disponível em estoque → verde (`status-pill-active`).
- Em transporte → azul (`status-pill-transit`).
- Em pré-venda → amarelo (`status-pill-pre`).
- Custo a definir → selo neutro.

## Módulo de Produtos (legado)

> O cadastro de produtos foi removido da interface nesta etapa. Esta seção é mantida como registro histórico; o menu **Produtos** não é mais exibido e os itens passaram a preencher categoria, marca, fabricante, coleção, escala e ano diretamente no módulo Loja.

Arquivos: `app/Views/produtos/**`, `public/assets/css/produtos.css` e `public/assets/js/produtos.js`.

### Listagem

- Acesso pelo menu **Loja → Produtos**.
- Toolbar com pesquisa por nome/SKU, filtros por categoria, marca, coleção, escala e status, além de ordenação.
- Prévia das linhas da loja: **Novidades** e **Destaques**.
- Tabela em telas maiores e cards em dispositivos menores.
- Miniatura, nome, SKU, categoria, marca, coleção, escala, quantidades por situação e status.
- Paginação de 20 registros preservando filtros.

### Formulário

- Abas **Informações principais**, **Características** e **Comercial e estoque**.
- Botões de cadastro rápido ao lado dos selects de categoria, marca, fabricante, coleção e escala.
- Modal de cadastro rápido via AJAX, sem perder os dados já preenchidos.
- Máscara monetária de 3 casas decimais no preço de venda.
- A seção de imagens aparece após o produto ser salvo.

### Imagens

- Carrossel com indicadores, setas e miniaturas.
- Upload múltiplo com pré-visualização antes de salvar.
- Lista de imagens com indicação de imagem principal.
- Ações de tornar principal, mover para cima/baixo, substituir e excluir.
- Limite de formatos e tamanho informado na interface.
- Imagem principal destacada com selo **Principal**.

### Detalhes

- Carrossel principal com miniaturas.
- Resumo do produto, preços, características e conservação.
- Cards de estoque e custo médio real.
- Histórico de entradas.
- Prévia do card reutilizável da loja.

## Menu Configurações

O menu lateral ganhou uma seção recolhível **Configurações**, com:

- Categorias
- Marcas
- Fabricantes
- Coleções
- Escalas

Cada item abre uma listagem com cadastro, edição, ativação/inativação e exclusão. A exclusão é bloqueada quando há produtos vinculados. As páginas usam abas de navegação entre os tipos e o componente `.config-tabs`.

## Componentes reutilizáveis

- Botão principal `.btn-brand`.
- Botões de ícone `.btn-icon`.
- Selos `.badge-soft` e `.badge-soft-yellow`.
- Status `.status-pill` com variantes ativa/inativa/transporte/pré-venda.
- Cards `.metric-card`, `.panel-card` e `.loja-summary-card`.
- Estados vazios `.empty-state`.
- Toolbar de pesquisa `.toolbar` e barra de busca `.loja-entry-search`.
- Tabela `.admin-table`, `.loja-table`.
- Ações de tabela `.table-actions`.
- Abas `.loja-tabs` e `.config-tabs`, filtros `.loja-filter-pill` e paginação `.loja-pagination`.
- Área fixa `.loja-entries-top`, cards mobile `.loja-entry-card` e miniaturas `.loja-item-thumb`.
- Modal de item `.loja-item-modal-form` com pré-visualização de fotos `.image-preview-grid`.
- Menu lateral recolhível `.admin-nav-collapse` e sublinks `.admin-nav-sublink`.
- Alertas flash do Bootstrap.
- Páginas de erro 403, 404, 405, 419 e 500.

## Responsividade

- Grid do Bootstrap e breakpoints padrão (`sm`, `md`, `lg`, `xl`).
- Sidebar oculta abaixo de `992px` e acessível via offcanvas.
- Login em coluna única abaixo de `992px`.
- Cards adaptam de 1 para 2 e 4 colunas conforme a largura.
- Formulários empilham campos no mobile.
- Tabelas usam rolagem horizontal.
- O gerenciador de imagens empilha miniatura e ações no mobile.
- Botões de ação ocupam largura total em telas muito pequenas.

## Acessibilidade

- Labels associados aos campos.
- `aria-label`, `aria-pressed`, `aria-current` e `aria-hidden` nos componentes interativos.
- Foco visível em campos e botões.
- Contraste adequado entre texto e fundo.
- Navegação por teclado nos formulários e no offcanvas.
- Imagens do carrossel e dos cards com texto alternativo.
- Setas e miniaturas do carrossel com `aria-label`.
- Confirmação de ações destrutivas.
- Funcionamento básico sem animações.

## Bibliotecas frontend e versões

| Biblioteca | Versão | Origem |
|---|---|---|
| Bootstrap CSS/JS | 5.3.3 | jsDelivr CDN |
| Bootstrap Icons | 1.11.3 | jsDelivr CDN |
| jQuery | 3.7.1 | code.jquery.com |
| Inter | Google Fonts (sem versão fixada) | Google Fonts |
| JavaScript próprio | — | `app.js`, `admin.js`, `loja.js` e `masks.js` |

O CSS próprio (`style.css`, `auth.css`, `admin.css`, `loja.css` e `configuracoes.css`) complementa o Bootstrap e não substitui sua estrutura responsiva.

O `masks.js` aplica máscara monetária de 3 casas decimais a qualquer campo com o atributo `data-money`.
