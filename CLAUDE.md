# CLAUDE.md — Instruções para IA neste projeto

Leia este arquivo antes de fazer qualquer alteração no projeto **ebi_selfservice**.
Estas regras são padrão obrigatório para toda IA que trabalhar aqui.

---

## Stack e arquitetura

- **PHP 8.5+** sem framework, sem autoloader
- **SQLite** com WAL mode (`PRAGMA journal_mode=WAL; PRAGMA synchronous=NORMAL;`)
- **Dois bancos**: `selfservice/data/ebi.db` (central) e `ebi/i/user_XXX/data/instance.db` (por instância)
- **Thin stubs**: novas instâncias em `ebi/i/user_XXX/` recebem stubs mínimos que definem `INSTANCE_DIR` e fazem `require` para `ebi/template/`

## Senhas

- Toda senha deve ser armazenada **exclusivamente como hash bcrypt** (cost 12):
  ```php
  password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12])
  ```
- **Senha padrão de fábrica em todo o sistema: `Senha123!`**
- Hash bcrypt de `Senha123!` (cost 12): `$2y$12$BPPI8U9mvBmGP/kI0pH/n.PUkkn/cB/9qrOaePiKcVy.vitwF7VsW`
- Nunca armazenar senha em texto plano. Os campos legados `SENHA_ADMIN_REAL` e `SENHA_PAINEL` devem ficar vazios.

## Estatísticas

- As tabelas `stats_daily` e `admin_daily_stats` **nunca armazenam nomes de crianças**
- Armazenar apenas contagens, faixas etárias (age_0_3, age_4_7, age_8_11, age_12_14, age_15_17) e JSONs com chaves de portaria/comum

## Segurança

- CSRF obrigatório em todos os POST (`csrf_validate()` / `csrf_field()`)
- Arquivos SQLite devem ter `chmod(0600)` aplicado na criação
- `sanitize_for_html()` em toda saída HTML

## Versionamento de páginas — REGRA OBRIGATÓRIA

**Sempre que modificar ou criar uma página PHP que gera HTML**, adicionar no final do `<body>` (antes de `</body>`) o seguinte rodapé de versão:

```php
<div class="text-center mt-4 mb-2" style="font-size:9px;color:#b0b0b0;opacity:0.6">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
```

### Como funciona

- `VERSAO_SISTEMA` é definido automaticamente nos bootstraps do sistema no formato `aaaammddhhMM` (ex: `202607141530`)
- O valor vem do último commit git (`git log -1 --format=%cd --date=format:'%Y%m%d%H%M'`)
- O fallback `date('YmdHi')` é usado se o git não estiver disponível

### Onde VERSAO_SISTEMA já é definido

| Contexto | Arquivo que define |
|---|---|
| `ebi/template/` (EBI principal) | `ebi/template/inc/bootstrap.php` via `obter_versao_sistema()` |
| `ebi/template/saida/` (portaria) | `ebi/template/saida/inc/bootstrap.php` |
| `selfservice/` (painel admin, cadastro) | `selfservice/inc/paths.php` |

### Páginas que já têm o rodapé

- `ebi/template/views/main.php` ✓
- `ebi/template/views/login.php` ✓
- `ebi/template/saida/index.php` ✓
- `ebi/template/saida/painel.php` ✓
- `selfservice/selfservice.php` ✓
- `selfservice/admin.php` ✓
- `selfservice/recuperar_senha.php` ✓

### Quando NÃO adicionar

- Arquivos de include (`inc/*.php`, `views/` parciais sem `</body>`)
- Scripts CLI
- Endpoints AJAX que retornam JSON

---

## Banco de dados — não commitar

Arquivos `.db` estão no `.gitignore`. Nunca commitar `ebi.db` nem `instance.db`.

## Padrões de commit

Seguir o padrão `tipo(escopo): mensagem`:
- `feat(sqlite):` — nova funcionalidade de banco
- `fix(bootstrap):` — correção de bug
- `ui(selfservice):` — mudança visual
- `chore:` — configuração, gitignore, CLAUDE.md

---

## Estado Atual Verificado (2026-07-14)

- Integridade sintática: `267` arquivos PHP validados com `php -l` (sem erros).
- Caminho canônico de ambiente: o `selfservice` usa `.env` na raiz do projeto (`PROJECT_ROOT/.env`).
- Instalação/admin: `selfservice/install.php` e `selfservice/admin.php` estão alinhados para `ADMIN_PASSWORD_HASH` no mesmo `.env`.
- Segurança de credenciais: `selfservice/install.php` não deve persistir senha de admin em texto plano no arquivo `.instalado`.

## Pendências Estruturais Conhecidas

- `selfservice/cleanup_instances.php` ainda lê atividade em `config/.lastaccess`, mas o bootstrap atual grava `.lastaccess` na raiz da instância.
- Fluxo de cadastro em `selfservice/selfservice.php` precisa tratar retorno de `db_inserir_usuario()` e rollback lógico quando a criação da instância falhar.
- Tabela `ss_users` (`selfservice/inc/db_manager.php`) não possui `UNIQUE(email)`; definir política oficial (permitir ou bloquear múltiplas contas por e-mail) e refletir no schema.

## Regras Operacionais de Revisão

- Antes de alterar fluxos críticos (`admin`, `selfservice`, `install`, `criar_instancia`), executar ao menos:
  - `php -l` no(s) arquivo(s) alterado(s)
  - `find . -name '*.php' -print0 | xargs -0 -I{} php -l '{}'` em mudanças amplas
- Em buscas de código no repositório, ignorar diretórios temporários (`.claude/worktrees/`) para evitar falso positivo de código duplicado.
