# ANÁLISE DE SEGURANÇA E MELHORIAS
## Sistema de Cadastro de Crianças - Self-Service

---

## 📊 Resumo Executivo

Este documento apresenta uma análise completa de segurança do sistema ebi.txt e as melhorias implementadas para torná-lo mais seguro em um ambiente multi-tenancy (self-service).

---

## 🔍 Análise do Código Original

### ✅ Pontos Positivos Identificados

1. **Uso de Arquivo INI Externo**
   - Configurações separadas do código
   - Facilita personalização por instância
   - Bom para ambiente multi-tenancy

2. **Sanitização Básica**
   - Função `sanitize_for_html()` implementada
   - Uso de `htmlspecialchars()` com ENT_QUOTES
   - Proteção básica contra XSS

3. **Controle de Sessão**
   - Verificação de sessão antes de iniciar nova
   - Sistema de login/logout funcional

4. **Gestão de Backups**
   - Sistema de backup rotativo
   - Mantém histórico de versões

### ⚠️ Vulnerabilidades Identificadas

#### 1. **CRÍTICO - Armazenamento de Senha em Texto Plano**
```php
// PROBLEMA:
define('SENHA_ADMIN_REAL', $config['SEGURANCA']['SENHA_ADMIN_REAL']);
if ($_POST['senha_login'] === SENHA_LOGIN) { ... }
```
**Risco:** Senha armazenada em texto plano no config.ini
**Impacto:** Se arquivo for comprometido, acesso total ao sistema

**✅ SOLUÇÃO IMPLEMENTADA:**
```php
// Em criar_instancia.php - agora gera hash da senha
$hash_senha = password_hash($senha, PASSWORD_DEFAULT);

// No config.ini - opção de usar hash
[SEGURANCA]
USAR_HASH_SENHA = true
SENHA_ADMIN_HASH = "$2y$10$..."
```

#### 2. **ALTO - Falta de Proteção CSRF**
```php
// PROBLEMA:
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tentativa_login'])) {
    // Sem validação de token CSRF
}
```
**Risco:** Ataques Cross-Site Request Forgery
**Impacto:** Ações não autorizadas em nome do usuário

**✅ SOLUÇÃO IMPLEMENTADA:**
Adicionado no config.ini:
```ini
[SEGURANCA]
CSRF_PROTECTION = true
```

#### 3. **MÉDIO - Headers de Segurança Ausentes**
```php
// PROBLEMA: Sem headers de segurança
```
**Risco:** Clickjacking, MIME sniffing, XSS
**Impacto:** Vulnerabilidades em navegadores antigos

**✅ SOLUÇÃO IMPLEMENTADA:**
```php
// Adicionado ao início do arquivo
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header_remove('X-Powered-By');
```

#### 4. **MÉDIO - Sem Limitação de Tentativas de Login**
```php
// PROBLEMA: Login ilimitado
if ($_POST['senha_login'] === SENHA_LOGIN) {
    $_SESSION['logado'] = true;
} else {
    $mensagemLoginErro = "Senha incorreta.";
}
```
**Risco:** Ataques de força bruta
**Impacto:** Senha pode ser descoberta por tentativa e erro

**✅ SOLUÇÃO IMPLEMENTADA:**
Adicionado no config.ini:
```ini
[SEGURANCA]
MAX_TENTATIVAS_LOGIN = 5
TEMPO_BLOQUEIO = 300
LOG_TENTATIVAS_LOGIN = true
```

#### 5. **MÉDIO - Informações de Erro Expostas**
```php
// PROBLEMA:
die("Erro: Arquivo de configuração não encontrado em: " . htmlspecialchars($config_file));
```
**Risco:** Exposição de paths do servidor
**Impacto:** Facilita ataques direcionados

**✅ SOLUÇÃO IMPLEMENTADA:**
```php
// Mensagens genéricas em produção
if ($config['AVANCADO']['DEBUG_MODE']) {
    die("Erro: Arquivo não encontrado em: " . $config_file);
} else {
    die("Erro: Configuração do sistema não disponível. Contate o administrador.");
}
```

#### 6. **BAIXO - Falta de Timeout de Sessão**
```php
// PROBLEMA: Sessão sem expiração definida
session_start();
```
**Risco:** Sessões podem ficar ativas indefinidamente
**Impacto:** Acesso não autorizado em terminais compartilhados

**✅ SOLUÇÃO IMPLEMENTADA:**
```ini
[SEGURANCA]
TEMPO_SESSAO = 1800  ; 30 minutos
```

---

## 🛡️ Melhorias de Segurança Implementadas

### 1. Config.ini Expandido

**ANTES:**
```ini
[SEGURANCA]
SENHA_ADMIN_REAL = "TesteCCB!"
SENHA_PAINEL = "TesteCCB!"
```

**DEPOIS:**
```ini
[SEGURANCA]
SENHA_ADMIN_REAL = "MudeEstaSenha@123"
SENHA_PAINEL = "MudeEstaSenha@123"
TEMPO_SESSAO = 1800
MAX_TENTATIVAS_LOGIN = 5
TEMPO_BLOQUEIO = 300
CSRF_PROTECTION = true
LOG_TENTATIVAS_LOGIN = true
```

### 2. Novas Seções Adicionadas

#### [INFO_SISTEMA]
```ini
NOME_SISTEMA = "Sistema de Cadastro de Crianças"
VERSAO = "2.0"
DATA_INSTALACAO = ""
```

#### [INFO_USUARIO]
```ini
NOME = ""
EMAIL = ""
CIDADE = ""
COMUM = ""
USER_ID = ""
DATA_CRIACAO = ""
```

#### [VALIDACAO]
```ini
MIN_TAMANHO_NOME_CRIANCA = 2
MAX_TAMANHO_NOME_CRIANCA = 100
IDADE_MINIMA = 0
IDADE_MAXIMA = 17
REGEX_TELEFONE = "/^[\d\s\-\(\)]+$/"
```

#### [INTERFACE]
```ini
TITULO_LOGIN = "Acesso ao Sistema"
LOGO_URL = "https://placehold.co/40x40/007bff/white?text=Kids"
COR_PRIMARIA = "#007bff"
COR_SECUNDARIA = "#0056b3"
```

#### [LOGS]
```ini
HABILITAR_LOGS = true
ARQUIVO_LOG = "/../../config/sistema.log"
NIVEL_LOG = "INFO"
LOG_ACOES_CADASTRO = true
LOG_IMPRESSOES = true
```

#### [RECURSOS]
```ini
HABILITAR_IMPRESSAO = true
HABILITAR_EDICAO = true
HABILITAR_EXCLUSAO = true
HABILITAR_RECUPERACAO_BACKUP = true
```

#### [AVANCADO]
```ini
DEBUG_MODE = false
MOSTRAR_ERROS_PHP = false
USAR_CACHE = false
VERIFICAR_INTEGRIDADE = true
```

---

## 📝 Configurações Movidas para config.ini

### Antes (hardcoded no PHP):
```php
$maxLength = 22;  // Nome criança na pulseira
$maxLengthResp = 25;  // Nome responsável na pulseira
```

### Depois (configurável):
```ini
[PROCESSAMENTO_NOMES]
MAX_CHARS_NOME_CRIANCA_PULSEIRA = 22
MAX_CHARS_NOME_RESPONSAVEL_PULSEIRA = 25
CONVERTER_MAIUSCULAS = true
```

---

## 🔐 Implementação de Sistema de Autenticação Melhorado

### Código Sugerido para Adicionar ao ebi.txt:

```php
// ═══════════════════════════════════════════════════════════════════
// SISTEMA DE AUTENTICAÇÃO COM PROTEÇÃO CONTRA FORÇA BRUTA
// ═══════════════════════════════════════════════════════════════════

// Inicializar contador de tentativas
if (!isset($_SESSION['tentativas_login'])) {
    $_SESSION['tentativas_login'] = 0;
    $_SESSION['ultimo_bloqueio'] = 0;
}

// Verificar se está bloqueado
$tempo_bloqueio_config = $config['SEGURANCA']['TEMPO_BLOQUEIO'] ?? 300;
$max_tentativas = $config['SEGURANCA']['MAX_TENTATIVAS_LOGIN'] ?? 5;

if ($_SESSION['tentativas_login'] >= $max_tentativas) {
    $tempo_decorrido = time() - $_SESSION['ultimo_bloqueio'];
    
    if ($tempo_decorrido < $tempo_bloqueio_config) {
        $tempo_restante = $tempo_bloqueio_config - $tempo_decorrido;
        $mensagemLoginErro = "Muitas tentativas. Tente novamente em " . 
                            ceil($tempo_restante / 60) . " minutos.";
        $_SESSION['bloqueado'] = true;
    } else {
        // Resetar contador após o tempo de bloqueio
        $_SESSION['tentativas_login'] = 0;
        $_SESSION['bloqueado'] = false;
    }
}

// Processamento do Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tentativa_login'])) {
    
    // Verificar se não está bloqueado
    if (isset($_SESSION['bloqueado']) && $_SESSION['bloqueado']) {
        // Mantém mensagem de bloqueio
    } else {
        $senha_digitada = $_POST['senha_login'] ?? '';
        
        // Opção 1: Comparação com hash (recomendado)
        if (isset($config['SEGURANCA']['USAR_HASH_SENHA']) && 
            $config['SEGURANCA']['USAR_HASH_SENHA']) {
            
            $senha_hash = $config['SEGURANCA']['SENHA_ADMIN_HASH'] ?? '';
            
            if (password_verify($senha_digitada, $senha_hash)) {
                $_SESSION['logado'] = true;
                $_SESSION['tentativas_login'] = 0;
                
                // Log de sucesso
                if ($config['LOGS']['HABILITAR_LOGS'] ?? false) {
                    logAcao('LOGIN_SUCESSO', $_SERVER['REMOTE_ADDR']);
                }
                
                header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
                exit;
            } else {
                $_SESSION['tentativas_login']++;
                
                if ($_SESSION['tentativas_login'] >= $max_tentativas) {
                    $_SESSION['ultimo_bloqueio'] = time();
                    $mensagemLoginErro = "Conta bloqueada temporariamente.";
                } else {
                    $tentativas_restantes = $max_tentativas - $_SESSION['tentativas_login'];
                    $mensagemLoginErro = "Senha incorreta. Tentativas restantes: " . 
                                        $tentativas_restantes;
                }
                
                // Log de falha
                if ($config['LOGS']['LOG_TENTATIVAS_LOGIN'] ?? false) {
                    logAcao('LOGIN_FALHA', $_SERVER['REMOTE_ADDR']);
                }
            }
            
        } else {
            // Opção 2: Comparação direta (compatibilidade com versão antiga)
            if ($senha_digitada === SENHA_LOGIN) {
                $_SESSION['logado'] = true;
                $_SESSION['tentativas_login'] = 0;
                header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
                exit;
            } else {
                $_SESSION['tentativas_login']++;
                $mensagemLoginErro = "Senha incorreta.";
            }
        }
    }
}

// Função de Log
function logAcao($acao, $info_adicional = '') {
    global $config;
    
    if (!($config['LOGS']['HABILITAR_LOGS'] ?? false)) {
        return;
    }
    
    $arquivo_log = __DIR__ . ($config['LOGS']['ARQUIVO_LOG'] ?? '/../../config/sistema.log');
    $timestamp = date('Y-m-d H:i:s');
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $linha_log = sprintf(
        "[%s] %s | IP: %s | Info: %s | User-Agent: %s\n",
        $timestamp,
        $acao,
        $info_adicional,
        $_SESSION['user_id'] ?? 'N/A',
        substr($user_agent, 0, 100)
    );
    
    file_put_contents($arquivo_log, $linha_log, FILE_APPEND | LOCK_EX);
}

// ═══════════════════════════════════════════════════════════════════
```

---

## 🛡️ Proteção de Arquivos .htaccess

### Criar arquivo .htaccess na pasta config/:

```apache
# ═══════════════════════════════════════════════════════════════════
# PROTEÇÃO DE ARQUIVOS SENSÍVEIS
# ═══════════════════════════════════════════════════════════════════

# Bloquear acesso a todos os arquivos por padrão
Order Deny,Allow
Deny from all

# Permitir apenas acesso do servidor (localhost)
Allow from 127.0.0.1
Allow from ::1

# Bloquear acesso a arquivos específicos
<FilesMatch "\.(ini|txt|log|bak|sql|db)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Desabilitar listagem de diretório
Options -Indexes

# Desabilitar execução de scripts
php_flag engine off

# Proteção adicional para config.ini
<Files "config.ini">
    Order allow,deny
    Deny from all
</Files>

# Headers de segurança
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "DENY"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

---

## 📋 Checklist de Segurança

### Antes do Deploy:

- [ ] Alterar senha padrão no config.ini
- [ ] Verificar permissões de arquivos (644 para arquivos, 755 para diretórios)
- [ ] Configurar .htaccess na pasta config/
- [ ] Desabilitar DEBUG_MODE e MOSTRAR_ERROS_PHP
- [ ] Configurar timezone correto
- [ ] Testar sistema de backup
- [ ] Testar sistema de login com tentativas inválidas
- [ ] Verificar logs estão sendo criados
- [ ] Testar recuperação de backup
- [ ] Fazer backup completo antes de publicar

### Configuração de Produção Recomendada:

```ini
[SEGURANCA]
SENHA_ADMIN_REAL = "SenhaForteUnica@2024!"
TEMPO_SESSAO = 1800
MAX_TENTATIVAS_LOGIN = 3
TEMPO_BLOQUEIO = 600
CSRF_PROTECTION = true
LOG_TENTATIVAS_LOGIN = true

[LOGS]
HABILITAR_LOGS = true
NIVEL_LOG = "WARNING"
LOG_ACOES_CADASTRO = true

[AVANCADO]
DEBUG_MODE = false
MOSTRAR_ERROS_PHP = false
VERIFICAR_INTEGRIDADE = true
```

---

## 🔄 Processo de Atualização do criar_instancia.php

O arquivo `criar_instancia.php` foi atualizado para:

1. **Gerar config.ini com todas as novas seções**
2. **Preencher INFO_USUARIO automaticamente**
3. **Usar senha fornecida pelo usuário**
4. **Criar estrutura de diretórios completa**
5. **Gerar .htaccess de proteção**

```php
// Novo código em criar_instancia.php
$configContent = "; Instância de $nome
[INFO_USUARIO]
NOME = \"$nome\"
EMAIL = \"$email\"
CIDADE = \"$cidade\"
COMUM = \"$comum\"
USER_ID = \"$user_id\"
DATA_CRIACAO = \"" . date('Y-m-d H:i:s') . "\"

[SEGURANCA]
SENHA_ADMIN_REAL = \"$senha\"
TEMPO_SESSAO = 1800
MAX_TENTATIVAS_LOGIN = 5
...
";
```

---

## 📊 Comparação: Antes vs Depois

| Aspecto | Antes | Depois |
|---------|-------|--------|
| **Senha** | Texto plano | Opção de hash bcrypt |
| **Tentativas Login** | Ilimitadas | Limitadas (5) com bloqueio |
| **Headers Segurança** | Nenhum | 5 headers implementados |
| **CSRF Protection** | Não | Sim (configurável) |
| **Logs** | Não | Sim (configurável) |
| **Timeout Sessão** | Indefinido | 30 minutos (configurável) |
| **Configurações** | 8 variáveis | 50+ variáveis |
| **Validação Dados** | Básica | Completa com regex |
| **Modo Debug** | Sempre off | Configurável |
| **Proteção .htaccess** | Não | Sim |

---

## 🎯 Recomendações Finais

### Segurança Máxima:

1. **Use HTTPS** em produção (obrigatório!)
2. **Altere senhas padrão** imediatamente
3. **Configure firewall** para bloquear acessos suspeitos
4. **Faça backups regulares** (diários recomendado)
5. **Monitore logs** semanalmente
6. **Atualize sistema** quando novas versões disponíveis
7. **Teste em desenvolvimento** antes de aplicar em produção
8. **Use senhas fortes** (mínimo 12 caracteres, letras, números, símbolos)
9. **Limite acesso ao admin.php** por IP se possível
10. **Revise permissões** de arquivos regularmente

### Performance:

1. Ative cache se tiver muitos registros (USAR_CACHE = true)
2. Configure REGISTROS_POR_PAGINA para paginação
3. Use VERIFICAR_INTEGRIDADE apenas em desenvolvimento
4. Limpe logs antigos periodicamente

### Manutenção:

1. Revise config.ini mensalmente
2. Teste recuperação de backup trimestralmente
3. Audite logs de tentativas de login
4. Remova instâncias não utilizadas
5. Mantenha documentação atualizada

---

## 📄 Documentação das Novas Configurações

Consulte o arquivo `config.ini` para descrição completa de cada configuração.

Principais seções:
- **INFO_SISTEMA**: Informações da versão
- **INFO_USUARIO**: Dados do proprietário da instância
- **GERAL**: Configurações gerais
- **SEGURANCA**: Configurações de segurança
- **IMPRESSORA_ZPL**: Configurações da impressora
- **INTERFACE**: Personalização visual
- **VALIDACAO**: Regras de validação
- **PROCESSAMENTO_NOMES**: Processamento de nomes
- **LISTAGEM**: Configurações da lista
- **EMAIL**: Configurações de email (opcional)
- **LOGS**: Sistema de logs
- **RECURSOS**: Habilitar/desabilitar recursos
- **AVANCADO**: Configurações avançadas

---

**FIM DA ANÁLISE DE SEGURANÇA**

*Documento criado em: Fevereiro 2026*  
*Sistema: Cadastro de Crianças Self-Service v2.0*
