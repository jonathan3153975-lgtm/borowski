# 04 — Autenticação e Controle de Acesso

## Perfis

| Perfil | Descrição | Acesso |
|---|---|---|
| `super_admin` | Proprietário do sistema | Dashboard e gerenciamento de administradores |
| `admin` | Administrador operacional | Dashboard, sem gerenciamento de administradores |

Não existem permissões individuais por módulo nesta etapa.

## Matriz de acesso

| Método | Rota | Visitante | `admin` | `super_admin` |
|---|---|---|---|---|
| GET | `/` | Redireciona para `/login` | Redireciona para `/admin` | Redireciona para `/admin` |
| GET | `/login` | Permitido | Redireciona para `/admin` | Redireciona para `/admin` |
| POST | `/login` | Permitido | Redireciona para `/admin` | Redireciona para `/admin` |
| POST | `/logout` | Bloqueado | Permitido | Permitido |
| GET | `/admin` | Bloqueado | Permitido | Permitido |
| GET | `/admin/usuarios` | Bloqueado | 403 | Permitido |
| GET | `/admin/usuarios/criar` | Bloqueado | 403 | Permitido |
| POST | `/admin/usuarios` | Bloqueado | 403 | Permitido |
| GET | `/admin/usuarios/{id}/editar` | Bloqueado | 403 | Permitido |
| POST | `/admin/usuarios/{id}/editar` | Bloqueado | 403 | Permitido |
| POST | `/admin/usuarios/{id}/status` | Bloqueado | 403 | Permitido |
| POST | `/admin/usuarios/{id}/excluir` | Bloqueado | 403 | Permitido |
| GET | `/admin/loja`, `/admin/loja/entradas` e `/admin/loja/estoque` | Bloqueado | Permitido | Permitido |
| POST | `/admin/loja/**` | Bloqueado | Permitido | Permitido |

Ocultar o menu na interface é apenas melhoria de experiência. A autorização real ocorre sempre no backend. O módulo Loja é operacional e está disponível para os dois perfis administrativos.

## Fluxo de login

1. O usuário acessa `GET /login`; o `GuestMiddleware` impede acesso de quem já está autenticado.
2. O formulário inclui token CSRF.
3. O `POST /login` valida o CSRF globalmente em `public/index.php`.
4. `AuthController` aplica o bloqueio temporário por tentativas.
5. `UserService::authenticate()`:
   - normaliza o e-mail;
   - busca o usuário por e-mail com prepared statement;
   - executa `password_verify()` contra o hash real ou contra um hash fictício fixo, reduzindo diferença de tempo para e-mails inexistentes;
   - exige usuário existente, senha correta e status `ativo`;
   - em falha, registra auditoria `login_falha` e retorna mensagem genérica;
   - em sucesso, atualiza `ultimo_acesso`, registra `login_sucesso` e chama `login_user()`.
6. `login_user()` regenera o ID da sessão, grava apenas `id`, `nome`, `email` e `tipo`, e invalida o token CSRF anterior.
7. O usuário é redirecionado para `/admin`.

Mensagem de erro padrão: `E-mail ou senha inválidos.` Ela não revela se o e-mail existe, se a senha está errada ou se o usuário está inativo.

## Fluxo de logout

1. `POST /logout` exige autenticação e token CSRF.
2. O evento é registrado em auditoria.
3. A sessão é esvaziada, o cookie é expirado e a sessão é destruída.
4. Uma nova sessão é iniciada apenas para exibir a mensagem de sucesso.
5. O usuário é redirecionado para `/login`.

## Middleware

### `AuthMiddleware`

- Executado nas rotas administrativas.
- Sem sessão autenticada: grava mensagem flash e redireciona para `/login`.

### `GuestMiddleware`

- Executado em `GET /login` e `POST /login`.
- Com sessão autenticada: redireciona para `/admin`.

### Verificação de super-admin

As rotas de gerenciamento chamam `Controller::requireSuperAdmin()` no início de cada ação. Sem perfil `super_admin`, a resposta é **403** com uma página de acesso negado. A regra também é reforçada no `UserService`.

## Sessão

Configuração aplicada em `public/index.php`:

- Nome definido por `SESSION_NAME`.
- Cookie `HttpOnly`.
- `SameSite=Lax`.
- `Secure` quando `SESSION_SECURE_COOKIE=true` ou quando a requisição é HTTPS.
- `session.use_strict_mode=1`.
- `session.use_only_cookies=1`.
- Regeneração do ID após autenticação.
- Apenas dados não sensíveis são armazenados na sessão; nunca senha ou hash.
- Logout destrói a sessão e invalida o cookie.

## CSRF

- Token aleatório de 64 caracteres hexadecimais, gerado com `random_bytes(32)`.
- Armazenado na sessão.
- Incluído em todos os formulários por `csrf_field()`.
- Validado globalmente em toda requisição `POST` com `hash_equals()`.
- Token inválido ou ausente retorna **419** e uma página de sessão expirada.
- O token é renovado após login.

Observação: o token é mantido durante a sessão autenticada e não é rotacionado a cada formulário.

## Bloqueio por tentativas de login

Mecanismo simples baseado na sessão:

- Máximo de **5 tentativas** dentro de **15 minutos**.
- Ao atingir o limite, o login fica bloqueado por **15 minutos**.
- O evento `login_bloqueado` é registrado na auditoria.
- Login bem-sucedido limpa o contador.
- A validação ocorre no backend, independentemente de JavaScript.

Limitação conhecida: por ser baseado na sessão, um atacante pode contornar o limite iniciando novas sessões. Para produção com maior exposição, recomenda-se evoluir para limitação por IP/e-mail em banco ou cache.

## Instalação do primeiro super-administrador

Procedimento preferencial via CLI:

```bash
php scripts/create-super-admin.php
```

Garantias:

1. Exige execução em CLI; acesso HTTP recebe 403.
2. Confirma conexão e existência das tabelas.
3. Interrompe se já existir super-admin.
4. Valida nome, e-mail e força da senha.
5. Gera hash com `password_hash()`.
6. Cria o registro como `super_admin` e `ativo`.
7. Registra auditoria sem armazenar a senha.

Não existe instalador web nesta entrega. A `INSTALL_KEY` está reservada em `.env` para eventual evolução, mas não é utilizada por nenhuma rota atual.

## Proteção do super-administrador

- O sistema permite apenas um super-admin.
- A criação de administradores pela interface sempre usa o tipo `admin`.
- `updateAdmin`, `toggleStatus` e `deleteAdmin` recusam alvos do tipo `super_admin`.
- A exclusão do próprio usuário autenticado é recusada no service.
- O super-admin aparece na listagem como registro protegido, sem ações de edição, status ou exclusão.
- A listagem permite pesquisa por nome ou e-mail.

## Auditoria

A tabela `logs_auditoria` preserva os eventos mesmo após a exclusão de administradores. O `usuario_id` referencia o ator da ação; quando o usuário é excluído, o campo passa a `NULL` por `ON DELETE SET NULL`.

O `AuditService` centraliza o registro e é reutilizado pelo `UserService` e pelos services do módulo Loja. As ações do módulo estão listadas em [06-MODULO-LOJA.md](06-MODULO-LOJA.md#auditoria).

Nunca são registrados:

- senhas;
- hashes de senha;
- tokens CSRF ou de sessão;
- chave de instalação;
- conteúdo completo de formulários;
- URLs completas de fornecedores com possíveis parâmetros sensíveis.

## Testes realizados

Roteiro automatizado via HTTP, com banco isolado de teste:

- Redirecionamentos de visitante e de usuário autenticado.
- Login inválido com mensagem genérica.
- Login válido e carregamento do dashboard.
- CSRF ausente retornando 419.
- Criação, edição, inativação, reativação e exclusão de administrador.
- Bloqueio de edição do super-admin.
- Acesso de `admin` comum ao gerenciamento retornando 403.
- Logout e nova exigência de autenticação.
- Bloqueio após 5 tentativas inválidas.
- Rotas inexistentes (404) e método incorreto (405).
- Proteção das rotas do módulo Loja.
- Auditoria do módulo Loja sem dados sensíveis.

Resultado: **todos os testes passaram**. Detalhes do módulo em [06-MODULO-LOJA.md](06-MODULO-LOJA.md).
