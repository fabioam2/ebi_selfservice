# ebi/template — Sistema de Cadastro de Crianças

Este diretório é o **template base** das instâncias EBI. Cada instância criada pelo selfservice é uma cópia desta pasta, com `config.ini` e arquivos de dados próprios.

## Estrutura de arquivos

```
ebi/template/
├── CLAUDE.md                  # Este arquivo
├── config.ini                 # Configuração da instância (NÃO expor publicamente)
├── index.php                  # Entry point principal
├── calibrar.php               # Utilitário de calibração da impressora
├── zerar_dados.php            # Utilitário para zerar o arquivo de dados
├── index.original.php         # Cópia original do index para referência
│
├── inc/
│   ├── bootstrap.php          # Carrega config.ini, define constantes e sessão
│   ├── auth.php               # Autenticação (login/logout)
│   ├── actions.php            # Handlers de todas as ações POST
│   └── funcoes.php            # Funções auxiliares (CRUD, backups, ZPL, etc.)
│
├── views/
│   ├── login.php              # Tela de login
│   └── main.php              # Interface principal (cadastro + listagem)
│
├── saida/                     # Módulo de saída/entrada de crianças
│   ├── index.php              # Portaria — scanner de QR Code
│   ├── painel.php             # Painel de monitoramento de saídas
│   ├── processar_qr.php       # Endpoint AJAX para processar QR Codes
│   ├── index.html             # Redirect para index.php
│   └── inc/
│       └── bootstrap.php      # Bootstrap específico do módulo saída
│
└── assets/
    └── signing/               # Assinatura digital para QR Codes
        ├── sign-message.php
        ├── private-key.pem
        ├── digital-certificate.txt
        └── .htaccess
```

## Fluxo de uma requisição

```
index.php
  └── inc/bootstrap.php   → parse config.ini, define constantes, inicia sessão
  └── inc/auth.php        → verifica autenticação; se não logado → views/login.php
  └── inc/funcoes.php     → funções de domínio disponíveis
  └── GET ?acao=preview_backup → resposta JSON/text e exit
  └── POST               → inc/actions.php (valida CSRF, executa ação, redirect)
  └── GET (padrão)       → views/main.php
```

## Configuração (`config.ini`)

O arquivo é lido por `inc/bootstrap.php` via `parse_ini_file(..., INI_SCANNER_TYPED)`. Seções obrigatórias:

| Seção | Finalidade |
|---|---|
| `[INFO_SISTEMA]` | Metadados da instância |
| `[INFO_USUARIO]` | Dados do proprietário (preenchido pelo selfservice) |
| `[GERAL]` | Caminhos dos arquivos de dados, backup, timezone |
| `[SEGURANCA]` | Senhas, tempo de sessão, CSRF, rate limiting |
| `[IMPRESSORA_ZPL]` | Configurações da impressora de pulseiras ZPL/QZ Tray |
| `[INTERFACE]` | Título, logo, cores do tema |
| `[VALIDACAO]` | Regras de validação dos campos |
| `[PROCESSAMENTO_NOMES]` | Truncamento e formatação de nomes na pulseira |
| `[LISTAGEM]` | Paginação, ordenação, filtros |
| `[EMAIL]` | SMTP (desabilitado por padrão) |
| `[LOGS]` | Sistema de logs (desabilitado por padrão) |
| `[RECURSOS]` | Feature flags para habilitar/desabilitar funcionalidades |
| `[AVANCADO]` | Debug mode, cache, encoding |
| `[LIMPEZA_AUTOMATICA]` | Remoção automática de instâncias inativas |
| `[SAIDA]` | Configurações do módulo de saída |

Caminhos em `[GERAL]` são **relativos ao diretório da instância** (não ao template):
```ini
ARQUIVO_DADOS = "/../../config/cadastro_criancas.txt"
```

## Formato dos dados (`cadastro_criancas.txt`)

Arquivo texto delimitado por `|` (configurável via `DELIMITADOR`). Cada linha é um registro:

```
ID|NomeCriança|NomeResponsável|Telefone|Idade|Comum|StatusImpresso|Portaria
```

- **StatusImpresso**: `0` = não impresso, `1` = impresso
- Backups automáticos: `cadastro_criancas.txt.bkp.YYYYMMDDHHIISS`

## Constantes definidas pelo bootstrap

| Constante | Origem |
|---|---|
| `ARQUIVO_DADOS` | `[GERAL] ARQUIVO_DADOS` |
| `DELIMITADOR` | `[GERAL] DELIMITADOR` |
| `SENHA_ADMIN_REAL` / `SENHA_LOGIN` | `[SEGURANCA] SENHA_ADMIN_REAL` |
| `TAMPULSEIRA`, `DOTS`, `FECHO`, `FECHOINI` | `[IMPRESSORA_ZPL]` |
| `PULSEIRAUTIL` | `(TAMPULSEIRA - FECHO) * DOTS` |
| `URL_IMPRESSORA` | `[IMPRESSORA_ZPL] URL_IMPRESSORA` |
| `TEMPO_SESSAO` | `[SEGURANCA] TEMPO_SESSAO` |
| `VERSAO_SISTEMA` | Hash do último commit git ou mtime do index.php |
| `PALAVRA_CONTADOR_COMUM` | `[IMPRESSORA_ZPL]` |
| `LISTA_PALAVRAS_CONTADOR_COMUM` | `[IMPRESSORA_ZPL]` |

## Impressão de pulseiras

A impressão usa **QZ Tray** via JavaScript na `views/main.php`. O código ZPL é gerado em `inc/funcoes.php` e enviado para `URL_IMPRESSORA` (padrão: `http://127.0.0.1:9100/write`).

Parâmetros ZPL calculados:
- `PULSEIRAUTIL = (TAMPULSEIRA - FECHO) * DOTS`
- `TAMPULSEIRA` em mm, `DOTS` em dots/mm

## Módulo de saída (`saida/`)

Sistema separado para controle de entrada/saída de crianças via QR Code:
- `saida/index.php` — interface da portaria (scanner)
- `saida/painel.php` — painel em tempo real para monitores
- `saida/processar_qr.php` — endpoint AJAX que valida QR e registra saída em `saidas.log`

O `saida/inc/bootstrap.php` navega dois níveis acima para encontrar o `config.ini` da instância.

## Segurança

- CSRF obrigatório em todos os POST (`csrf_validate()`)
- Senhas comparadas com `===` (sem hash — senhas simples por design do produto)
- `sanitize_for_html()` usa `htmlspecialchars` em toda saída HTML
- `sanitize_for_file()` remove o delimitador `|` antes de salvar
- `assets/signing/.htaccess` bloqueia acesso direto à chave privada
- `config.ini` deve ficar **fora** do `public_html` (ver comentário no próprio arquivo)

## Convenções de código

- PHP 7.4+, sem framework, sem autoloader
- Arquivos `inc/` são `require`'d diretamente pelo `index.php`
- Funções auxiliares em `inc/funcoes.php`; não há classes
- Sessão iniciada apenas em `inc/bootstrap.php`
- Mensagens de sucesso/erro passadas via `$_SESSION` e limpas após leitura
- Redirect-after-POST em todas as ações (`header('Location: ...')` + `exit`)
