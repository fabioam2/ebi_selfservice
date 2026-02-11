# 🧹 Sistema de Limpeza Automática de Instâncias

Sistema automático para remover instâncias inativas após período configurável, garantindo privacidade e LGPD.

## 📋 Índice

- [Configuração](#configuração)
- [Uso Manual](#uso-manual)
- [Configuração do Cron](#configuração-do-cron)
- [Exemplos](#exemplos)
- [Troubleshooting](#troubleshooting)

---

## ⚙️ Configuração

### 1. Arquivo `template/config.ini`

A seção `[LIMPEZA_AUTOMATICA]` controla o comportamento do sistema:

```ini
[LIMPEZA_AUTOMATICA]
; Habilitar limpeza automática de instâncias inativas
HABILITAR_LIMPEZA = true

; Tempo de inatividade em horas antes de apagar a instância
HORAS_INATIVIDADE = 6

; Criar backup antes de remover
BACKUP_ANTES_REMOVER = true

; Diretório para backups das instâncias removidas
DIRETORIO_BACKUP = "/../../backups_removed/"

; Manter arquivos de log após remoção
MANTER_LOGS = false
```

### Parâmetros:

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `HABILITAR_LIMPEZA` | boolean | `true` | Ativa/desativa o sistema de limpeza |
| `HORAS_INATIVIDADE` | integer | `6` | Horas sem uso antes de remover |
| `BACKUP_ANTES_REMOVER` | boolean | `true` | Cria backup .tar.gz antes de remover |
| `DIRETORIO_BACKUP` | string | `"/../../backups_removed/"` | Onde salvar os backups |
| `MANTER_LOGS` | boolean | `false` | Mantém logs após remoção |

---

## 🖥️ Uso Manual

### Sintaxe

```bash
php cleanup_instances.php [opções]
```

### Opções

| Opção | Descrição |
|-------|-----------|
| `--dry-run` | Simula execução sem remover nada (teste) |
| `--force` | Remove sem confirmação (para cron) |
| `--verbose` | Mostra detalhes da execução |

### Exemplos de Uso Manual

**1. Testar sem remover nada:**
```bash
php cleanup_instances.php --dry-run
```

**2. Testar com detalhes:**
```bash
php cleanup_instances.php --dry-run --verbose
```

**3. Executar limpeza real:**
```bash
php cleanup_instances.php --force
```

**4. Executar com log detalhado:**
```bash
php cleanup_instances.php --force --verbose
```

---

## ⏰ Configuração do Cron

### Linux/Unix

Edite o crontab:
```bash
crontab -e
```

#### Exemplos de Configuração

**1. Executar a cada hora:**
```cron
0 * * * * php /caminho/completo/para/selfservice/cleanup_instances.php --force >> /var/log/cleanup_instances.log 2>&1
```

**2. Executar a cada 30 minutos:**
```cron
*/30 * * * * php /caminho/completo/para/selfservice/cleanup_instances.php --force >> /var/log/cleanup_instances.log 2>&1
```

**3. Executar a cada 2 horas:**
```cron
0 */2 * * * php /caminho/completo/para/selfservice/cleanup_instances.php --force >> /var/log/cleanup_instances.log 2>&1
```

**4. Executar apenas à noite (a cada 6 horas entre 18h e 6h):**
```cron
0 0,6,18 * * * php /caminho/completo/para/selfservice/cleanup_instances.php --force >> /var/log/cleanup_instances.log 2>&1
```

**5. Executar com mais detalhes (verbose):**
```cron
0 * * * * php /caminho/completo/para/selfservice/cleanup_instances.php --force --verbose >> /var/log/cleanup_instances.log 2>&1
```

### Dicas para Cron

- **Caminho completo:** Sempre use o caminho absoluto para o PHP e o script
- **Redirecionamento:** Use `>> arquivo.log 2>&1` para salvar logs
- **Permissões:** Certifique-se que o usuário do cron tem permissão para remover as pastas
- **Teste primeiro:** Execute manualmente com `--dry-run` antes de configurar o cron

---

## 📊 Exemplos de Saída

### Modo Dry-Run (Teste)

```
[2026-02-11 19:45:00] 🚀 Iniciando limpeza de instâncias inativas...
[2026-02-11 19:45:00] ⚠️  MODO DRY-RUN: Nenhuma instância será removida
[2026-02-11 19:45:00] 📊 Total de instâncias encontradas: 5
[2026-02-11 19:45:00]   [user_123] ✅ Ativa (última atividade há 2.5 horas)
[2026-02-11 19:45:00]   [user_456] 🗑️  INATIVA há 8.2 horas - será removida
[2026-02-11 19:45:00]   [user_789] ✅ Ativa (última atividade há 1.0 horas)

============================================================
📊 RESUMO DA LIMPEZA
============================================================
Total de instâncias analisadas: 5
Instâncias ativas: 4
Instâncias removidas: 1 (simulado)
============================================================
```

### Modo Real (com --force)

```
[2026-02-11 19:45:00] 🚀 Iniciando limpeza de instâncias inativas...
[2026-02-11 19:45:00] 📊 Total de instâncias encontradas: 5
[2026-02-11 19:45:00]   [user_456] 🗑️  INATIVA há 8.2 horas - será removida
[2026-02-11 19:45:01]   [user_456] 💾 Backup criado: user_456_20260211_194501.tar.gz
[2026-02-11 19:45:02]   [user_456] ✅ Instância removida com sucesso

============================================================
📊 RESUMO DA LIMPEZA
============================================================
Total de instâncias analisadas: 5
Instâncias ativas: 4
Instâncias removidas: 1
============================================================
```

---

## 🔧 Troubleshooting

### Problema: "Limpeza automática está DESABILITADA"

**Causa:** `HABILITAR_LIMPEZA = false` no config.ini

**Solução:** Edite `template/config.ini` e defina `HABILITAR_LIMPEZA = true`

---

### Problema: "Diretório de instâncias não encontrado"

**Causa:** O diretório `instances/` não existe

**Solução:**
```bash
mkdir -p selfservice/instances
```

---

### Problema: Instâncias não são removidas no cron

**Causa:** Permissões insuficientes ou caminho incorreto

**Solução:**
1. Verifique as permissões:
   ```bash
   ls -la selfservice/instances/
   ```

2. Teste manualmente como o usuário do cron:
   ```bash
   sudo -u www-data php cleanup_instances.php --dry-run --verbose
   ```

3. Verifique os logs do cron:
   ```bash
   tail -f /var/log/cleanup_instances.log
   ```

---

### Problema: Backups não são criados

**Causa:** Comando `tar` não disponível ou permissões

**Solução:**
1. Verifique se `tar` está instalado:
   ```bash
   which tar
   ```

2. Verifique permissões do diretório de backup:
   ```bash
   ls -la selfservice/backups_removed/
   ```

---

## 🔐 Segurança e LGPD

### Por que remover instâncias?

✅ **Conformidade com LGPD:** Dados de crianças não devem ser mantidos além do necessário
✅ **Minimização de risco:** Menos dados = menos superfície de ataque
✅ **Economia de espaço:** Libera espaço em disco automaticamente
✅ **Privacidade:** Garante que dados sensíveis não fiquem expostos

### Recomendações:

- 🕒 **6 horas** (padrão): Ideal para eventos de curta duração
- 🕐 **12 horas**: Para eventos que duram o dia todo
- 📅 **24 horas**: Para eventos de fim de semana
- 🚫 **Não desabilite**: Mantenha sempre ativo para proteção de dados

---

## 📝 Logs e Monitoramento

### Verificar logs do cron:

```bash
tail -f /var/log/cleanup_instances.log
```

### Verificar instâncias ativas:

```bash
ls -la selfservice/instances/
```

### Verificar backups criados:

```bash
ls -lh selfservice/backups_removed/
```

### Verificar último acesso de uma instância:

```bash
cat selfservice/instances/USER_ID/config/.lastaccess
date -r $(cat selfservice/instances/USER_ID/config/.lastaccess)
```

---

## 🆘 Suporte

Para mais informações ou problemas:
- Verifique os logs: `/var/log/cleanup_instances.log`
- Execute com `--verbose` para mais detalhes
- Teste com `--dry-run` antes de aplicar mudanças

---

**Versão:** 1.0
**Última atualização:** 2026-02-11
