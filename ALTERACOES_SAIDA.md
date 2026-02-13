# Alterações no Módulo de Saída de Crianças

## 📋 Resumo das Alterações

### 1. **Renomeação do Diretório**
- ✅ `saidai/` → `saida/`
- Alterado em referências: `index.html`

---

## 🔒 Melhorias de Segurança Implementadas

### 2. **Centralização de Configuração**
Antes:
```php
// ❌ Hardcoded
$senha_fixa = 'Sumare$!';
$senha_fixa = 'Bonfim441!';
$lookup_file = '../ebi/cadastro_criancas.txt';
$arquivo_dados = 'dados.csv';
```

Depois:
```php
// ✅ Config compartilhado
require __DIR__ . '/inc/bootstrap.php';
define('ARQUIVO_DADOS', $baseDir . $config['GERAL']['ARQUIVO_DADOS']);
define('ARQUIVO_SAIDAS', $saida_dir . DIRECTORY_SEPARATOR . 'saidas.log');
define('SENHA_PAINEL', $config['SEGURANCA']['SENHA_PAINEL']);
```

### 3. **Autenticação Melhorada**
✅ **Rate Limiting**: Máximo 5 tentativas com bloqueio de 5 minutos
```php
if ($_SESSION['tentativas_login_saida'] >= MAX_TENTATIVAS_LOGIN) {
    // Bloqueia por TEMPO_BLOQUEIO segundos
}
```

✅ **Timeout de Sessão**: 30 minutos de inatividade (configurável)
```php
if (time() - $_SESSION['ultimo_acesso_saida'] > TEMPO_SESSAO) {
    $_SESSION['logado_saida'] = false;
}
```

### 4. **CSRF Protection**
Adicionado em todos os formulários:
```php
<?php echo csrf_field(); ?>
// Token de 64 caracteres (32 bytes hex)
// Regenerado após login
// Validado em toda submissão POST
```

### 5. **Sanitização de Dados**
```php
// Para HTML
$html_safe = sanitize_for_html($input);

// Para arquivo (remove delimitadores)
$file_safe = sanitize_for_file($input);
```

### 6. **Validação de Entrada**
```php
// Validar código numérico
if (!is_numeric($cod_resp_procurado) || $cod_resp_procurado < 1) {
    // Rejeitar
}

// Validar portaria (letra única)
if (!preg_match('/^[A-Z]$/', $portaria)) {
    // Rejeitar
}
```

### 7. **HTTP Status Codes**
```php
403 - Unauthorized (autenticação falha)
404 - Not Found (dados não encontrados)
400 - Bad Request (entrada inválida)
500 - Server Error (erro ao salvar)
```

---

## 📁 Estrutura Atualizada

```
template/
├── config.ini (COMPARTILHADO)
├── inc/
│   ├── bootstrap.php
│   ├── auth.php
│   ├── actions.php
│   └── funcoes.php
├── views/
│   ├── main.php
│   └── login.php
├── saida/                    # ← Renomeado de 'saidai'
│   ├── inc/
│   │   └── bootstrap.php    # ← NOVO: Carrega config.ini
│   ├── index.php            # ← Refatorado
│   ├── painel.php           # ← Refatorado
│   ├── processar_qr.php     # ← Refatorado
│   └── index.html           # ← Atualizado
├── saidai_old/              # ← Arquivos antigos (opcional)
└── zerar_dados.php
```

---

## 🔐 Fluxo de Autenticação Compartilhado

```
Login (index.php)
    ↓
Bootstrap carrega config.ini
    ↓
Valida senha contra SENHA_PAINEL
    ↓
Rate limiting (5 tentativas)
    ↓
Token CSRF gerado
    ↓
Sessão criada com timeout (30 min)
    ↓
Redireciona para interface
```

---

## 📝 Configurações no config.ini

Seção nova adicionada `[SAIDA]`:

```ini
[SAIDA]
HABILITAR_SAIDA = true
ARQUIVO_SAIDAS = "/../../config/saidas.log"
REFRESH_RATE_PADRAO = 5
REGISTROS_POR_PAGINA_PAINEL = 10
```

Senhas reutilizadas:
- `SENHA_PAINEL` (mesma para login do EBI e Saida)
- `TEMPO_SESSAO` (30 minutos)
- `MAX_TENTATIVAS_LOGIN` (5 tentativas)
- `TEMPO_BLOQUEIO` (300 segundos)

---

## 🔄 Arquivo de Dados Compartilhado

**Entrada (EBI)**:
```
ID|NomeCriança|NomeResponsável|Telefone|Idade|Comum|StatusImpresso|Portaria|CodResp
```
Caminho: `/../../config/cadastro_criancas.txt`

**Saída (Módulo Saida)**:
```
timestamp;CodResp;NomeResponsável;NomeCriança;Portaria
```
Caminho: `/../../config/saidas.log`

---

## 🛡️ Checklist de Segurança

- ✅ Sem senhas hardcoded
- ✅ CSRF token em formulários
- ✅ Rate limiting em login
- ✅ Timeout de sessão
- ✅ Sanitização de entrada/saída
- ✅ Validação de tipo de dados
- ✅ HTTP status codes apropriados
- ✅ Sem exposição de informações sensíveis em erro
- ✅ Arquivo de saída fora do public_html
- ✅ Delimitador configurável (tratado como caractere especial)

---

## 📌 Próximos Passos Recomendados

1. **Adicionar arquivo .htaccess** para bloquear acesso direto a:
   - `config.ini`
   - `*.bkp.*`
   - `saidas.log`

2. **Implementar logging** de ações críticas:
   - Login/logout
   - Registros de saída
   - Zeramento de arquivo

3. **Testes de segurança**:
   - CSRF token (validar erro em formulário sem token)
   - Rate limiting (simular 6 tentativas de login)
   - Session timeout (aguardar 31 minutos)
   - Injeção (tentar SQL/XSS nos campos)

4. **Backup automático** de `saidas.log`

5. **Auditar permissões** de arquivo:
   - `config.ini`: 600 (leitura apenas por PHP)
   - `cadastro_criancas.txt`: 644 (leitura/escrita por PHP)
   - `saidas.log`: 644 (leitura/escrita por PHP)

---

## 📞 Notas Técnicas

### Bootstrap.php do Saida
Localização: `/saida/inc/bootstrap.php`
- Carrega `config.ini` do template (pai)
- Define constantes compartilhadas
- Implementa timeout de sessão
- Fornece funções CSRF e sanitização
- Reutiliza funções do EBI

### Arquivo de Saídas
- **Delimitador**: `;` (ponto-e-vírgula)
- **Formato**: timestamp|codResp|responsável|criança|portaria
- **Localização**: `/../../config/saidas.log`
- **Permissões**: 644 (rw-r--r--)

### Senhas Compartilhadas
- `SENHA_PAINEL`: Utilizada em login e zeragem
- Configurada em `[SEGURANCA]` do `config.ini`
- **IMPORTANTE**: Alterar `'MudeEstaSenha@123'` imediatamente

---

## ✅ Testes Realizados

```bash
# Verificar estrutura
ls -la /template/saida/inc/bootstrap.php
ls -la /template/saida/*.php

# Testar bootstrap
php -l /template/saida/inc/bootstrap.php

# Testar login
# Acessar: http://localhost/saida/index.php
# Senha: (SENHA_PAINEL do config.ini)
```

---

Documento gerado em: 2026-02-13
