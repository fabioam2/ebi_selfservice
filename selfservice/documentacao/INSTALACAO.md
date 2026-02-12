# 📦 Guia de Instalação - EBI Self-Service

Guia completo para instalação e configuração do sistema EBI Self-Service com as melhorias implementadas.

---

## 📋 Índice

1. [Pré-requisitos](#-pré-requisitos)
2. [Instalação Básica](#-instalação-básica)
3. [Configuração do Ambiente](#-configuração-do-ambiente)
4. [Instalação de Dependências](#-instalação-de-dependências)
5. [Configuração de Permissões](#-configuração-de-permissões)
6. [Verificação da Instalação](#-verificação-da-instalação)
7. [Configurações Avançadas](#-configurações-avançadas)
8. [Manutenção](#-manutenção)
9. [Solução de Problemas](#-solução-de-problemas)

---

## 🔧 Pré-requisitos

### Software Necessário

| Software | Versão Mínima | Versão Recomendada | Obrigatório |
|----------|---------------|-------------------|-------------|
| **PHP** | 7.4 | 8.1+ | ✅ Sim |
| **Composer** | 2.0 | 2.6+ | ✅ Sim |
| **Apache/Nginx** | Qualquer | Atual | ✅ Sim |
| **Git** | 2.0 | 2.40+ | ⚠️ Recomendado |

### Extensões PHP Necessárias

Verifique se as seguintes extensões estão instaladas:

```bash
php -m | grep -E '(json|mbstring|fileinfo|zip)'
```

**Extensões obrigatórias:**
- ✅ `json` - Manipulação de JSON
- ✅ `mbstring` - Suporte a multibyte strings
- ✅ `fileinfo` - Detecção de tipos de arquivo
- ⚠️ `zip` - Criação de backups (recomendado)

### Instalação de Extensões (se necessário)

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install php-json php-mbstring php-zip
sudo systemctl restart apache2
```

**CentOS/RHEL:**
```bash
sudo yum install php-json php-mbstring php-zip
sudo systemctl restart httpd
```

---

## 🚀 Instalação Básica

### 1. Clone o Repositório

```bash
# Clone do repositório
git clone https://github.com/fabioam2/ebi_selfservice.git
cd ebi_selfservice
```

### 2. Verifique a Estrutura

```bash
ls -la
```

**Você deve ver:**
```
.
├── composer.json           # ← Gerenciador de dependências
├── .env.example           # ← Template de configuração
├── .gitignore             # ← Proteção de arquivos
├── selfservice/           # ← Sistema principal
│   ├── selfservice.php    # ← Página de cadastro
│   ├── admin.php          # ← Painel admin
│   ├── criar_instancia.php
│   ├── inc/
│   │   └── rate_limit.php # ← Sistema de proteção
│   ├── template/          # ← Template de instâncias
│   └── documentacao/      # ← Documentação
├── ebi/                   # ← Sistema base
└── qrcode/                # ← Gerador QR Code
```

---

## ⚙️ Configuração do Ambiente

### 1. Criar Arquivo de Configuração

```bash
# Copiar template de configuração
cp .env.example .env
```

### 2. Editar Configurações

Abra o arquivo `.env` e ajuste os valores:

```bash
nano .env
# ou
vim .env
```

### 3. Configurações Essenciais

#### **Senha do Administrador**

Para gerar um novo hash de senha:

```bash
php -r "echo password_hash('SuaSenhaAqui', PASSWORD_BCRYPT) . PHP_EOL;"
```

Copie o resultado e cole em `.env`:
```ini
ADMIN_PASSWORD_HASH='$2y$12$...'
```

#### **Caminhos de Diretórios**

Ajuste os caminhos absolutos para seu servidor:

```ini
INSTANCE_BASE_PATH='/var/www/html/ebi_selfservice/selfservice/instances'
TEMPLATE_PATH='/var/www/html/ebi_selfservice/selfservice/template'
DATA_PATH='/var/www/html/ebi_selfservice/selfservice/data'
LOG_FILE='/var/www/html/ebi_selfservice/selfservice/data/app.log'
BACKUP_PATH='/var/www/html/ebi_selfservice/selfservice/backups'
```

#### **URL Base**

Configure a URL onde o sistema estará disponível:

```ini
BASE_URL='http://seu-dominio.com'
# ou para localhost:
BASE_URL='http://localhost'
```

#### **Rate Limiting**

Ajuste os limites de proteção contra abuso:

```ini
RATE_LIMIT_ENABLED='true'
RATE_LIMIT_MAX_REQUESTS='10'    # Número de requisições
RATE_LIMIT_TIME_WINDOW='3600'   # Janela de tempo (segundos)
```

#### **Configurações de Email (Opcional)**

Se quiser notificações por email:

```ini
EMAIL_ENABLED='true'
SMTP_HOST='smtp.gmail.com'
SMTP_PORT='587'
SMTP_USERNAME='seu-email@gmail.com'
SMTP_PASSWORD='sua-senha-app'
SMTP_ENCRYPTION='tls'
EMAIL_FROM='noreply@seu-dominio.com'
```

---

## 📦 Instalação de Dependências

### 1. Instalar Composer (se ainda não tiver)

**Linux/macOS:**
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

**Windows:**
- Baixe o instalador: https://getcomposer.org/Composer-Setup.exe
- Execute e siga as instruções

### 2. Instalar Dependências do Projeto

```bash
# Navegar até o diretório do projeto
cd /var/www/html/ebi_selfservice

# Instalar dependências de produção
composer install --no-dev --optimize-autoloader

# OU instalar com ferramentas de desenvolvimento (PHPUnit, PHPStan)
composer install
```

**Saída esperada:**
```
Loading composer repositories with package information
Installing dependencies from lock file
Package operations: 15 installs, 0 updates, 0 removals
  - Installing psr/log (2.0.0)
  - Installing monolog/monolog (2.9.0)
  - Installing vlucas/phpdotenv (5.6.0)
  ...
Generating optimized autoload files
```

### 3. Verificar Instalação

```bash
composer show
```

**Você deve ver:**
- ✅ `monolog/monolog` - Sistema de logging
- ✅ `vlucas/phpdotenv` - Variáveis de ambiente
- ⚠️ `phpunit/phpunit` (se instalou com dev)
- ⚠️ `phpstan/phpstan` (se instalou com dev)

---

## 🔒 Configuração de Permissões

### 1. Criar Diretórios Necessários

```bash
# A partir do diretório raiz do projeto
mkdir -p selfservice/data
mkdir -p selfservice/instances
mkdir -p selfservice/backups
mkdir -p selfservice/logs
```

### 2. Configurar Permissões

**Para Apache (www-data):**
```bash
# Dar permissão ao Apache para escrever em diretórios de dados
sudo chown -R www-data:www-data selfservice/data
sudo chown -R www-data:www-data selfservice/instances
sudo chown -R www-data:www-data selfservice/backups
sudo chown -R www-data:www-data selfservice/logs

# Permissões adequadas
chmod 755 selfservice/data
chmod 755 selfservice/instances
chmod 755 selfservice/backups
chmod 755 selfservice/logs
```

**Para Nginx (nginx ou www-data):**
```bash
sudo chown -R nginx:nginx selfservice/data
sudo chown -R nginx:nginx selfservice/instances
sudo chown -R nginx:nginx selfservice/backups
sudo chown -R nginx:nginx selfservice/logs
```

### 3. Proteger Arquivo .env

```bash
# CRÍTICO: Arquivo .env contém informações sensíveis
chmod 600 .env
chown www-data:www-data .env
```

### 4. Criar Arquivos .gitkeep

Para manter diretórios vazios no Git:

```bash
touch selfservice/instances/.gitkeep
touch selfservice/data/.gitkeep
touch selfservice/backups/.gitkeep
```

---

## ✅ Verificação da Instalação

### 1. Executar Instalador do Sistema

Acesse via navegador:
```
http://seu-dominio.com/selfservice/install.php
```

**O instalador irá:**
- ✅ Verificar versão do PHP (>= 7.4)
- ✅ Verificar extensões necessárias
- ✅ Criar estrutura de diretórios
- ✅ Configurar permissões
- ✅ Gerar credenciais de admin
- ✅ Criar arquivo `.instalado` (flag)

### 2. Verificar Extensões PHP

```bash
php -v
php -m | grep -E '(json|mbstring|fileinfo|zip)'
```

### 3. Testar Rate Limiting

Crie um script de teste:

```bash
cat > test_rate_limit.php << 'EOF'
<?php
require 'selfservice/inc/rate_limit.php';

$ip = '127.0.0.1';
$result = checkRateLimit($ip, 5, 3600);

echo $result ? "✅ Rate limiting OK\n" : "❌ Bloqueado\n";

$status = getRateLimitStatus($ip, 5, 3600);
echo "Requisições restantes: {$status['remaining']}\n";
echo "Reset em: {$status['reset_in']} segundos\n";
EOF

php test_rate_limit.php
```

### 4. Testar Autoloading

```bash
cat > test_autoload.php << 'EOF'
<?php
require 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('test');
$log->pushHandler(new StreamHandler('php://stdout'));
$log->info('✅ Autoloading funcionando!');
EOF

php test_autoload.php
```

### 5. Verificar Logs

```bash
# Ver logs do sistema
tail -f selfservice/data/app.log

# Ver logs do PHP
tail -f /var/log/apache2/error.log  # Apache
tail -f /var/log/nginx/error.log    # Nginx
```

---

## 🔧 Configurações Avançadas

### 1. Configurar VirtualHost (Apache)

```apache
<VirtualHost *:80>
    ServerName ebi.seu-dominio.com
    DocumentRoot /var/www/html/ebi_selfservice

    <Directory /var/www/html/ebi_selfservice>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Proteger arquivos sensíveis
    <FilesMatch "\.(env|ini|log|txt)$">
        Require all denied
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/ebi-error.log
    CustomLog ${APACHE_LOG_DIR}/ebi-access.log combined
</VirtualHost>
```

Ativar e reiniciar:
```bash
sudo a2ensite ebi.conf
sudo systemctl reload apache2
```

### 2. Configurar Server Block (Nginx)

```nginx
server {
    listen 80;
    server_name ebi.seu-dominio.com;
    root /var/www/html/ebi_selfservice;
    index index.php;

    location / {
        try_files $uri $uri/ =404;
    }

    # Proteger arquivos sensíveis
    location ~ \.(env|ini|log|txt)$ {
        deny all;
        return 404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }

    access_log /var/log/nginx/ebi-access.log;
    error_log /var/log/nginx/ebi-error.log;
}
```

Ativar e reiniciar:
```bash
sudo ln -s /etc/nginx/sites-available/ebi.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 3. Configurar HTTPS (Let's Encrypt)

```bash
sudo apt-get install certbot python3-certbot-apache  # Apache
# ou
sudo apt-get install certbot python3-certbot-nginx   # Nginx

sudo certbot --apache -d ebi.seu-dominio.com  # Apache
# ou
sudo certbot --nginx -d ebi.seu-dominio.com   # Nginx
```

Atualizar `.env`:
```ini
BASE_URL='https://ebi.seu-dominio.com'
```

### 4. Configurar Cron para Limpeza Automática

```bash
# Editar crontab
crontab -e

# Adicionar linha para executar limpeza diariamente às 3h da manhã
0 3 * * * php /var/www/html/ebi_selfservice/selfservice/cleanup_instances.php >> /var/log/ebi-cleanup.log 2>&1

# Limpeza de rate limit files (semanal)
0 4 * * 0 php -r "require '/var/www/html/ebi_selfservice/selfservice/inc/rate_limit.php'; cleanupOldRateLimitFiles(7);"
```

---

## 🧹 Manutenção

### Atualização de Dependências

```bash
# Atualizar todas as dependências
composer update

# Atualizar apenas uma biblioteca específica
composer update monolog/monolog

# Ver dependências desatualizadas
composer outdated
```

### Limpeza de Cache

```bash
# Limpar cache do Composer
composer clear-cache

# Limpar logs antigos (mais de 30 dias)
find selfservice/data/ -name "*.log" -mtime +30 -delete

# Limpar backups antigos (mais de 90 dias)
find selfservice/backups/ -name "*.zip" -mtime +90 -delete
```

### Backup do Sistema

```bash
#!/bin/bash
# backup-ebi.sh

BACKUP_DIR="/backups/ebi"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="ebi_backup_$DATE.tar.gz"

mkdir -p $BACKUP_DIR

# Backup completo (excluindo vendor e node_modules)
tar -czf "$BACKUP_DIR/$BACKUP_FILE" \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='selfservice/instances' \
    /var/www/html/ebi_selfservice/

echo "✅ Backup criado: $BACKUP_FILE"

# Manter apenas últimos 7 backups
ls -t $BACKUP_DIR/ebi_backup_*.tar.gz | tail -n +8 | xargs -r rm

echo "✅ Backups antigos removidos"
```

Tornar executável e agendar:
```bash
chmod +x backup-ebi.sh
crontab -e
# Adicionar: 0 2 * * * /caminho/backup-ebi.sh
```

---

## 🔍 Solução de Problemas

### Problema: "Class not found"

**Causa:** Autoload não configurado

**Solução:**
```bash
composer dump-autoload -o
```

### Problema: Rate limiting bloqueando acesso legítimo

**Causa:** Limites muito restritivos

**Solução:**
```bash
# Aumentar limites no .env
RATE_LIMIT_MAX_REQUESTS='20'
RATE_LIMIT_TIME_WINDOW='3600'

# OU limpar dados de rate limit
rm -f selfservice/data/ratelimit_*.json
```

### Problema: Erro de permissão ao criar instância

**Causa:** Apache/Nginx sem permissão de escrita

**Solução:**
```bash
sudo chown -R www-data:www-data selfservice/instances
sudo chmod 755 selfservice/instances
```

### Problema: .env não está sendo lido

**Causa:** PHPDotEnv não carregado

**Solução:**
Adicionar no início dos arquivos PHP:
```php
<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$adminHash = $_ENV['ADMIN_PASSWORD_HASH'];
```

### Problema: Composer muito lento

**Solução:**
```bash
# Usar mirror brasileiro
composer config -g repos.packagist composer https://packagist.com.br

# OU desabilitar verificação SSL (apenas dev)
composer config -g disable-tls true
```

### Logs de Debug

Ativar modo debug temporariamente:

```bash
# No .env
DEBUG_MODE='true'
APP_ENV='development'
LOG_LEVEL='debug'
```

Ver logs em tempo real:
```bash
tail -f selfservice/data/app.log
tail -f selfservice/data/erros.log
tail -f selfservice/data/rate_limit_violations.log
```

---

## 📚 Referências

- [Documentação Principal](README.md)
- [Análise de Segurança](ANALISE_SEGURANCA.md)
- [Mudanças e Melhorias](MUDANCAS_E_MELHORIAS.md)
- [Exemplos de Uso](EXEMPLOS_DE_USO.md)
- [Limpeza Automática](CLEANUP_README.md)

---

## 🆘 Suporte

### Problemas Comuns
- Verifique os logs em `selfservice/data/erros.log`
- Teste permissões de diretórios
- Valide configurações no `.env`
- Confirme extensões PHP instaladas

### Contato
- **Issues:** https://github.com/fabioam2/ebi_selfservice/issues
- **Documentação:** `/selfservice/documentacao/`

---

**Versão:** 2.0
**Última Atualização:** 2026-02-12
**Autor:** EBI Team
