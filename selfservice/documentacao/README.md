# Sistema Self-Service - Cadastro de Crianças

Sistema de auto-atendimento que permite que usuários criem suas próprias instâncias do Sistema de Cadastro de Crianças.

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Requisitos](#requisitos)
3. [Instalação](#instalação)
4. [Estrutura de Arquivos](#estrutura-de-arquivos)
5. [Configuração](#configuração)
6. [Uso](#uso)
7. [Administração](#administração)
8. [Segurança](#segurança)
9. [Suporte](#suporte)

---

## 🎯 Visão Geral

Este sistema permite que usuários:
- Criem uma conta gratuitamente
- Recebam uma instância isolada do sistema
- Gerenciem seus próprios dados
- Tenham acesso completo ao sistema de cadastro de crianças

### Funcionalidades Principais

✅ Cadastro de usuários com validação
✅ Criação automática de instâncias isoladas
✅ Painel administrativo para gerenciamento
✅ Sistema de segurança com senhas criptografadas
✅ Backup automático dos dados
✅ Interface responsiva e moderna

---

## 💻 Requisitos

- **Servidor Web**: Apache 2.4+ ou Nginx
- **PHP**: 7.4 ou superior
- **Extensões PHP necessárias**:
  - php-curl
  - php-gd
  - php-mbstring
  - php-xml
- **Permissões**: Escrita em diretórios

---

## 🚀 Instalação

### Passo 1: Upload dos Arquivos

Faça upload de todos os arquivos para o seu servidor web:

```
/public_html/
├── selfservice.php          # Página de cadastro
├── admin.php                # Painel administrativo
├── criar_instancia.php      # Script de criação de instâncias
├── template/
│   └── ebi.txt              # Template do sistema
├── instances/               # Instâncias dos usuários (criado automaticamente)
├── data/                    # Dados do sistema (criado automaticamente)
└── backups/                 # Backups (criado automaticamente)
```

### Passo 2: Configurar Permissões

Dê permissão de escrita aos seguintes diretórios:

```bash
chmod 755 instances/
chmod 755 data/
chmod 755 backups/
chmod 755 template/
```

### Passo 3: Copiar Template

Certifique-se de que o arquivo `ebi.txt` (seu sistema original) está na pasta `template/`:

```bash
cp seu_sistema_original.php template/ebi.txt
```

### Passo 4: Configurar Senha de Admin

Edite o arquivo `admin.php` e altere a senha de administrador:

```php
define('SENHA_ADMIN', 'SuaSenhaSegura123!');
```

### Passo 5: Testar

Acesse em seu navegador:
- `http://seudominio.com/selfservice.php` - Página de cadastro
- `http://seudominio.com/admin.php` - Painel administrativo

---

## 📁 Estrutura de Arquivos

### Após a instalação e criação de instâncias:

```
/
├── selfservice.php              # Página de cadastro público
├── admin.php                    # Painel admin (protegido por senha)
├── criar_instancia.php          # Funções de criação de instâncias
│
├── template/
│   └── ebi.txt                  # Template do sistema original
│
├── data/
│   ├── selfservice_users.txt    # Banco de dados de usuários
│   ├── instancias_criadas.log   # Log de instâncias criadas
│   ├── instancias_removidas.log # Log de instâncias removidas
│   └── erros.log                # Log de erros
│
├── instances/
│   ├── user_xxxxx/              # Instância do usuário 1
│   │   ├── config/
│   │   │   ├── config.ini
│   │   │   ├── cadastro_criancas.txt
│   │   │   └── painel_criancas.txt
│   │   ├── public_html/
│   │   │   └── ebi/
│   │   │       └── index.php
│   │   ├── README.txt
│   │   └── system.log
│   │
│   └── user_yyyyy/              # Instância do usuário 2
│       └── ...
│
└── backups/                     # Backups de instâncias removidas
```

---

## ⚙️ Configuração

### Configurações Principais

As configurações são feitas automaticamente na criação de cada instância. Cada usuário recebe:

1. **Arquivo config.ini personalizado** com:
   - Senha única definida pelo usuário
   - Informações do comum e cidade
   - Configurações da impressora ZPL

2. **Arquivos de dados vazios** prontos para uso

3. **Sistema completo** idêntico ao original

### Personalização

Você pode personalizar o template editando:
- `template/ebi.txt` - Sistema base
- `selfservice.php` - Página de cadastro
- `admin.php` - Painel administrativo

---

## 🎮 Uso

### Para Usuários

1. Acesse `selfservice.php`
2. Preencha o formulário de cadastro:
   - Nome completo
   - Email válido
   - Cidade
   - Nome do comum
   - Senha (mínimo 6 caracteres)
3. Clique em "Criar Minha Conta Grátis"
4. Receba o link da sua instância
5. Acesse o link e faça login com sua senha

### Para Administradores

1. Acesse `admin.php`
2. Faça login com a senha de administrador
3. Visualize todas as instâncias criadas
4. Gerencie usuários:
   - Ver estatísticas
   - Acessar instâncias
   - Copiar links
   - Remover instâncias (com cuidado!)

---

## 🔒 Segurança

### Medidas Implementadas

✅ **Validação de dados**: Todos os inputs são validados
✅ **Sanitização**: Proteção contra XSS e injeção
✅ **Senhas criptografadas**: Hash bcrypt
✅ **Isolamento**: Cada usuário tem seu próprio diretório
✅ **Proteção de arquivos**: .htaccess bloqueia acesso direto
✅ **Logs**: Registro de todas as ações

### Recomendações de Segurança

1. **Altere a senha de admin** imediatamente após a instalação
2. **Use HTTPS** em produção
3. **Faça backups regulares** do diretório `instances/`
4. **Monitore os logs** em `data/`
5. **Limite o acesso** ao `admin.php` por IP se possível
6. **Mantenha o PHP atualizado**

### Proteção Adicional

Adicione ao `.htaccess` na raiz:

```apache
# Bloquear acesso ao admin por IP (opcional)
<Files "admin.php">
    Order Deny,Allow
    Deny from all
    Allow from 192.168.1.100
    # Adicione IPs confiáveis aqui
</Files>

# Proteção contra listagem de diretórios
Options -Indexes

# Proteção de arquivos sensíveis
<FilesMatch "\.(txt|log|ini)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

## 📊 Administração

### Painel Administrativo

O painel `admin.php` oferece:

**Estatísticas**:
- Total de instâncias criadas
- Total de usuários cadastrados
- Instâncias criadas hoje

**Gerenciamento**:
- Visualizar todos os usuários
- Buscar por nome, email, cidade, comum
- Acessar qualquer instância
- Copiar links para compartilhar
- Remover instâncias (cria backup automático)

### Logs e Monitoramento

Verifique regularmente os arquivos de log:

```bash
# Ver últimas instâncias criadas
tail -f data/instancias_criadas.log

# Ver erros
tail -f data/erros.log

# Ver instâncias removidas
tail -f data/instancias_removidas.log
```

### Manutenção

**Limpeza de instâncias antigas**:
- Use o painel admin para remover instâncias
- Backups são criados automaticamente em `backups/`

**Backup do sistema**:
```bash
# Fazer backup completo
tar -czf backup-selfservice-$(date +%Y%m%d).tar.gz instances/ data/
```

---

## 🆘 Suporte

### Problemas Comuns

**Erro: "Arquivo de configuração não encontrado"**
- Verifique se `template/ebi.txt` existe
- Verifique as permissões do arquivo

**Erro: "Não foi possível criar diretório"**
- Verifique permissões: `chmod 755 instances/`
- Verifique se o servidor tem espaço em disco

**Usuário não consegue acessar a instância**
- Verifique se o link está correto
- Verifique permissões do diretório da instância
- Verifique logs em `data/erros.log`

**Senha incorreta no admin**
- Edite `admin.php` e redefina a senha
- Limpe o cache do navegador

### Debug

Ativar modo debug (apenas em desenvolvimento):

```php
// No início do arquivo que está com problema
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Logs

Todos os logs ficam em `data/`:
- `instancias_criadas.log` - Registro de criações
- `erros.log` - Erros do sistema
- `instancias_removidas.log` - Registro de remoções

---

## 📝 Notas Adicionais

### Limitações

- Cada usuário pode criar apenas uma instância por email
- Links são únicos e não podem ser alterados
- Remoção de instâncias é permanente (exceto backup)

### Melhorias Futuras

Possíveis melhorias a implementar:
- [ ] Sistema de recuperação de senha
- [ ] Email de confirmação no cadastro
- [ ] Painel do usuário para gerenciar própria instância
- [ ] Estatísticas de uso por instância
- [ ] Exportação de dados
- [ ] Temas personalizáveis
- [ ] API REST para integração

### Customização

Para personalizar o visual:
- Edite os estilos CSS em `selfservice.php`
- Modifique o layout em `admin.php`
- Altere cores, fontes e ícones conforme necessário

---

## 📄 Licença

Este sistema é fornecido "como está", sem garantias.
Use por sua conta e risco.

---

## 🤝 Contribuições

Para reportar bugs ou sugerir melhorias, entre em contato com o desenvolvedor.

---

**Desenvolvido com ❤️ para a comunidade**

*Última atualização: Fevereiro 2026*
