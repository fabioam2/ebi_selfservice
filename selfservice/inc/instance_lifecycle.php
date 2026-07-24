<?php
/**
 * Ciclo de vida de instâncias: tokens de ação, quarentena, restauração e limpeza.
 *
 * Os registros de estatística permanecem agregados no SQLite; nenhuma função
 * deste arquivo grava nomes de crianças em tabelas de controle.
 */

require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/db_manager.php';

function lifecycle_validar_user_id(string $userId): bool {
    return $userId !== ''
        && preg_match('/^[A-Za-z0-9_.]+$/', $userId) === 1
        && strpos($userId, '..') === false;
}

function lifecycle_config_int(string $key, int $default, int $min, int $max): int {
    $value = filter_var($_ENV[$key] ?? $default, FILTER_VALIDATE_INT);
    if ($value === false) return $default;
    return max($min, min($max, (int)$value));
}

function lifecycle_retencao_quarentena_dias(): int {
    return lifecycle_config_int('QUARANTINE_RETENTION_DAYS', 7, 1, 365);
}

function lifecycle_base_url(): string {
    if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
        $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $rootPath = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/selfservice/index.php'));
        $prefix = ($rootPath === '/' || $rootPath === '\\') ? '' : rtrim($rootPath, '/');
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $prefix;
    }

    return rtrim((string)($_ENV['BASE_URL'] ?? ''), '/');
}

function lifecycle_instance_container(string $userId): ?string {
    if (!lifecycle_validar_user_id($userId)) return null;

    $container = rtrim(INSTANCE_BASE_PATH, '/') . '/' . $userId;
    return is_dir($container) ? $container : null;
}

function lifecycle_instance_root(string $userId): ?string {
    $container = lifecycle_instance_container($userId);
    if ($container === null) return null;

    $candidates = [
        $container,
        $container . '/public_html/ebi',
    ];
    foreach ($candidates as $candidate) {
        if (is_file($candidate . '/config.ini')) return $candidate;
    }

    return null;
}

function lifecycle_instance_url(string $userId, ?string $baseUrl = null): string {
    if (!lifecycle_validar_user_id($userId)) return '';

    $baseUrl = rtrim($baseUrl ?? lifecycle_base_url(), '/');
    if ($baseUrl === '') return '';

    $root = lifecycle_instance_root($userId);
    $suffix = $root !== null && str_ends_with($root, '/public_html/ebi')
        ? '/public_html/ebi/index.php'
        : '/index.php';
    $publicBase = trim((string)($_ENV['INSTANCE_URL_PREFIX'] ?? 'ebi/i'), '/');

    return $baseUrl . '/' . $publicBase . '/' . rawurlencode($userId) . $suffix;
}

function lifecycle_criar_token_acao(string $userId, string $purpose, int $hours): string {
    if (!lifecycle_validar_user_id($userId) || !in_array($purpose, ['quarantine', 'recover'], true)) {
        throw new InvalidArgumentException('Dados inválidos para token de instância.');
    }

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $now = date('Y-m-d H:i:s');
    $expiresAt = date('Y-m-d H:i:s', time() + max(1, $hours) * 3600);

    $pdo = central_db();
    $pdo->prepare('DELETE FROM instance_action_tokens WHERE expires_at < ? OR consumed_at IS NOT NULL')
        ->execute([$now]);
    $pdo->prepare(
        'INSERT INTO instance_action_tokens (token_hash, user_id, purpose, expires_at, created_at)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([$hash, $userId, $purpose, $expiresAt, $now]);

    return $token;
}

function lifecycle_buscar_token_acao(string $token, string $purpose): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/', $token) || !in_array($purpose, ['quarantine', 'recover'], true)) {
        return null;
    }

    $statement = central_db()->prepare(
        'SELECT user_id, purpose, expires_at
         FROM instance_action_tokens
         WHERE token_hash = ? AND purpose = ? AND consumed_at IS NULL AND expires_at >= ?
         LIMIT 1'
    );
    $statement->execute([hash('sha256', $token), $purpose, date('Y-m-d H:i:s')]);
    return $statement->fetch() ?: null;
}

function lifecycle_consumir_token_acao(string $token, string $purpose): bool {
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return false;

    $statement = central_db()->prepare(
        'UPDATE instance_action_tokens
         SET consumed_at = ?
         WHERE token_hash = ? AND purpose = ? AND consumed_at IS NULL AND expires_at >= ?'
    );
    $statement->execute([
        date('Y-m-d H:i:s'),
        hash('sha256', $token),
        $purpose,
        date('Y-m-d H:i:s'),
    ]);

    return $statement->rowCount() === 1;
}

function lifecycle_link_acao(string $userId, string $purpose, ?string $baseUrl = null, ?int $hours = null): string {
    $baseUrl = rtrim($baseUrl ?? lifecycle_base_url(), '/');
    if ($baseUrl === '') return '';

    $duration = $hours ?? ($purpose === 'recover'
        ? lifecycle_retencao_quarentena_dias() * 24
        : lifecycle_config_int('INSTANCE_ACTION_TOKEN_HOURS', 168, 1, 24 * 90));
    $token = lifecycle_criar_token_acao($userId, $purpose, $duration);

    return $baseUrl . '/selfservice/gerenciar_instancia.php?acao=' . rawurlencode($purpose)
        . '&token=' . rawurlencode($token);
}

function lifecycle_preparar_diretorio_quarentena(): void {
    if (!is_dir(QUARANTINE_PATH) && !mkdir(QUARANTINE_PATH, 0700, true) && !is_dir(QUARANTINE_PATH)) {
        throw new RuntimeException('Não foi possível preparar o diretório de quarentena.');
    }
    @chmod(QUARANTINE_PATH, 0700);

    $htaccess = QUARANTINE_PATH . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Require all denied\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n", LOCK_EX);
        @chmod($htaccess, 0600);
    }
}

function lifecycle_registro_quarentena(string $userId): ?array {
    $statement = central_db()->prepare('SELECT * FROM instance_quarantine WHERE user_id = ? LIMIT 1');
    $statement->execute([$userId]);
    return $statement->fetch() ?: null;
}

function lifecycle_colocar_em_quarentena(string $userId, string $reason = 'user_request', ?string $baseUrl = null): array {
    if (!lifecycle_validar_user_id($userId)) {
        return ['sucesso' => false, 'erro' => 'Identificador de instância inválido.'];
    }

    $container = lifecycle_instance_container($userId);
    if ($container === null || lifecycle_instance_root($userId) === null) {
        return ['sucesso' => false, 'erro' => 'Instância ativa não encontrada.'];
    }

    $current = lifecycle_registro_quarentena($userId);
    if ($current !== null && ($current['status'] ?? '') === 'quarantined') {
        return ['sucesso' => false, 'erro' => 'A instância já está em quarentena.'];
    }

    try {
        lifecycle_preparar_diretorio_quarentena();
    } catch (Throwable $error) {
        return ['sucesso' => false, 'erro' => $error->getMessage()];
    }

    $destination = rtrim(QUARANTINE_PATH, '/') . '/' . $userId;
    if (file_exists($destination)) {
        return ['sucesso' => false, 'erro' => 'Já existe uma cópia da instância em quarentena.'];
    }

    $now = date('Y-m-d H:i:s');
    $expiresAt = date('Y-m-d H:i:s', time() + lifecycle_retencao_quarentena_dias() * 86400);
    $recoveryUrl = lifecycle_link_acao($userId, 'recover', $baseUrl, lifecycle_retencao_quarentena_dias() * 24);

    if (!@rename($container, $destination)) {
        return ['sucesso' => false, 'erro' => 'Não foi possível mover a instância para a quarentena.'];
    }

    $pdo = central_db();
    try {
        $pdo->beginTransaction();
        $pdo->prepare(
            'INSERT INTO instance_quarantine
                (user_id, reason, requested_at, delete_after, recovery_until, status, recovered_at, finalized_at)
             VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)
             ON CONFLICT(user_id) DO UPDATE SET
                reason = excluded.reason,
                requested_at = excluded.requested_at,
                delete_after = excluded.delete_after,
                recovery_until = excluded.recovery_until,
                status = excluded.status,
                recovered_at = NULL,
                finalized_at = NULL'
        )->execute([$userId, $reason, $now, $expiresAt, $expiresAt, 'quarantined']);
        $pdo->prepare('UPDATE ss_users SET active = 0 WHERE user_id = ?')->execute([$userId]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        @rename($destination, $container);
        return ['sucesso' => false, 'erro' => 'Falha ao registrar a quarentena: ' . $error->getMessage()];
    }

    lifecycle_registrar_log('QUARANTENA', $userId, $reason . '|exclusao=' . $expiresAt);
    return [
        'sucesso' => true,
        'recuperacao_url' => $recoveryUrl,
        'expira_em' => $expiresAt,
    ];
}

function lifecycle_restaurar_quarentena(string $userId): array {
    if (!lifecycle_validar_user_id($userId)) {
        return ['sucesso' => false, 'erro' => 'Identificador de instância inválido.'];
    }

    $record = lifecycle_registro_quarentena($userId);
    if ($record === null || ($record['status'] ?? '') !== 'quarantined') {
        return ['sucesso' => false, 'erro' => 'Não existe uma instância disponível para recuperação.'];
    }
    if (($record['recovery_until'] ?? '') < date('Y-m-d H:i:s')) {
        return ['sucesso' => false, 'erro' => 'O prazo para recuperar esta instância expirou.'];
    }

    $source = rtrim(QUARANTINE_PATH, '/') . '/' . $userId;
    $destination = rtrim(INSTANCE_BASE_PATH, '/') . '/' . $userId;
    if (!is_dir($source)) {
        return ['sucesso' => false, 'erro' => 'Os arquivos da instância em quarentena não foram encontrados.'];
    }
    if (file_exists($destination)) {
        return ['sucesso' => false, 'erro' => 'Já existe uma instância ativa com este identificador.'];
    }

    if (!@rename($source, $destination)) {
        return ['sucesso' => false, 'erro' => 'Não foi possível restaurar os arquivos da instância.'];
    }

    $pdo = central_db();
    try {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE instance_quarantine SET status = ?, recovered_at = ? WHERE user_id = ?')
            ->execute(['recovered', date('Y-m-d H:i:s'), $userId]);
        $pdo->prepare('UPDATE ss_users SET active = 1 WHERE user_id = ?')->execute([$userId]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        @rename($destination, $source);
        return ['sucesso' => false, 'erro' => 'Falha ao registrar a recuperação: ' . $error->getMessage()];
    }

    lifecycle_registrar_log('RECUPERADA', $userId, 'instancia restaurada');
    return ['sucesso' => true, 'link' => lifecycle_instance_url($userId)];
}

function lifecycle_remover_diretorio(string $directory): void {
    if (!is_dir($directory)) return;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($directory);
}

function lifecycle_expurgar_quarentenas_vencidas(): array {
    $pdo = central_db();
    $statement = $pdo->prepare(
        'SELECT user_id FROM instance_quarantine
         WHERE status = ? AND delete_after <= ?
         ORDER BY delete_after ASC'
    );
    $statement->execute(['quarantined', date('Y-m-d H:i:s')]);
    $userIds = $statement->fetchAll(PDO::FETCH_COLUMN);
    $result = ['apagadas' => 0, 'erros' => []];

    foreach ($userIds as $userId) {
        $directory = rtrim(QUARANTINE_PATH, '/') . '/' . $userId;
        try {
            lifecycle_remover_diretorio($directory);
            if (is_dir($directory)) {
                throw new RuntimeException('Não foi possível excluir todos os arquivos da quarentena.');
            }

            $pdo->beginTransaction();
            $pdo->prepare('UPDATE instance_quarantine SET status = ?, finalized_at = ? WHERE user_id = ?')
                ->execute(['deleted', date('Y-m-d H:i:s'), $userId]);
            $pdo->prepare('DELETE FROM instance_action_tokens WHERE user_id = ?')->execute([$userId]);
            $pdo->prepare('DELETE FROM instance_inactivity_notices WHERE user_id = ?')->execute([$userId]);
            $pdo->prepare('DELETE FROM ss_users WHERE user_id = ?')->execute([$userId]);
            $pdo->commit();
            lifecycle_registrar_log('EXPURGADA', $userId, 'prazo de quarentena encerrado');
            $result['apagadas']++;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $result['erros'][] = $userId . ': ' . $error->getMessage();
        }
    }

    return $result;
}

function lifecycle_ultimo_acesso(string $userId): ?int {
    $root = lifecycle_instance_root($userId);
    $container = lifecycle_instance_container($userId);
    if ($root === null || $container === null) return null;

    foreach ([$root . '/.lastaccess', $container . '/.lastaccess', $container . '/config/.lastaccess'] as $file) {
        if (is_file($file)) {
            $value = (int)trim((string)@file_get_contents($file));
            if ($value > 0) return $value;
        }
    }

    $config = $root . '/config.ini';
    return is_file($config) ? @filemtime($config) ?: null : null;
}

function lifecycle_limpar_dados_sensiveis(string $userId, int $hours): array {
    $root = lifecycle_instance_root($userId);
    if ($root === null) return ['limpa' => false, 'motivo' => 'instância não encontrada'];

    $database = $root . '/data/instance.db';
    if (!is_file($database)) return ['limpa' => false, 'motivo' => 'banco de dados não encontrado'];

    $pdo = new PDO('sqlite:' . $database, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $lastCreated = $pdo->query('SELECT MAX(created_at) FROM cadastros')->fetchColumn();
    if (!$lastCreated) return ['limpa' => false, 'motivo' => 'sem cadastros'];

    $timestamp = strtotime((string)$lastCreated);
    if ($timestamp === false || $timestamp > time() - max(1, $hours) * 3600) {
        return ['limpa' => false, 'motivo' => 'ainda dentro do prazo'];
    }

    $pdo->beginTransaction();
    try {
        $saidas = $pdo->exec('DELETE FROM saidas');
        $cadastros = $pdo->exec('DELETE FROM cadastros');
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }

    lifecycle_registrar_log('DADOS_SENSIVEIS_LIMPOS', $userId, 'cadastros=' . $cadastros . '|saidas=' . $saidas);
    return ['limpa' => true, 'cadastros' => $cadastros, 'saidas' => $saidas];
}

function lifecycle_listar_usuarios_ativos(): array {
    return central_db()->query('SELECT user_id, email, nome, cidade, comum FROM ss_users WHERE active = 1 ORDER BY user_id')
        ->fetchAll();
}

function lifecycle_obter_inatividade(string $userId): ?array {
    $statement = central_db()->prepare('SELECT * FROM instance_inactivity_notices WHERE user_id = ? LIMIT 1');
    $statement->execute([$userId]);
    return $statement->fetch() ?: null;
}

function lifecycle_salvar_inatividade(string $userId, string $firstNotice, string $lastNotice, string $quarantineAfter): void {
    central_db()->prepare(
        'INSERT INTO instance_inactivity_notices (user_id, first_notified_at, last_notified_at, quarantine_after)
         VALUES (?, ?, ?, ?)
         ON CONFLICT(user_id) DO UPDATE SET
            first_notified_at = excluded.first_notified_at,
            last_notified_at = excluded.last_notified_at,
            quarantine_after = excluded.quarantine_after'
    )->execute([$userId, $firstNotice, $lastNotice, $quarantineAfter]);
}

function lifecycle_limpar_inatividade(string $userId): void {
    central_db()->prepare('DELETE FROM instance_inactivity_notices WHERE user_id = ?')->execute([$userId]);
}

function lifecycle_tarefa_deve_rodar(string $taskName, int $intervalSeconds, bool $force = false): bool {
    if ($force) return true;

    $statement = central_db()->prepare('SELECT last_run_at FROM scheduled_task_runs WHERE task_name = ? LIMIT 1');
    $statement->execute([$taskName]);
    $lastRun = $statement->fetchColumn();
    if (!$lastRun) return true;

    $timestamp = strtotime((string)$lastRun);
    return $timestamp === false || $timestamp <= time() - max(1, $intervalSeconds);
}

function lifecycle_registrar_tarefa(string $taskName, string $status, string $details): void {
    central_db()->prepare(
        'INSERT INTO scheduled_task_runs (task_name, last_run_at, last_status, details)
         VALUES (?, ?, ?, ?)
         ON CONFLICT(task_name) DO UPDATE SET
            last_run_at = excluded.last_run_at,
            last_status = excluded.last_status,
            details = excluded.details'
    )->execute([$taskName, date('Y-m-d H:i:s'), $status, $details]);
}

function lifecycle_listar_tarefas(): array {
    return central_db()->query('SELECT task_name, last_run_at, last_status, details FROM scheduled_task_runs ORDER BY task_name')
        ->fetchAll();
}

function lifecycle_registrar_log(string $event, string $userId, string $details = ''): void {
    $line = date('Y-m-d H:i:s') . '|' . $event . '|' . $userId . '|' . $details . "\n";
    @file_put_contents(DATA_PATH . '/instance_lifecycle.log', $line, FILE_APPEND | LOCK_EX);
}