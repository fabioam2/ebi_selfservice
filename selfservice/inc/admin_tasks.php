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
$cronLogFile = DATA_PATH . '/tarefas_agendadas.log';
$cronLogLines = [];

if (is_file($cronLogFile) && is_readable($cronLogFile)) {
    $handle = fopen($cronLogFile, 'rb');
    if ($handle !== false) {
        $size = filesize($cronLogFile);
        $start = max(0, (int)$size - 65536);
        fseek($handle, $start);
        if ($start > 0) {
            fgets($handle);
        }

        while (($line = fgets($handle)) !== false) {
            $cronLogLines[] = rtrim($line, "\r\n");
        }
        fclose($handle);
        $cronLogLines = array_slice($cronLogLines, -200);
    }
}
?>

<div class="content-header">
    <h2><i class="fas fa-clock mr-2"></i>Tarefas Agendadas</h2>
    <p class="text-muted mb-0">Acompanhe as rotinas automáticas de retenção, quarentena e expurgo.</p>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle mr-2"></i>O cron pode executar a cada 15 minutos. O próprio sistema registra a última execução e só roda as tarefas horárias e diárias quando necessário.
</div>

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

<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-terminal mr-2"></i>Agendamento no servidor</h5>
    </div>
    <div class="card-body">
        <p>Adicione a linha abaixo ao crontab do usuário que executa o PHP no servidor:</p>
        <pre class="bg-light p-3 mb-3"><code><?php echo htmlspecialchars($cronCommand); ?></code></pre>
        <a class="btn btn-outline-primary" href="?page=docs&amp;doc=TAREFAS_AGENDADAS.md">
            <i class="fas fa-book mr-1"></i>Ver documentação completa
        </a>
        <button type="button" class="btn btn-outline-dark ml-2" data-toggle="modal" data-target="#modalLogsCron">
            <i class="fas fa-list-alt mr-1"></i>Ver logs de execução
        </button>
    </div>
</div>

<div class="modal fade" id="modalLogsCron" tabindex="-1" role="dialog" aria-labelledby="tituloLogsCron" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="tituloLogsCron"><i class="fas fa-list-alt mr-2"></i>Logs de execução do cron</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Exibindo as últimas <?php echo count($cronLogLines); ?> linhas de <code><?php echo htmlspecialchars(basename($cronLogFile)); ?></code>.</p>
                <?php if (empty($cronLogLines)): ?>
                    <div class="alert alert-secondary mb-0">Ainda não há registros. Confirme que o cron foi configurado e executado.</div>
                <?php else: ?>
                    <pre class="bg-dark text-light p-3 mb-0" style="max-height:55vh;overflow:auto;white-space:pre-wrap;word-break:break-word"><code><?php echo htmlspecialchars(implode("\n", $cronLogLines), ENT_QUOTES, 'UTF-8'); ?></code></pre>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" onclick="window.location.reload()"><i class="fas fa-sync-alt mr-1"></i>Atualizar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

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