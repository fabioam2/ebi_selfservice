<?php
/** @var array<string,string> $configAtual */
$taskRows = lifecycle_listar_tarefas();
$tasksByName = [];
foreach ($taskRows as $taskRow) {
    $tasksByName[$taskRow['task_name']] = $taskRow;
}

$taskDefinitions = [
    'sensitive_data_cleanup' => [
        'title' => 'Limpeza de dados sensíveis',
        'schedule' => 'A cada hora',
        'description' => 'Remove cadastros e saídas após o prazo configurado, preservando as estatísticas agregadas.',
    ],
    'instance_lifecycle_daily' => [
        'title' => 'Ciclo de inatividade e quarentena',
        'schedule' => 'Uma vez ao dia',
        'description' => 'Envia avisos, coloca instâncias inativas em quarentena e expurga apenas quarentenas vencidas.',
    ],
];

$cronCommand = '*/15 * * * * /usr/bin/php ' . SELFSERVICE_ROOT . '/tarefas_agendadas.php >> ' . DATA_PATH . '/tarefas_agendadas.log 2>&1';

// Logs isolados por finalidade
$cronLogFile = DATA_PATH . '/tarefas_agendadas.log';
$lifecycleLogFile = DATA_PATH . '/instance_lifecycle.log';

$cronLogLines = lifecycle_ler_log($cronLogFile);
$lifecycleLogLines = lifecycle_ler_log($lifecycleLogFile);

// Log de ciclo de vida no formato data|EVENTO|user_id|detalhes — separa a
// limpeza dos demais eventos para facilitar a auditoria.
$eventosLimpeza = ['DADOS_SENSIVEIS_LIMPOS', 'LIMPEZA_MANUAL', 'LIMPEZA_MANUAL_LOTE', 'ERRO_LIMPEZA_SENSIVEL', 'ERRO_LIMPEZA_MANUAL'];
$logLimpeza = [];
$logOutros = [];
foreach ($lifecycleLogLines as $linha) {
    $partes = explode('|', $linha, 4);
    $evento = $partes[1] ?? '';
    if (in_array($evento, $eventosLimpeza, true)) {
        $logLimpeza[] = $partes;
    } else {
        $logOutros[] = $partes;
    }
}
$logLimpeza = array_reverse($logLimpeza);
$logOutros = array_reverse($logOutros);

$instanciasAtivas = lifecycle_listar_usuarios_ativos();
?>

<div class="content-header">
    <h2><i class="fas fa-clock mr-2"></i>Tarefas Agendadas</h2>
    <p class="text-muted mb-0">Rotinas automáticas, limpeza manual e registros de execução em um só lugar.</p>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle mr-2"></i>O cron pode executar a cada 15 minutos. O próprio sistema registra a última execução e só roda as tarefas horárias e diárias quando necessário.
</div>

<!-- ── Rotinas automáticas ─────────────────────────────────────────────── -->
<h5 class="mb-3"><i class="fas fa-robot mr-2 text-muted"></i>Rotinas automáticas</h5>
<div class="row">
    <?php foreach ($taskDefinitions as $taskName => $definition): ?>
        <?php $task = $tasksByName[$taskName] ?? null; ?>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-<?php echo $task !== null && ($task['last_status'] ?? '') === 'ok' ? 'success' : 'secondary'; ?> text-white">
                    <h5 class="mb-0"><i class="fas fa-<?php echo $taskName === 'sensitive_data_cleanup' ? 'broom' : 'shield-alt'; ?> mr-2"></i><?php echo htmlspecialchars($definition['title']); ?></h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small"><?php echo htmlspecialchars($definition['description']); ?></p>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Frequência</dt>
                        <dd class="col-sm-7"><?php echo htmlspecialchars($definition['schedule']); ?></dd>
                        <dt class="col-sm-5">Última execução</dt>
                        <dd class="col-sm-7"><?php echo $task !== null ? htmlspecialchars(date('d/m/Y H:i:s', strtotime((string)$task['last_run_at']))) : 'Ainda não executada'; ?></dd>
                        <dt class="col-sm-5">Estado</dt>
                        <dd class="col-sm-7">
                            <?php if ($task === null): ?>
                                <span class="badge badge-secondary">aguardando cron</span>
                            <?php elseif (($task['last_status'] ?? '') === 'ok'): ?>
                                <span class="badge badge-success">ok</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><?php echo htmlspecialchars((string)$task['last_status']); ?></span>
                            <?php endif; ?>
                        </dd>
                        <dt class="col-sm-5">Resumo</dt>
                        <dd class="col-sm-7"><code class="small"><?php echo $task !== null ? htmlspecialchars((string)$task['details']) : '-'; ?></code></dd>
                    </dl>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ── Limpeza manual ──────────────────────────────────────────────────── -->
<h5 class="mb-3"><i class="fas fa-hand-sparkles mr-2 text-muted"></i>Limpeza manual</h5>
<div class="card mb-4 border-warning">
    <div class="card-header bg-warning">
        <h5 class="mb-0"><i class="fas fa-broom mr-2"></i>Limpar dados de crianças agora</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-secondary small mb-3">
            <i class="fas fa-shield-alt mr-1"></i>
            Remove <strong>cadastros e saídas</strong> imediatamente, sem esperar o prazo de retenção.
            As <strong>estatísticas são preservadas</strong>: elas ficam em tabelas separadas
            (<code>stats_daily</code> no banco de cada instância e <code>admin_daily_stats</code> no banco central)
            e não guardam nomes de crianças.
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <h6 class="font-weight-bold">Uma instância</h6>
                <form method="post" onsubmit="return confirm('Confirma a limpeza dos dados desta instância? Os cadastros e saídas serão apagados imediatamente.');">
                    <?php echo admin_csrf_field(); ?>
                    <div class="form-group">
                        <label for="limpar_user_id" class="small">Instância</label>
                        <select class="form-control" id="limpar_user_id" name="user_id" required>
                            <option value="">— selecione —</option>
                            <?php foreach ($instanciasAtivas as $instancia): ?>
                                <option value="<?php echo htmlspecialchars((string)$instancia['user_id']); ?>">
                                    <?php
                                    $rotulo = (string)$instancia['user_id'];
                                    $extra = trim(((string)($instancia['cidade'] ?? '')) . ' / ' . ((string)($instancia['comum'] ?? '')), ' /');
                                    echo htmlspecialchars($extra !== '' ? "{$rotulo} — {$extra}" : $rotulo);
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="limpar_dados_instancia" value="1" class="btn btn-warning">
                        <i class="fas fa-broom mr-1"></i>Limpar esta instância
                    </button>
                </form>
            </div>

            <div class="col-md-6 mb-3">
                <h6 class="font-weight-bold text-danger">Todas as instâncias ativas</h6>
                <form method="post" onsubmit="return confirm('ATENÇÃO: isso apaga os cadastros e saídas de TODAS as instâncias ativas. Confirma?');">
                    <?php echo admin_csrf_field(); ?>
                    <div class="form-group">
                        <label for="confirmar_limpeza_total" class="small">Digite <code>LIMPAR</code> para confirmar</label>
                        <input type="text" class="form-control" id="confirmar_limpeza_total" name="confirmar_limpeza_total" placeholder="LIMPAR" autocomplete="off" required>
                    </div>
                    <button type="submit" name="limpar_dados_todas" value="1" class="btn btn-danger">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Limpar todas (<?php echo count($instanciasAtivas); ?>)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── Agendamento no servidor ─────────────────────────────────────────── -->
<h5 class="mb-3"><i class="fas fa-server mr-2 text-muted"></i>Agendamento no servidor</h5>
<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-terminal mr-2"></i>Linha do crontab</h5>
    </div>
    <div class="card-body">
        <p>Adicione a linha abaixo ao crontab do usuário que executa o PHP no servidor:</p>
        <pre class="bg-light p-3 mb-3" style="overflow-x:auto;white-space:pre-wrap;word-break:break-all"><code><?php echo htmlspecialchars($cronCommand); ?></code></pre>
        <a class="btn btn-outline-primary" href="?page=docs&amp;doc=TAREFAS_AGENDADAS.md">
            <i class="fas fa-book mr-1"></i>Ver documentação completa
        </a>
    </div>
</div>

<!-- ── Logs ────────────────────────────────────────────────────────────── -->
<h5 class="mb-3"><i class="fas fa-list-alt mr-2 text-muted"></i>Registros de atividade</h5>
<div class="card mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Logs</h5>
        <button type="button" class="btn btn-outline-light btn-sm" onclick="window.location.reload()">
            <i class="fas fa-sync-alt mr-1"></i>Atualizar
        </button>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs mb-3" id="logTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-log-limpeza" data-toggle="tab" href="#log-limpeza" role="tab">
                    <i class="fas fa-broom mr-1"></i>Limpeza <span class="badge badge-secondary"><?php echo count($logLimpeza); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-log-cron" data-toggle="tab" href="#log-cron" role="tab">
                    <i class="fas fa-clock mr-1"></i>Execuções do cron <span class="badge badge-secondary"><?php echo count($cronLogLines); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-log-ciclo" data-toggle="tab" href="#log-ciclo" role="tab">
                    <i class="fas fa-shield-alt mr-1"></i>Ciclo de vida <span class="badge badge-secondary"><?php echo count($logOutros); ?></span>
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Log de limpeza -->
            <div class="tab-pane fade show active" id="log-limpeza" role="tabpanel">
                <p class="small text-muted">
                    Eventos de limpeza de dados — automáticos e manuais. Origem: <code><?php echo htmlspecialchars(basename($lifecycleLogFile)); ?></code>
                </p>
                <?php if (empty($logLimpeza)): ?>
                    <div class="alert alert-secondary mb-0">Nenhuma limpeza registrada até o momento.</div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height:45vh;overflow:auto">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:20%">Data/Hora</th>
                                    <th style="width:25%">Evento</th>
                                    <th style="width:20%">Instância</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logLimpeza as $entrada): ?>
                                    <?php $ehErro = strpos((string)($entrada[1] ?? ''), 'ERRO') === 0; ?>
                                    <tr class="<?php echo $ehErro ? 'table-danger' : ''; ?>">
                                        <td class="small"><?php echo htmlspecialchars((string)($entrada[0] ?? '')); ?></td>
                                        <td class="small">
                                            <span class="badge badge-<?php echo $ehErro ? 'danger' : 'success'; ?>">
                                                <?php echo htmlspecialchars((string)($entrada[1] ?? '')); ?>
                                            </span>
                                        </td>
                                        <td class="small"><code><?php echo htmlspecialchars((string)($entrada[2] ?? '')); ?></code></td>
                                        <td class="small"><?php echo htmlspecialchars((string)($entrada[3] ?? '')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Log do cron -->
            <div class="tab-pane fade" id="log-cron" role="tabpanel">
                <p class="small text-muted">
                    Saída de cada execução do cron (a cada 15 minutos). Origem: <code><?php echo htmlspecialchars(basename($cronLogFile)); ?></code>
                </p>
                <?php if (empty($cronLogLines)): ?>
                    <div class="alert alert-secondary mb-0">Ainda não há registros. Confirme que o cron foi configurado e executado.</div>
                <?php else: ?>
                    <pre class="bg-dark text-light p-3 mb-0" style="max-height:45vh;overflow:auto;white-space:pre-wrap;word-break:break-word"><code><?php echo htmlspecialchars(implode("\n", array_reverse($cronLogLines)), ENT_QUOTES, 'UTF-8'); ?></code></pre>
                <?php endif; ?>
            </div>

            <!-- Log de ciclo de vida -->
            <div class="tab-pane fade" id="log-ciclo" role="tabpanel">
                <p class="small text-muted">
                    Quarentena, recuperação, expurgo e avisos de inatividade. Origem: <code><?php echo htmlspecialchars(basename($lifecycleLogFile)); ?></code>
                </p>
                <?php if (empty($logOutros)): ?>
                    <div class="alert alert-secondary mb-0">Nenhum evento de ciclo de vida registrado até o momento.</div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height:45vh;overflow:auto">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:20%">Data/Hora</th>
                                    <th style="width:25%">Evento</th>
                                    <th style="width:20%">Instância</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logOutros as $entrada): ?>
                                    <?php $ehErro = strpos((string)($entrada[1] ?? ''), 'ERRO') === 0; ?>
                                    <tr class="<?php echo $ehErro ? 'table-danger' : ''; ?>">
                                        <td class="small"><?php echo htmlspecialchars((string)($entrada[0] ?? '')); ?></td>
                                        <td class="small">
                                            <span class="badge badge-<?php echo $ehErro ? 'danger' : 'info'; ?>">
                                                <?php echo htmlspecialchars((string)($entrada[1] ?? '')); ?>
                                            </span>
                                        </td>
                                        <td class="small"><code><?php echo htmlspecialchars((string)($entrada[2] ?? '')); ?></code></td>
                                        <td class="small"><?php echo htmlspecialchars((string)($entrada[3] ?? '')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Política atual ──────────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-sliders-h mr-2"></i>Política atual</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3 mb-3"><strong><?php echo htmlspecialchars($configAtual['SENSITIVE_DATA_RETENTION_HOURS'] ?? '24'); ?> h</strong><br><small class="text-muted">dados sensíveis</small></div>
            <div class="col-md-3 mb-3"><strong><?php echo htmlspecialchars($configAtual['INACTIVITY_WARNING_DAYS'] ?? '30'); ?> dias</strong><br><small class="text-muted">até o primeiro aviso</small></div>
            <div class="col-md-3 mb-3"><strong><?php echo htmlspecialchars($configAtual['INACTIVITY_GRACE_DAYS'] ?? '30'); ?> dias</strong><br><small class="text-muted">prazo após o aviso</small></div>
            <div class="col-md-3 mb-3"><strong><?php echo htmlspecialchars($configAtual['QUARANTINE_RETENTION_DAYS'] ?? '7'); ?> dias</strong><br><small class="text-muted">prazo de recuperação</small></div>
        </div>
        <a href="?page=settings" class="btn btn-outline-secondary btn-sm"><i class="fas fa-cog mr-1"></i>Ajustar configurações</a>
    </div>
</div>
