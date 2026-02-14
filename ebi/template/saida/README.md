# Módulo Saida - Registro de Saída de Crianças

## 📋 Descrição

Módulo de entrada/saída de crianças que registra quando responsáveis retiram seus filhos da instituição. Integra-se com o sistema EBI compartilhando:
- Arquivo de configuração (`config.ini`)
- Banco de dados de crianças (`cadastro_criancas.txt`)
- Credenciais de autenticação

---

## 🔗 Integração com EBI

### Arquivo Compartilhado: `config.ini`

```
template/config.ini ← COMPARTILHADO
    ↓
template/inc/bootstrap.php (EBI)
    ↓
template/saida/inc/bootstrap.php (SAIDA) ← carrega mesmo config.ini
```

### Configurações Herdadas

| Configuração | Seção | Valor Padrão | Uso |
|---|---|---|---|
| `ARQUIVO_DADOS` | `[GERAL]` | `/../../config/cadastro_criancas.txt` | Ler dados de crianças |
| `DELIMITADOR` | `[GERAL]` | `\|` (pipe) | Parsear arquivo de dados |
| `SENHA_PAINEL` | `[SEGURANCA]` | `MudeEstaSenha@123` | Login |
| `TEMPO_SESSAO` | `[SEGURANCA]` | `1800` (30 min) | Timeout sessão |
| `MAX_TENTATIVAS_LOGIN` | `[SEGURANCA]` | `5` | Rate limiting |
| `TEMPO_BLOQUEIO` | `[SEGURANCA]` | `300` (5 min) | Duração bloqueio |

### Configurações Específicas

| Configuração | Seção | Valor Padrão | Uso |
|---|---|---|---|
| `ARQUIVO_SAIDAS` | `[SAIDA]` | `/../../config/saidas.log` | Log de saídas |
| `HABILITAR_SAIDA` | `[SAIDA]` | `true` | Ativa/desativa módulo |

---

## 📂 Estrutura de Diretórios

```
saida/
├── inc/
│   └── bootstrap.php         # Carrega config + define constantes
├── index.php                 # Login + interface de consulta
├── painel.php                # Dashboard de saídas registradas
├── processar_qr.php          # API JSON (consulta + registro)
├── index.html                # Redireciona para index.php
└── README.md                 # Este arquivo
```

---

## 🔐 Fluxo de Autenticação

```
┌─────────────────────────────────────────────────────────┐
│ 1. Usuario acessa index.php                             │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ 2. Bootstrap carrega config.ini                         │
│    - Define ARQUIVO_DADOS                              │
│    - Define SENHA_PAINEL                               │
│    - Inicia sessão com timeout                         │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ 3. Valida autenticação                                  │
│    - Se não autenticado → exibe form login              │
│    - Se autenticado → exibe interface                   │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ 4. Login (POST com CSRF)                                │
│    - Verifica senha contra SENHA_PAINEL                 │
│    - Rate limiting: max 5 tentativas                    │
│    - Cria sessão: $_SESSION['logado_saida'] = true      │
│    - Regenera CSRF token                                │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ 5. Interface de Consulta                                │
│    - Input: Código do Responsável                       │
│    - Fetch: processar_qr.php (POST JSON)                │
│    - Retorna: Responsável + Crianças                    │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ 6. Registro de Saída                                    │
│    - Seleciona Portaria (M/F)                           │
│    - Fetch: processar_qr.php (POST JSON)                │
│    - Salva em ARQUIVO_SAIDAS                            │
└─────────────────────────────────────────────────────────┘
```

---

## 📝 Formato de Dados

### Entrada (leitura de `cadastro_criancas.txt`)

```
ID|NomeCriança|NomeResponsável|Telefone|Idade|Comum|StatusImpresso|Portaria|CodResp
1|Ana Silva|Maria Silva|11999999999|3|Bonfim|N|A|1
2|Bruno Santos|João Santos|11999999998|4|Parque|S|B|1
```

Campos utilizados no Saida:
- `$dados[1]` = NomeCriança
- `$dados[2]` = NomeResponsável
- `$dados[4]` = Idade
- `$dados[8]` = CodResp (código do responsável)

### Saída (escreve em `saidas.log`)

```
timestamp;CodResp;NomeResponsável;NomeCriança;Portaria
1707840000;1;Maria Silva;Ana Silva;M
1707840300;1;Maria Silva;Bruno Santos;M
```

Delimitador: `;` (ponto-e-vírgula)

---

## 🔧 Arquivo: bootstrap.php

### Responsabilidades

1. **Carrega Configuração**
   ```php
   $config = parse_ini_file($config_file, true, INI_SCANNER_TYPED);
   ```

2. **Define Constantes Globais**
   ```php
   define('ARQUIVO_DADOS', ...);
   define('ARQUIVO_SAIDAS', ...);
   define('SENHA_PAINEL', ...);
   ```

3. **Gerencia Sessão**
   ```php
   session_start();
   // Verifica timeout a cada acesso
   ```

4. **Fornece Funções Reutilizáveis**
   ```php
   sanitize_for_html()
   sanitize_for_file()
   csrf_token()
   csrf_validate()
   lerTodosCadastros()
   ```

### Constantes Definidas

```php
ARQUIVO_DADOS           // Arquivo de cadastro de crianças
DELIMITADOR             // Caractere separador (|)
ARQUIVO_SAIDAS          // Log de saídas
SENHA_PAINEL            // Senha de acesso
TEMPO_SESSAO            // Segundos até timeout
MAX_TENTATIVAS_LOGIN    // Máximo de tentativas
TEMPO_BLOQUEIO          // Segundos até desbloqueio
```

---

## 🔒 Segurança

### CSRF Protection

Todos os formulários incluem token CSRF:
```html
<?php echo csrf_field(); ?>
<!-- Gera: <input type="hidden" name="csrf_token" value="..."> -->
```

Token é:
- ✅ Gerado: 64 caracteres (32 bytes em hex)
- ✅ Regenerado após login
- ✅ Validado em toda submissão POST
- ✅ Vinculado à sessão do usuário

### Sanitização

```php
// Para saída em HTML (impede XSS)
$safe = sanitize_for_html($user_input);
// Remove espaços, escapa caracteres especiais

// Para arquivo (impede injeção de delimitador)
$safe = sanitize_for_file($user_input);
// Remove pipes (|) que causariam corrupção
```

### Rate Limiting

```php
if ($_SESSION['tentativas_login_saida'] >= 5) {
    if (time() - $_SESSION['ultimo_login_tentativa'] < 300) {
        // Bloqueado por mais X segundos
    }
}
```

### Session Timeout

```php
if (time() - $_SESSION['ultimo_acesso_saida'] > 1800) {
    // Sessão expirada
    $_SESSION['logado_saida'] = false;
}
```

---

## 📊 API: processar_qr.php

### Requisição 1: Consultar Crianças

```bash
curl -X POST http://localhost/saida/processar_qr.php \
  -H "Content-Type: application/json" \
  -d '{"type": "consultar", "codigo": "1"}'
```

**Resposta (sucesso)**:
```json
{
  "status": "success_lookup",
  "responsavel": "Maria Silva",
  "criancas": [
    "Ana Silva [3 anos]",
    "Bruno Santos [4 anos]"
  ],
  "codResp": "1"
}
```

**Resposta (erro)**:
```json
{
  "status": "error",
  "message": "Código de responsável (999) não encontrado."
}
```

### Requisição 2: Registrar Saída

```bash
curl -X POST http://localhost/saida/processar_qr.php \
  -H "Content-Type: application/json" \
  -d '{
    "type": "registrar",
    "registroData": "1;Maria Silva;Ana Silva [3 anos];Bruno Santos [4 anos]",
    "portaria": "M"
  }'
```

**Resposta (sucesso)**:
```json
{
  "status": "success_registered",
  "message": "2 criança(s) registrada(s) para Maria Silva."
}
```

### Validações

- ✅ Autenticação obrigatória
- ✅ Código numérico e positivo
- ✅ Portaria = letra única (A-Z)
- ✅ Tipo de requisição validado
- ✅ JSON bem-formado

---

## 🎯 Páginas

### index.php - Login + Consulta

```
┌─────────────────────────────────┐
│  Acesso - Saída de Crianças     │
├─────────────────────────────────┤
│ Código do Responsável: [______] │
│ [Consultar]                     │
│                                 │
│ (Exibe dados após consulta)     │
│ Responsável: Maria Silva        │
│ Criança(s): Ana [3], Bruno [4]  │
│ Portaria: [Masculino/Feminino]  │
│ [Registrar Saída] [Cancelar]    │
│                                 │
│ [Ver Painel de Saídas]          │
├─────────────────────────────────┤
│ [Sair]                          │
└─────────────────────────────────┘
```

### painel.php - Dashboard

```
┌──────────────────────────────────────┐
│  Painel de Saídas         [Sair]     │
├──────────────────────────────────────┤
│                                      │
│ 14:32 - Ana Silva; Bruno Santos -   │
│         Maria Silva (M)              │
│                                      │
│ 14:15 - João Santos -                │
│         Pedro Silva (F)              │
│                                      │
├──────────────────────────────────────┤
│ [Ver 10 últimos / Ver Todos]         │
│ Atualizar a cada: [5s ▼]             │
│ [Zerar Arquivo]                      │
│ [Registrar Nova Saída]               │
└──────────────────────────────────────┘
```

---

## 🚀 Instalação/Configuração

### 1. Verificar Bootstrap
```bash
php -l template/saida/inc/bootstrap.php
# Parse error... (deve retornar sem erros)
```

### 2. Testar Carregamento de Config
```bash
cd template/saida
php -r "require 'inc/bootstrap.php'; echo ARQUIVO_DADOS;"
# /path/to/cadastro_criancas.txt
```

### 3. Criar Arquivo de Saídas
```bash
touch /path/to/saidas.log
chmod 644 /path/to/saidas.log
```

### 4. Testar Login
```
Acesse: http://localhost/saida/
Senha: (configurada em config.ini [SEGURANCA] SENHA_PAINEL)
```

---

## 🐛 Troubleshooting

| Problema | Causa | Solução |
|---|---|---|
| `Arquivo de configuração não encontrado` | Config.ini em caminho errado | Verificar caminho relativo em bootstrap.php |
| `Erro ao ler arquivo de cadastro` | Permissões insuficientes | `chmod 644 cadastro_criancas.txt` |
| `Erro ao salvar saídas` | Arquivo_saidas sem escrita | `chmod 644 saidas.log` |
| `Sessão expirada imediatamente` | Timeout muito baixo | Aumentar TEMPO_SESSAO em config.ini |
| `Token CSRF inválido` | Sessão reiniciada | Limpar cookies/cache |

---

## 📚 Referências

- **Config Compartilhado**: `template/config.ini`
- **Bootstrap EBI**: `template/inc/bootstrap.php`
- **Documentação Alterações**: `ALTERACOES_SAIDA.md`

---

Versão: 1.0 | Data: 2026-02-13 | Status: ✅ Produção
