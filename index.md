# EBI SelfService — Índice de Páginas

> 🔐 **Senha padrão de fábrica:** `Senha123!`
> Armazenada apenas como hash bcrypt (cost 12). Troque em produção.

Este documento descreve **todas as páginas e scripts** do projeto, agrupados por módulo.
Uma versão HTML equivalente (com links clicáveis) está em [`index.html`](index.html).

---

## Sumário

- [Self-Service (gerenciamento de instâncias)](#1-self-service-gerenciamento-de-instâncias)
- [EBI — template base](#2-ebi--template-base)
- [Módulo Saída](#3-módulo-saída)
- [Utilitários](#4-utilitários)
- [Ambiente de teste local](#5-ambiente-de-teste-local)
- [Documentação auxiliar](#6-documentação-auxiliar)

---

## 1. Self-Service (gerenciamento de instâncias)

Pasta: [`selfservice/`](selfservice/)

| Página | Caminho | Para quem | Descrição |
|---|---|---|---|
| Página de Instalação | [selfservice/instal.html](selfservice/instal.html) | visitante | Apresentação e chamada para cadastro. |
| Cadastro | [selfservice/selfservice.php](selfservice/selfservice.php) | usuário final | Formulário público que cria uma instância isolada. Detecta se já existe instância para o e-mail e permite apagar (exige `password_verify` da senha da instância) ou criar uma nova. Valida CSRF e usa `password_hash` ao salvar o usuário em `data/selfservice_users.txt`. |
| Painel admin | [selfservice/admin.php](selfservice/admin.php) | administrador | Login com `Senha123!` (hash em `ADMIN_PASSWORD_HASH` no `.env`). Permite: gerenciar usuários (CRUD), listar/remover instâncias, editar configurações e acessar a documentação. Usa `session_regenerate_id(true)` e CSRF em todas as ações. |
| Criação de instância | [selfservice/criar_instancia.php](selfservice/criar_instancia.php) | *biblioteca* | Funções `criarInstanciaUsuario()`, `verificarInstanciaExiste()`, `obterInfoInstancia()`, `listarTodasInstancias()`, `removerInstancia()`. Gera `config.ini` da instância com **apenas hash bcrypt** da senha. `chmod 0600` nos INIs e `mkdir 0700` na pasta `config/`. |
| Install | [selfservice/install.php](selfservice/install.php) | setup inicial | Verifica requisitos (PHP, extensões, diretórios), cria `.htaccess` de proteção, gera senha admin aleatória já **hasheada** e grava em `ADMIN_PASSWORD_HASH` no `.env`. **Rode uma vez e apague o arquivo.** |
| Cleanup CLI | [selfservice/cleanup_instances.php](selfservice/cleanup_instances.php) | tarefa agendada | Executável somente via CLI (`php selfservice/cleanup_instances.php`). Remove instâncias sem acesso há mais de 24 h usando `.lastaccess`. |
| Config Manager | [selfservice/config_manager.php](selfservice/config_manager.php) | — | **Desativado**. Retorna `410 Gone`. O arquivo antigo escrevia INI concatenando strings (injeção); manter para compatibilidade de URLs antigas. Use o painel admin. |

### Pastas internas do self-service

- [selfservice/inc/](selfservice/inc/) — helpers: `paths.php`, `rate_limit.php`, `user_manager.php`, `email_manager.php`, `admin_dashboard.php`, `admin_docs.php`, `admin_instances.php`, `admin_settings.php`, `admin_users.php`, `test_email.php`.
- [selfservice/template/](selfservice/template/) — cópia do EBI que é clonada a cada nova instância.
- [selfservice/data/](selfservice/data/) — estado persistente (bloqueado via `.htaccess`): `admin_users.json`, `selfservice_users.txt`, `instancias_criadas.log`, `erros.log`.
- [selfservice/instances/](selfservice/instances/) — instâncias criadas (uma subpasta por usuário, `chmod 0700`).
- [selfservice/documentacao/](selfservice/documentacao/) — documentação funcional.

---

## 2. EBI — template base

Pasta: [`ebi/template/`](ebi/template/)

> ⚠️ Este diretório é um **template**: em produção cada usuário recebe sua cópia em `selfservice/instances/<id>/public_html/ebi/`. Os arquivos aqui servem de referência e para desenvolvimento.

| Página | Caminho | Para quem | Descrição |
|---|---|---|---|
| Cadastro principal | [ebi/template/index.php](ebi/template/index.php) | usuário | Login + tela de cadastro de crianças, listagem com filtros, impressão ZPL, preview/recuperação de backup e zeragem. Usa `verificar_senha_admin()` (`password_verify`) e valida `preview_backup` com regex + `realpath`. |
| Login (view) | [ebi/template/views/login.php](ebi/template/views/login.php) | — | Tela de autenticação renderizada quando a sessão não está logada. |
| Painel principal (view) | [ebi/template/views/main.php](ebi/template/views/main.php) | — | Interface Bootstrap com formulário de cadastro, tabela, modais de backup e impressão. |
| Calibrar impressora | [ebi/template/calibrar.php](ebi/template/calibrar.php) | operador técnico | Assistente interativo para calibrar impressora ZPL/TSPL2. |
| Zerar dados (CLI) | [ebi/template/zerar_dados.php](ebi/template/zerar_dados.php) | tarefa administrativa | Somente CLI. Faz backup com timestamp e apaga o arquivo principal se for mais antigo que 24 h. |
| Índice antigo | [ebi/template/index.original.php](ebi/template/index.original.php) | histórico | Versão pré-refatoração, mantida como referência. |
| Config | [ebi/template/config.ini](ebi/template/config.ini) | — | **Bloqueado via `.htaccess`.** Contém `SENHA_ADMIN_HASH`, `SENHA_PAINEL_HASH`, paths, flags e parâmetros de impressão. |

### `inc/` (lógica)

- [ebi/template/inc/bootstrap.php](ebi/template/inc/bootstrap.php) — carrega `config.ini`, abre sessão endurecida (HttpOnly, SameSite=Lax, Secure em HTTPS), envia headers de segurança, fornece `csrf_*`, `sanitize_for_*`, `verificar_senha_admin()`, `verificar_senha_painel()` e `migrar_senha_legada_para_hash()`.
- [ebi/template/inc/auth.php](ebi/template/inc/auth.php) — fluxo de login/logout com `session_regenerate_id(true)`, rate limit por sessão e CSRF.
- [ebi/template/inc/actions.php](ebi/template/inc/actions.php) — handlers POST (cadastrar, excluir, imprimir, atualizar status, recuperar backup).
- [ebi/template/inc/funcoes.php](ebi/template/inc/funcoes.php) — utilitários de arquivo (ler/salvar cadastros, backup rotativo, ZPL).

---

## 3. Módulo Saída

Pasta: [`ebi/template/saida/`](ebi/template/saida/)

| Página | Caminho | Para quem | Descrição |
|---|---|---|---|
| Registrar saída | [ebi/template/saida/index.php](ebi/template/saida/index.php) | portaria | Login com `Senha123!` + leitor de QR Code. Ao ler um código válido, mostra a(s) criança(s) do responsável para confirmar a saída. |
| Painel de saídas | [ebi/template/saida/painel.php](ebi/template/saida/painel.php) | portaria/admin | Exibe as últimas saídas registradas com auto-refresh. Permite zerar o log mediante confirmação de senha (`verificar_senha_painel`). |
| Processar QR (AJAX) | [ebi/template/saida/processar_qr.php](ebi/template/saida/processar_qr.php) | *endpoint* | Recebe `type=consultar/registrar` e responde JSON. |
| Bootstrap | [ebi/template/saida/inc/bootstrap.php](ebi/template/saida/inc/bootstrap.php) | — | Reutiliza o `config.ini` do EBI. Define constantes, abre sessão endurecida, envia headers de segurança e fornece `verificar_senha_painel()`. |

---

## 4. Utilitários

| Página | Caminho | Para quem | Descrição |
|---|---|---|---|
| Gerador de QR | [qrcode/default.php](qrcode/default.php) | *endpoint* | Gera imagem PNG de um QR Code a partir de parâmetros na URL. |

---

## 5. Ambiente de teste local

Pasta: [`test-env/`](test-env/) — **não vai para produção**, adicionada ao `.gitignore` para a instância (`test-env/instance/`).

| Item | Caminho | Descrição |
|---|---|---|
| Página de testes | [test-env/index.html](test-env/index.html) | Atalhos e checklist de segurança. |
| Launcher | [test-env/start.sh](test-env/start.sh) | Sobe o servidor embutido do PHP (`php -S 127.0.0.1:8080`) com router. Chama `seed.php` automaticamente se necessário. |
| Router | [test-env/router.php](test-env/router.php) | Emula o `.htaccess`: bloqueia `.ini`, `.txt`, `.log`, `.env`, `.md`, arquivos ocultos, `.bkp.N` e pastas internas sensíveis. |
| Seed | [test-env/seed.php](test-env/seed.php) | Cria (ou recria) `test-env/instance/` com `config.ini` (hash bcrypt de `Senha123!`), 3 cadastros fictícios e 1 saída registrada. |

### Como rodar

```bash
./test-env/start.sh
# abre http://127.0.0.1:8080/
```

Para recriar a instância do zero:

```bash
php test-env/seed.php
```

---

## 6. Documentação auxiliar

| Documento | Descrição |
|---|---|
| [ALTERACOES_SAIDA.md](ALTERACOES_SAIDA.md) | Histórico da refatoração do módulo de saída. |
| [ebi/template/CLAUDE.md](ebi/template/CLAUDE.md) | Guia técnico do EBI (mapeamento de constantes etc.). |
| [selfservice/documentacao/README.md](selfservice/documentacao/README.md) | Visão geral do self-service. |
| [selfservice/documentacao/INSTALACAO.md](selfservice/documentacao/INSTALACAO.md) | Passo-a-passo de instalação. |
| [selfservice/documentacao/EXEMPLOS_DE_USO.md](selfservice/documentacao/EXEMPLOS_DE_USO.md) | Casos de uso. |
| [selfservice/documentacao/ANALISE_SEGURANCA.md](selfservice/documentacao/ANALISE_SEGURANCA.md) | Análise de segurança. |
| [selfservice/documentacao/CLEANUP_README.md](selfservice/documentacao/CLEANUP_README.md) | Como configurar o cleanup agendado. |
| [selfservice/documentacao/MUDANCAS_E_MELHORIAS.md](selfservice/documentacao/MUDANCAS_E_MELHORIAS.md) | Changelog funcional. |
| [selfservice/documentacao/INICIO_RAPIDO.txt](selfservice/documentacao/INICIO_RAPIDO.txt) | Guia rápido em texto. |

---

## Senhas e credenciais padrão

| Item | Valor | Observação |
|---|---|---|
| Senha de fábrica (todos os módulos) | `Senha123!` | Gravada apenas como hash bcrypt (cost 12). |
| Campo no `config.ini` | `SENHA_ADMIN_HASH` / `SENHA_PAINEL_HASH` | Texto plano (`SENHA_ADMIN_REAL`, `SENHA_PAINEL`) fica vazio. |
| Admin do selfservice | `$_ENV['ADMIN_PASSWORD_HASH']` | Criado pelo `install.php`; padrão de fábrica é o hash de `Senha123!`. |

### Trocar a senha

```bash
php -r "echo password_hash('MinhaSenhaNova', PASSWORD_BCRYPT, ['cost'=>12]);"
```

Copie o resultado para `SENHA_ADMIN_HASH` / `SENHA_PAINEL_HASH` no `config.ini`
ou para `ADMIN_PASSWORD_HASH` no `.env`, conforme o módulo.
