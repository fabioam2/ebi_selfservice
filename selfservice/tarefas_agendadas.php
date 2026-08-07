<?php
/**
 * Executor das tarefas de ciclo de vida.
 * Configure o cron para chamá-lo a cada 15 minutos; o estado no SQLite decide
 * quando as rotinas horárias e diárias realmente devem rodar.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script só pode ser executado pela linha de comando.' . PHP_EOL);
}

// Em CLI os erros SEMPRE aparecem, independente do DEBUG_MODE do painel (que
// desliga display_errors em inc/paths.php). Sem isto, uma falha fatal — extensão
// ausente, permissão negada, .env ilegível — encerraria o script em silêncio
// absoluto, sem nada na tela nem no log.
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/../.env')) {
        Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
    }
}

require_once __DIR__ . '/inc/paths.php';

// paths.php desliga display_errors quando DEBUG_MODE está off. Reafirma aqui,
// antes dos demais require, para que uma falha em qualquer um deles apareça.
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

require_once __DIR__ . '/inc/db_manager.php';
require_once __DIR__ . '/inc/instance_lifecycle.php';
require_once __DIR__ . '/inc/email_manager.php';

$force = in_array('--force', $argv, true);

// --status: mostra o estado atual sem executar nenhuma tarefa. Serve para
// confirmar rapidamente que o agendamento está funcionando.
if (in_array('--status', $argv, true)) {
    $logFile = DATA_PATH . '/tarefas_agendadas.log';
    $agora = time();

    fwrite(STDOUT, "Estado das tarefas agendadas\n");
    fwrite(STDOUT, str_repeat('-', 60) . "\n");
    fwrite(STDOUT, 'Data/hora atual : ' . date('Y-m-d H:i:s') . "\n");
    fwrite(STDOUT, 'Arquivo de log  : ' . $logFile . "\n");

    if (is_file($logFile)) {
        $idade = $agora - (int)@filemtime($logFile);
        fwrite(STDOUT, 'Última escrita  : ' . date('Y-m-d H:i:s', (int)@filemtime($logFile))
            . ' (' . floor($idade / 60) . " min atrás)\n");
        fwrite(STDOUT, 'Tamanho         : ' . number_format((float)@filesize($logFile)) . " bytes\n");
    } else {
        fwrite(STDOUT, "Última escrita  : arquivo ainda não existe\n");
    }

    fwrite(STDOUT, "\nTarefas registradas no banco:\n");
    $tarefas = lifecycle_listar_tarefas();
    if (empty($tarefas)) {
        fwrite(STDOUT, "  (nenhuma execução registrada até agora)\n");
    } else {
        foreach ($tarefas as $t) {
            $ts = strtotime((string)$t['last_run_at']);
            $min = $ts !== false ? floor(($agora - $ts) / 60) : '?';
            fwrite(STDOUT, sprintf(
                "  %-26s %s (%s min atrás)  status=%s  %s\n",
                $t['task_name'],
                (string)$t['last_run_at'],
                (string)$min,
                (string)$t['last_status'],
                (string)$t['details']
            ));
        }
    }

    $lockFile = DATA_PATH . '/tarefas_agendadas.lock';
    fwrite(STDOUT, "\nLock: " . (is_file($lockFile) ? 'presente (normal entre execuções)' : 'ausente') . "\n");
    fwrite(STDOUT, "\nÚltimas linhas do log:\n");
    $linhas = lifecycle_ler_log($logFile, 10);
    fwrite(STDOUT, empty($linhas) ? "  (log vazio)\n" : '  ' . implode("\n  ", $linhas) . "\n");
    exit(0);
}

$lockFile = DATA_PATH . '/tarefas_agendadas.lock';
$lock = fopen($lockFile, 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . "] outra execução ainda está ativa; encerrando.\n");
    exit(0);
}
@chmod($lockFile, 0600);

function tarefa_log(string $message): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    fwrite(STDOUT, $line);
    @file_put_contents(DATA_PATH . '/tarefas_agendadas.log', $line, FILE_APPEND | LOCK_EX);
}

function tarefa_limpar_dados_sensiveis(): array {
    $hours = lifecycle_config_int('SENSITIVE_DATA_RETENTION_HOURS', 24, 1, 24 * 365);
    $summary = ['instancias' => 0, 'cadastros' => 0, 'saidas' => 0, 'erros' => 0];

    foreach (lifecycle_listar_usuarios_ativos() as $usuario) {
        try {
            $result = lifecycle_limpar_dados_sensiveis((string)$usuario['user_id'], $hours);
            if (!empty($result['limpa'])) {
                $summary['instancias']++;
                $summary['cadastros'] += (int)($result['cadastros'] ?? 0);
                $summary['saidas'] += (int)($result['saidas'] ?? 0);
            }
        } catch (Throwable $error) {
            $summary['erros']++;
            lifecycle_registrar_log('ERRO_LIMPEZA_SENSIVEL', (string)$usuario['user_id'], $error->getMessage());
        }
    }

    return $summary;
}

function tarefa_ciclo_inatividade(): array {
    $warningDays = lifecycle_config_int('INACTIVITY_WARNING_DAYS', 30, 1, 3650);
    $graceDays = lifecycle_config_int('INACTIVITY_GRACE_DAYS', 30, 1, 3650);
    $reminderDays = lifecycle_config_int('INACTIVITY_REMINDER_DAYS', 7, 1, 365);
    $now = time();
    $limit = $now - $warningDays * 86400;
    $summary = ['avisos' => 0, 'quarentenas' => 0, 'expurgadas' => 0, 'erros' => 0];

    $purge = lifecycle_expurgar_quarentenas_vencidas();
    $summary['expurgadas'] += (int)($purge['apagadas'] ?? 0);
    $summary['erros'] += count($purge['erros'] ?? []);

    foreach (lifecycle_listar_usuarios_ativos() as $usuario) {
        $userId = (string)$usuario['user_id'];
        $lastAccess = lifecycle_ultimo_acesso($userId);
        if ($lastAccess === null || $lastAccess > $limit) {
            lifecycle_limpar_inatividade($userId);
            continue;
        }

        $notice = lifecycle_obter_inatividade($userId);
        try {
            if ($notice === null) {
                $firstNotice = date('Y-m-d H:i:s', $now);
                $quarantineAfter = date('Y-m-d H:i:s', $now + $graceDays * 86400);
                enviarEmailInatividadeInstancia(
                    (string)$usuario['email'],
                    (string)$usuario['nome'],
                    lifecycle_instance_url($userId),
                    $quarantineAfter
                );
                lifecycle_salvar_inatividade($userId, $firstNotice, $firstNotice, $quarantineAfter);
                lifecycle_registrar_log('AVISO_INATIVIDADE', $userId, 'quarentena=' . $quarantineAfter);
                $summary['avisos']++;
                continue;
            }

            if (strtotime((string)$notice['quarantine_after']) <= $now) {
                $result = lifecycle_colocar_em_quarentena($userId, 'inactivity');
                if (!empty($result['sucesso'])) {
                    enviarEmailQuarentenaInstancia(
                        (string)$usuario['email'],
                        (string)$usuario['nome'],
                        (string)$result['recuperacao_url'],
                        (string)$result['expira_em']
                    );
                    lifecycle_limpar_inatividade($userId);
                    $summary['quarentenas']++;
                } else {
                    $summary['erros']++;
                    lifecycle_registrar_log('ERRO_QUARENTENA_INATIVIDADE', $userId, (string)($result['erro'] ?? 'erro desconhecido'));
                }
                continue;
            }

            $lastNotice = strtotime((string)$notice['last_notified_at']);
            if ($lastNotice === false || $lastNotice <= $now - $reminderDays * 86400) {
                enviarEmailInatividadeInstancia(
                    (string)$usuario['email'],
                    (string)$usuario['nome'],
                    lifecycle_instance_url($userId),
                    (string)$notice['quarantine_after']
                );
                lifecycle_salvar_inatividade(
                    $userId,
                    (string)$notice['first_notified_at'],
                    date('Y-m-d H:i:s', $now),
                    (string)$notice['quarantine_after']
                );
                lifecycle_registrar_log('LEMBRETE_INATIVIDADE', $userId, 'quarentena=' . $notice['quarantine_after']);
                $summary['avisos']++;
            }
        } catch (Throwable $error) {
            $summary['erros']++;
            lifecycle_registrar_log('ERRO_INATIVIDADE', $userId, $error->getMessage());
        }
    }

    return $summary;
}

try {
    if (lifecycle_tarefa_deve_rodar('sensitive_data_cleanup', 3600, $force)) {
        $result = tarefa_limpar_dados_sensiveis();
        $details = 'instancias=' . $result['instancias'] . '|cadastros=' . $result['cadastros']
            . '|saidas=' . $result['saidas'] . '|erros=' . $result['erros'];
        lifecycle_registrar_tarefa('sensitive_data_cleanup', $result['erros'] === 0 ? 'ok' : 'warning', $details);
        tarefa_log('limpeza sensivel: ' . $details);
    } else {
        tarefa_log('limpeza sensivel: ainda não é hora de executar.');
    }

    if (lifecycle_tarefa_deve_rodar('instance_lifecycle_daily', 86400, $force)) {
        $result = tarefa_ciclo_inatividade();
        $details = 'avisos=' . $result['avisos'] . '|quarentenas=' . $result['quarentenas']
            . '|expurgadas=' . $result['expurgadas'] . '|erros=' . $result['erros'];
        lifecycle_registrar_tarefa('instance_lifecycle_daily', $result['erros'] === 0 ? 'ok' : 'warning', $details);
        tarefa_log('ciclo diario: ' . $details);
    } else {
        tarefa_log('ciclo diario: ainda não é hora de executar.');
    }
} catch (Throwable $error) {
    tarefa_log('falha não tratada: ' . $error->getMessage());
    exit(1);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}