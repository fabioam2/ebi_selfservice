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
