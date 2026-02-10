# EXEMPLOS DE USO - Sistema Self-Service

## 📖 Índice de Exemplos

1. [Fluxo Básico de Uso](#fluxo-basico)
2. [Cenários de Uso](#cenarios)
3. [Exemplos de Código](#codigo)
4. [Casos de Teste](#testes)
5. [Troubleshooting](#troubleshooting)

---

## 🎯 Fluxo Básico de Uso

### Para Usuários Finais

#### Exemplo 1: Cadastro de um Novo Usuário

**Passo a Passo:**

1. João acessa: `https://igreja.com.br/selfservice.php`

2. Preenche o formulário:
   ```
   Nome: João Silva
   Email: joao@email.com
   Cidade: São Paulo
   Comum: Comum Central
   Senha: MinhaSenh@123
   Confirmar Senha: MinhaSenh@123
   ```

3. Clica em "Criar Minha Conta Grátis"

4. Recebe o link:
   ```
   https://igreja.com.br/instances/user_63f5a1b2e4d8c/public_html/ebi/index.php
   ```

5. Acessa o link e faz login com a senha `MinhaSenh@123`

6. Agora tem acesso total ao sistema de cadastro de crianças!

---

#### Exemplo 2: Múltiplos Usuários do Mesmo Comum

**Cenário:** Comum Asa Sul quer que 3 pessoas tenham acesso

**Cadastros:**

```
Usuário 1:
Nome: Maria Santos
Email: maria@asasul.com
Comum: Comum Asa Sul
→ Recebe: /instances/user_xxx/...

Usuário 2:
Nome: Pedro Costa
Email: pedro@asasul.com
Comum: Comum Asa Sul
→ Recebe: /instances/user_yyy/...

Usuário 3:
Nome: Ana Lima
Email: ana@asasul.com
Comum: Comum Asa Sul
→ Recebe: /instances/user_zzz/...
```

**Importante:** Cada um terá sua PRÓPRIA instância com dados SEPARADOS!
Se quiserem compartilhar, devem usar a MESMA conta.

---

### Para Administradores

#### Exemplo 3: Gerenciando Instâncias

**Acesso ao Admin:**
```
URL: https://igreja.com.br/admin.php
Senha: [senha gerada na instalação]
```

**Visualização no Painel:**
```
┌─────────────────────────────────────────────────────────┐
│ ESTATÍSTICAS                                            │
├─────────────────────────────────────────────────────────┤
│ Total de Instâncias: 15                                 │
│ Usuários Cadastrados: 15                                │
│ Criadas Hoje: 3                                         │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ LISTA DE INSTÂNCIAS                                     │
├──────────┬──────────────┬────────────┬─────────────────┤
│ Nome     │ Email        │ Comum      │ Data            │
├──────────┼──────────────┼────────────┼─────────────────┤
│ João     │ joao@...     │ Central    │ 09/02/26 10:30  │
│ Maria    │ maria@...    │ Asa Sul    │ 09/02/26 11:15  │
│ Pedro    │ pedro@...    │ Asa Sul    │ 09/02/26 11:20  │
└──────────┴──────────────┴────────────┴─────────────────┘
```

**Ações Disponíveis:**
- 🔗 Acessar qualquer instância
- 📋 Copiar link para compartilhar
- 🗑️ Remover instância (cria backup)

---

## 💼 Cenários de Uso Reais

### Cenário 1: Igreja com Múltiplos Comuns

**Situação:**
Igreja com 10 comuns. Cada comum quer seu próprio sistema.

**Solução:**
1. Cadastrar um responsável por comum
2. Cada comum recebe sua instância isolada
3. Admin pode monitorar todos

**Vantagens:**
- ✅ Dados separados por comum
- ✅ Cada comum gerencia independentemente
- ✅ Visão centralizada no admin

---

### Cenário 2: Evento com Múltiplas Portarias

**Situação:**
Evento com 5 portarias diferentes, cada uma precisa cadastrar crianças.

**Solução:**
1. Criar uma conta por portaria:
   ```
   Portaria Norte → portaria.norte@evento.com
   Portaria Sul → portaria.sul@evento.com
   Portaria Leste → portaria.leste@evento.com
   Portaria Oeste → portaria.oeste@evento.com
   Portaria Central → portaria.central@evento.com
   ```

2. Cada portaria trabalha em sua própria instância

3. No final, admin pode acessar todas as instâncias para consolidar dados

---

### Cenário 3: Treinamento de Equipe

**Situação:**
Precisa treinar 20 pessoas a usar o sistema.

**Solução:**
1. Criar instâncias de teste para cada participante
2. Cada um pratica em sua própria cópia
3. Após treinamento, pode deletar as instâncias de teste

```bash
# Exemplo de criação em massa
treinando01@exemplo.com → instância 1
treinando02@exemplo.com → instância 2
...
treinando20@exemplo.com → instância 20
```

---

## 💻 Exemplos de Código

### Exemplo 4: Personalizar Email de Boas-Vindas

**Arquivo:** `criar_instancia.php`

```php
// Adicionar após criar instância com sucesso

// Enviar email de boas-vindas
$para = $email;
$assunto = "Bem-vindo ao Sistema de Cadastro - $comum";
$mensagem = "
Olá $nome!

Sua instância do Sistema de Cadastro de Crianças foi criada com sucesso!

🔗 Link de Acesso: $link

📝 Suas Informações:
- Nome: $nome
- Email: $email
- Cidade: $cidade
- Comum: $comum

Use a senha que você cadastrou para fazer login.

Qualquer dúvida, entre em contato conosco.

Atenciosamente,
Equipe de Suporte
";

$headers = "From: noreply@seudominio.com\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

mail($para, $assunto, $mensagem, $headers);
```

---

### Exemplo 5: Adicionar Campo Extra no Cadastro

**Arquivo:** `selfservice.php`

```php
// Adicionar após o campo "comum"

<div class="form-group">
    <label for="telefone"><i class="fas fa-phone"></i> Telefone</label>
    <input type="tel" class="form-control" id="telefone" name="telefone" 
           placeholder="(00) 00000-0000">
</div>

// No processamento
$telefone = trim($_POST['telefone'] ?? '');

// Salvar no banco
$linha = implode('|', [
    $user_id,
    $email,
    $nome,
    $cidade,
    $comum,
    $telefone,  // novo campo
    $hash_senha,
    $data_cadastro
]);
```

---

### Exemplo 6: Limitar Número de Instâncias por Email

**Arquivo:** `selfservice.php`

```php
// Adicionar antes de criar instância

// Verificar quantas instâncias o email já tem
$countInstancias = 0;
if (file_exists(DB_SELFSERVICE)) {
    $usuarios = file(DB_SELFSERVICE, FILE_IGNORE_NEW_LINES);
    foreach ($usuarios as $usuario) {
        $dados = explode('|', $usuario);
        if (isset($dados[1]) && $dados[1] === $email) {
            $countInstancias++;
        }
    }
}

// Limitar a 3 instâncias por email
if ($countInstancias >= 3) {
    $erros[] = "Você já possui o máximo de 3 instâncias cadastradas com este email";
}
```

---

## 🧪 Casos de Teste

### Teste 1: Cadastro Básico

**Input:**
```
Nome: Teste Silva
Email: teste@teste.com
Cidade: Brasília
Comum: Comum Teste
Senha: 123456
```

**Resultado Esperado:**
- ✅ Usuário criado em `data/selfservice_users.txt`
- ✅ Diretório criado em `instances/user_xxx/`
- ✅ Arquivo config.ini criado com senha correta
- ✅ Link retornado funcional
- ✅ Login funciona com a senha cadastrada

---

### Teste 2: Validação de Email

**Input:**
```
Email: email-invalido
```

**Resultado Esperado:**
- ❌ Erro: "Email válido é obrigatório"
- ❌ Cadastro não processado

---

### Teste 3: Senhas Não Coincidem

**Input:**
```
Senha: 123456
Confirmar Senha: 654321
```

**Resultado Esperado:**
- ❌ Erro: "As senhas não coincidem"
- ❌ Cadastro não processado

---

### Teste 4: Email Duplicado

**Input:**
```
Email: joao@email.com (já existe)
```

**Resultado Esperado:**
- ❌ Erro: "Este email já está cadastrado"
- ❌ Cadastro não processado

---

### Teste 5: Senha Curta

**Input:**
```
Senha: 123
```

**Resultado Esperado:**
- ❌ Erro: "Senha deve ter no mínimo 6 caracteres"
- ❌ Cadastro não processado

---

## 🔧 Troubleshooting - Exemplos Práticos

### Problema 1: Link da Instância Não Funciona

**Sintoma:**
```
Erro 404 - Página não encontrada
```

**Diagnóstico:**
```bash
# Verificar se o diretório foi criado
ls -la instances/user_xxx/public_html/ebi/

# Verificar se o arquivo existe
ls -la instances/user_xxx/public_html/ebi/index.php

# Verificar permissões
ls -la instances/user_xxx/
```

**Solução:**
```bash
# Dar permissão correta
chmod 755 instances/user_xxx/
chmod 755 instances/user_xxx/public_html/
chmod 755 instances/user_xxx/public_html/ebi/
chmod 644 instances/user_xxx/public_html/ebi/index.php
```

---

### Problema 2: Erro ao Criar Instância

**Sintoma:**
```
Erro: Não foi possível criar diretório
```

**Diagnóstico:**
```bash
# Verificar permissões
ls -la instances/

# Verificar espaço em disco
df -h
```

**Solução:**
```bash
# Dar permissão de escrita
chmod 755 instances/

# Se necessário, liberar espaço
# Remover instâncias antigas não utilizadas
```

---

### Problema 3: Senha de Admin Esquecida

**Sintoma:**
```
Não consigo acessar admin.php
```

**Solução:**
```php
// Editar admin.php
// Linha ~5
define('SENHA_ADMIN', 'NovaSenha@123');

// Ou verificar em .instalado
cat .instalado
```

---

### Problema 4: Template Não Encontrado

**Sintoma:**
```
Erro ao ler arquivo template ebi.txt
```

**Diagnóstico:**
```bash
# Verificar se existe
ls -la template/ebi.txt

# Verificar conteúdo
head -n 10 template/ebi.txt
```

**Solução:**
```bash
# Copiar arquivo correto
cp /caminho/do/seu/sistema.php template/ebi.txt

# Verificar permissões
chmod 644 template/ebi.txt
```

---

## 📊 Monitoramento e Logs

### Exemplo 7: Verificar Logs de Criação

```bash
# Ver últimas 10 instâncias criadas
tail -n 10 data/instancias_criadas.log

# Exemplo de saída:
# 2026-02-09 10:30:45|user_xxx|João Silva|joao@email.com|SP|Central|http://...
# 2026-02-09 11:15:22|user_yyy|Maria Santos|maria@email.com|DF|Asa Sul|http://...
```

---

### Exemplo 8: Verificar Erros

```bash
# Ver erros
cat data/erros.log

# Filtrar erros de hoje
grep "$(date +%Y-%m-%d)" data/erros.log
```

---

### Exemplo 9: Estatísticas Rápidas

```bash
# Contar total de usuários
wc -l < data/selfservice_users.txt

# Contar instâncias criadas
ls -1 instances/ | wc -l

# Contar instâncias criadas hoje
grep "$(date +%Y-%m-%d)" data/instancias_criadas.log | wc -l
```

---

## 🔄 Backup e Restauração

### Exemplo 10: Fazer Backup Manual

```bash
# Backup completo
tar -czf backup-$(date +%Y%m%d-%H%M%S).tar.gz instances/ data/

# Backup de uma instância específica
tar -czf backup-user_xxx-$(date +%Y%m%d).tar.gz instances/user_xxx/
```

---

### Exemplo 11: Restaurar Instância

```bash
# Extrair backup
tar -xzf backup-user_xxx-20260209.tar.gz

# Restaurar permissões
chmod -R 755 instances/user_xxx/
```

---

## 🎨 Personalização Avançada

### Exemplo 12: Mudar Cores do Tema

**Arquivo:** `selfservice.php`

```css
/* Trocar gradiente roxo por azul */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
/* para */
background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);

/* Trocar cor primária */
color: #667eea;
/* para */
color: #2193b0;
```

---

### Exemplo 13: Adicionar Logo Personalizado

**Arquivo:** `selfservice.php`

```html
<!-- Substituir ícone padrão -->
<i class="fas fa-users icon-header"></i>

<!-- Por logo personalizado -->
<img src="logo.png" alt="Logo" style="max-width: 200px;">
```

---

## ✅ Checklist de Testes Completo

Antes de colocar em produção:

- [ ] Instalação executada sem erros
- [ ] Template configurado corretamente
- [ ] Cadastro de usuário teste funcionando
- [ ] Link da instância acessível
- [ ] Login na instância funcionando
- [ ] Painel admin acessível
- [ ] Busca no admin funcionando
- [ ] Copiar link funcionando
- [ ] Remover instância funcionando (teste em dev!)
- [ ] Logs sendo criados
- [ ] Permissões corretas
- [ ] .htaccess protegendo arquivos
- [ ] HTTPS configurado (produção)
- [ ] Backup configurado

---

**FIM DOS EXEMPLOS**

Para mais informações, consulte README.md
