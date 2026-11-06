# 10 — Categorias, Marcas, Fabricantes, Coleções e Escalas

## Visão geral

O sistema possui cadastros auxiliares reutilizados no cadastro de itens das entradas de produtos:

- Categorias
- Marcas
- Fabricantes
- Coleções
- Escalas

Cada cadastro fica em uma tabela própria, possui status `ativo`/`inativo`, descrição opcional e datas de criação/atualização. O gerenciamento fica no menu **Configurações** do painel administrativo.

## Acesso

O menu **Configurações** é exibido para administradores autenticados (`admin` e `super_admin`) e contém um submenu para cada tipo.

Rotas:

| Método | Rota | Ação |
|---|---|---|
| GET | `/admin/configuracoes` | Redireciona para categorias |
| GET | `/admin/configuracoes/{tipo}` | Listagem |
| GET | `/admin/configuracoes/{tipo}/criar` | Formulário de cadastro |
| POST | `/admin/configuracoes/{tipo}` | Criar |
| GET | `/admin/configuracoes/{tipo}/{id}/editar` | Formulário de edição |
| POST | `/admin/configuracoes/{tipo}/{id}` | Atualizar |
| POST | `/admin/configuracoes/{tipo}/{id}/status` | Ativar/inativar |
| POST | `/admin/configuracoes/{tipo}/{id}/excluir` | Excluir |
| POST | `/admin/configuracoes/{tipo}/rapido` | Cadastro rápido via AJAX (JSON) |

`{tipo}` aceita apenas:

- `categorias`
- `marcas`
- `fabricantes`
- `colecoes`
- `escalas`

Qualquer outro valor é rejeitado pelo service e pelo controller. Não há interpolação direta de tabelas fornecidas pelo usuário: o service usa um mapa fechado de tabelas e chaves estrangeiras.

## Campos

| Campo | Obrigatório | Regra |
|---|---|---|
| Nome | Sim | 2 a 120 caracteres, único na tabela |
| Descrição | Não | Até 255 caracteres |
| Status | Sim | `ativo` ou `inativo` |

Datas `criado_em` e `atualizado_em` são controladas pelo banco.

## Regras de negócio

- Nome duplicado é bloqueado com mensagem amigável.
- Registros inativos continuam vinculados aos itens existentes.
- A exclusão é bloqueada quando existe item usando o registro.
- Quando houver vínculo, a orientação é inativar o registro.
- O cadastro rápido cria o registro como ativo e devolve JSON para uso por formulários futuros.
- O cadastro rápido usa o mesmo service, validação, CSRF e auditoria do cadastro tradicional.

## Relacionamento com itens

| Cadastro | Coluna em `loja_itens` | Obrigatória | Ao excluir |
|---|---|---|---|
| Categoria | `categoria_id` | Não | Bloqueado pelo service; FK `SET NULL` como salvaguarda |
| Marca | `marca_id` | Não | Bloqueado pelo service; FK `SET NULL` como salvaguarda |
| Fabricante | `fabricante_id` | Não | Bloqueado pelo service; FK `SET NULL` como salvaguarda |
| Coleção | `colecao_id` | Não | Bloqueado pelo service; FK `SET NULL` como salvaguarda |
| Escala | `escala_id` | Não | Bloqueado pelo service; FK `SET NULL` como salvaguarda |

Os campos são preenchidos diretamente no modal de cadastro do item. O item também possui `ano_fabricacao` como campo numérico opcional. O cadastro auxiliar `fabricantes` representa o fabricante do carrinho; o fabricante do veículo original não faz parte desta etapa.

## Cadastro rápido (endpoint JSON)

O endpoint `POST /admin/configuracoes/{tipo}/rapido` permanece disponível e cria o registro como ativo, devolvendo:

```json
{ "ok": true, "item": { "id": 1, "nome": "..." } }
```

O cadastro de itens usa atualmente os selects tradicionais com os registros já cadastrados. O endpoint fica preparado para futuros formulários com botão **+** e AJAX.

## Tabelas

Todas usam InnoDB, `utf8mb4` e `utf8mb4_unicode_ci`:

- `categorias`
- `marcas`
- `fabricantes`
- `colecoes`
- `escalas`

Cada uma possui:

- `id INT UNSIGNED` PK
- `nome VARCHAR(120)` NOT NULL, UNIQUE
- `descricao VARCHAR(255)` NULL
- `status ENUM('ativo','inativo')` NOT NULL DEFAULT 'ativo'
- `criado_em DATETIME` NOT NULL
- `atualizado_em DATETIME` NULL

## Arquivos

- `app/Models/Configuracao.php`
- `app/Services/ConfiguracaoService.php`
- `app/Controllers/ConfiguracaoController.php`
- `app/Views/configuracoes/index.php`
- `app/Views/configuracoes/create.php`
- `app/Views/configuracoes/edit.php`
- `app/Views/configuracoes/partials/form.php`
- `app/Views/configuracoes/partials/tabs.php`
- `routes/configuracoes.php`
- `public/assets/css/configuracoes.css`

## Auditoria

Ações registradas:

- `configuracao_criada`
- `configuracao_atualizada`
- `configuracao_status`
- `configuracao_excluida`

As descrições incluem tipo, nome e ID, sem dados sensíveis.

## Testes realizados

- Cadastro dos cinco tipos auxiliares.
- Bloqueio de nome duplicado.
- Cadastro rápido via AJAX.
- Listagem e edição.
- Ativação/inativação.
- Bloqueio de exclusão de categoria vinculada a item.
- Exclusão após remoção do item.
- Proteção de rotas por autenticação e CSRF.

Resultado: todos os testes dos ajustes do módulo Loja passaram.
