# Tarefas Agendadas e Ciclo de Vida

O arquivo `selfservice/tarefas_agendadas.php` executa a manutencao automatica das instancias. Ele deve ser chamado pelo cron a cada 15 minutos. O proprio sistema guarda o horario de cada tarefa no banco central e executa somente as rotinas que estiverem vencidas.

## Cron

Edite o crontab do usuario que executa o PHP no servidor e ajuste os caminhos para a instalacao real:

```cron
*/15 * * * * /usr/bin/php /caminho/para/ebi_selfservice/selfservice/tarefas_agendadas.php >> /caminho/para/ebi_selfservice/selfservice/data/tarefas_agendadas.log 2>&1
```

O comando pode ser executado manualmente para uma verificacao controlada:

```sh
php selfservice/tarefas_agendadas.php
```

`--force` ignora temporariamente os intervalos registrados. Use somente com cuidado, pois pode executar a limpeza de dados ou o expurgo de quarentenas vencidas.

O antigo `cleanup_instances.php` foi mantido apenas para compatibilidade. Ele nao remove mais diretorios e delega a execucao para `tarefas_agendadas.php`.

## Rotinas

| Rotina | Frequencia | Efeito |
| --- | --- | --- |
| Limpeza de dados sensiveis | Horaria | Remove somente `cadastros` e `saidas` de uma instancia que esta sem novos cadastros pelo prazo configurado. `stats_daily` e `admin_daily_stats` nao sao alteradas. |
| Inatividade e quarentena | Diaria | Detecta instancias sem acesso, envia avisos periodicos, coloca em quarentena apos o prazo e expurga apenas quarentenas vencidas. |

O acesso e determinado pelo arquivo `.lastaccess` escrito pelo bootstrap da instancia. Se ele nao existir, o sistema usa a data de modificacao do `config.ini` como ultimo recurso.

## Configuracao

As variaveis abaixo ficam no `.env` e tambem podem ser alteradas em **Administracao > Configuracoes**:

| Variavel | Padrao | Finalidade |
| --- | ---: | --- |
| `SENSITIVE_DATA_RETENTION_HOURS` | `24` | Prazo sem novos cadastros antes de apagar dados identificaveis. |
| `INACTIVITY_WARNING_DAYS` | `30` | Inatividade ate o primeiro aviso. |
| `INACTIVITY_GRACE_DAYS` | `30` | Prazo entre o primeiro aviso e a quarentena. |
| `INACTIVITY_REMINDER_DAYS` | `7` | Intervalo dos lembretes durante o prazo. |
| `QUARANTINE_RETENTION_DAYS` | `7` | Prazo para recuperar a instancia antes do expurgo definitivo. |
| `INSTANCE_ACTION_TOKEN_HOURS` | `168` | Validade do link de confirmacao de exclusao. |
| `QUARANTINE_PATH` | `selfservice/data/quarantine` | Diretorio protegido que guarda instancias em quarentena. |

## Quarentena e recuperacao

Uma solicitacao de exclusao nunca apaga a instancia ao abrir o link do e-mail. O link abre uma pagina de confirmacao e a acao ocorre apenas por `POST` com CSRF. Os tokens sao aleatorios, armazenados apenas como hash SHA-256, possuem prazo e so podem ser consumidos uma vez.

Quando a instancia entra em quarentena, a pasta inteira e movida para `QUARANTINE_PATH`; a conta central e desativada e um e-mail de recuperacao e enviado. A restauracao move a pasta de volta e reativa a conta. Depois de `QUARANTINE_RETENTION_DAYS`, a tarefa diaria remove arquivos, tokens e a conta central, mas preserva as estatisticas agregadas.

## Monitoramento

Em **Administracao > Tarefas Agendadas**, o painel mostra a ultima execucao, estado e resumo agregado de cada rotina. O botao **Ver logs de execucao** abre as ultimas 200 linhas do log do cron. Os logs ficam em `selfservice/data/tarefas_agendadas.log` e `selfservice/data/instance_lifecycle.log`.

Os resumos nao armazenam ou exibem nomes de criancas.