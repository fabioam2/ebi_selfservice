<?php
/**
 * Gerenciador do banco de dados central (SQLite: selfservice/data/ebi.db).
 * Substitui selfservice_users.txt e fornece estatísticas agregadas de todas as instâncias.
 */

/**
 * Retorna (ou inicializa) a conexão PDO com o banco central.
 * Aceita um caminho explícito para uso em contexto de instância (stats push).
 */
function central_db(?string $overridePath = null): PDO {
    static $instances = [];

    $path = $overridePath
        ?? (defined('DATA_PATH') ? DATA_PATH . '/ebi.db' : __DIR__ . '/../data/ebi.db');

    if (!isset($instances[$path])) {
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA synchronous=NORMAL; PRAGMA foreign_keys=ON;');
        _central_db_init($pdo);
        if (file_exists($path)) {
            @chmod($path, 0600);
        }
        $instances[$path] = $pdo;
    }

    return $instances[$path];
}

function _central_db_init(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ss_users (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id     TEXT UNIQUE NOT NULL,
            email       TEXT NOT NULL,
            nome        TEXT NOT NULL,
            cidade      TEXT NOT NULL DEFAULT '',
            comum       TEXT NOT NULL DEFAULT '',
            senha_hash  TEXT NOT NULL,
            created_at  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%d %H:%M:%S','now','localtime')),
            last_access TEXT,
            active      INTEGER NOT NULL DEFAULT 1
        );

        CREATE INDEX IF NOT EXISTS idx_ss_users_email   ON ss_users(email);
        CREATE INDEX IF NOT EXISTS idx_ss_users_user_id ON ss_users(user_id);

        CREATE TABLE IF NOT EXISTS admin_daily_stats (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id          TEXT NOT NULL,
            date             TEXT NOT NULL,
            cidade           TEXT NOT NULL DEFAULT '',
            comum            TEXT NOT NULL DEFAULT '',
            total_cadastros  INTEGER NOT NULL DEFAULT 0,
            total_impressoes INTEGER NOT NULL DEFAULT 0,
            total_saidas     INTEGER NOT NULL DEFAULT 0,
            age_0_3          INTEGER NOT NULL DEFAULT 0,
            age_4_7          INTEGER NOT NULL DEFAULT 0,
            age_8_11         INTEGER NOT NULL DEFAULT 0,
            age_12_14        INTEGER NOT NULL DEFAULT 0,
            age_15_17        INTEGER NOT NULL DEFAULT 0,
            total_meninos    INTEGER NOT NULL DEFAULT 0,
            total_meninas    INTEGER NOT NULL DEFAULT 0,
            portaria_data    TEXT NOT NULL DEFAULT '{}',
            comum_data       TEXT NOT NULL DEFAULT '{}',
            updated_at       TEXT,
            UNIQUE(user_id, date)
        );

        CREATE INDEX IF NOT EXISTS idx_admin_stats_date ON admin_daily_stats(date);
        CREATE INDEX IF NOT EXISTS idx_admin_stats_user ON admin_daily_stats(user_id);

        CREATE TABLE IF NOT EXISTS qr_daily_stats (
            source          TEXT NOT NULL,
            date            TEXT NOT NULL,
            dimension       TEXT NOT NULL,
            dimension_value TEXT NOT NULL,
            total           INTEGER NOT NULL DEFAULT 0,
            updated_at      TEXT,
            PRIMARY KEY (source, date, dimension, dimension_value)
        );

        CREATE INDEX IF NOT EXISTS idx_qr_daily_stats_source_date
            ON qr_daily_stats(source, date);
        CREATE INDEX IF NOT EXISTS idx_qr_daily_stats_dimension
            ON qr_daily_stats(source, dimension, date);

        CREATE TABLE IF NOT EXISTS instance_quarantine (
            user_id        TEXT PRIMARY KEY,
            reason         TEXT NOT NULL DEFAULT 'user_request',
            requested_at   TEXT NOT NULL,
            delete_after   TEXT NOT NULL,
            recovery_until TEXT NOT NULL,
            status         TEXT NOT NULL DEFAULT 'quarantined',
            recovered_at   TEXT,
            finalized_at   TEXT
        );

        CREATE INDEX IF NOT EXISTS idx_instance_quarantine_status_date
            ON instance_quarantine(status, delete_after);

        CREATE TABLE IF NOT EXISTS instance_action_tokens (
            token_hash TEXT PRIMARY KEY,
            user_id    TEXT NOT NULL,
            purpose    TEXT NOT NULL,
            expires_at TEXT NOT NULL,
            consumed_at TEXT,
            created_at TEXT NOT NULL
        );

        CREATE INDEX IF NOT EXISTS idx_instance_action_tokens_lookup
            ON instance_action_tokens(user_id, purpose, expires_at);

        CREATE TABLE IF NOT EXISTS instance_inactivity_notices (
            user_id           TEXT PRIMARY KEY,
            first_notified_at TEXT NOT NULL,
            last_notified_at  TEXT NOT NULL,
            quarantine_after  TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS scheduled_task_runs (
            task_name   TEXT PRIMARY KEY,
            last_run_at TEXT NOT NULL,
            last_status TEXT NOT NULL DEFAULT 'ok',
            details     TEXT NOT NULL DEFAULT ''
        );
    ");

    $statsColumns = array_column($pdo->query('PRAGMA table_info(admin_daily_stats)')->fetchAll(), 'name');
    foreach (['total_meninos', 'total_meninas'] as $column) {
        if (!in_array($column, $statsColumns, true)) {
            $pdo->exec("ALTER TABLE admin_daily_stats ADD COLUMN {$column} INTEGER NOT NULL DEFAULT 0");
        }
    }
}

// ── ss_users ────────────────────────────────────────────────────────────────

function db_buscar_usuario_por_email(string $email): ?array {
    $stmt = central_db()->prepare('SELECT * FROM ss_users WHERE lower(email) = lower(?) ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function db_listar_usuarios_por_email(string $email): array {
    $stmt = central_db()->prepare('SELECT * FROM ss_users WHERE lower(email) = lower(?) ORDER BY created_at DESC');
    $stmt->execute([$email]);
    return $stmt->fetchAll() ?: [];
}

function db_buscar_usuario_por_id(string $user_id): ?array {
    $stmt = central_db()->prepare('SELECT * FROM ss_users WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    return $stmt->fetch() ?: null;
}

function db_inserir_usuario(
    string $user_id,
    string $email,
    string $nome,
    string $cidade,
    string $comum,
    string $senha_hash
): bool {
    try {
        $stmt = central_db()->prepare(
            'INSERT INTO ss_users (user_id, email, nome, cidade, comum, senha_hash)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        return $stmt->execute([$user_id, $email, $nome, $cidade, $comum, $senha_hash]);
    } catch (PDOException $e) {
        error_log('[EBI db_manager] inserir_usuario: ' . $e->getMessage());
        return false;
    }
}

function db_listar_usuarios(): array {
    return central_db()->query('SELECT * FROM ss_users ORDER BY created_at DESC')->fetchAll();
}

function db_remover_usuario(string $user_id): bool {
    $stmt = central_db()->prepare('DELETE FROM ss_users WHERE user_id = ?');
    return $stmt->execute([$user_id]);
}

function db_atualizar_last_access(string $user_id): void {
    $stmt = central_db()->prepare(
        "UPDATE ss_users SET last_access = strftime('%Y-%m-%d %H:%M:%S','now','localtime') WHERE user_id = ?"
    );
    $stmt->execute([$user_id]);
}

// ── admin_daily_stats ────────────────────────────────────────────────────────

/**
 * Incrementa (ou cria) o registro de stats do dia para uma instância.
 * $delta: ['cadastros'=>N, 'impressoes'=>N, 'saidas'=>N, 'age_*'=>N, 'total_meninos'=>N, 'total_meninas'=>N,
 *          'portaria_counts'=>['A'=>N,...], 'comum_counts'=>['Bonfim'=>N,...]]
 */
function db_registrar_admin_stat(
    string $user_id,
    string $cidade,
    string $comum,
    array  $delta,
    ?string $overridePath = null,
    ?string $date = null
): void {
    try {
        $today = $date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            ? $date
            : date('Y-m-d');
        $now   = date('Y-m-d H:i:s');
        $pdo   = central_db($overridePath);

        $stmt = $pdo->prepare('SELECT * FROM admin_daily_stats WHERE user_id = ? AND date = ?');
        $stmt->execute([$user_id, $today]);
        $existing = $stmt->fetch();

        $portariaData = $existing ? (json_decode($existing['portaria_data'], true) ?: []) : [];
        $comumData    = $existing ? (json_decode($existing['comum_data'],    true) ?: []) : [];

        foreach (($delta['portaria_counts'] ?? []) as $p => $cnt) {
            $portariaData[$p] = ($portariaData[$p] ?? 0) + (int)$cnt;
        }
        foreach (($delta['comum_counts'] ?? []) as $c => $cnt) {
            $comumData[$c] = ($comumData[$c] ?? 0) + (int)$cnt;
        }

        $pj = json_encode($portariaData, JSON_UNESCAPED_UNICODE);
        $cj = json_encode($comumData,    JSON_UNESCAPED_UNICODE);

        if ($existing) {
            $upd = $pdo->prepare(
                'UPDATE admin_daily_stats SET
                    total_cadastros  = total_cadastros  + ?,
                    total_impressoes = total_impressoes + ?,
                    total_saidas     = total_saidas     + ?,
                    age_0_3  = age_0_3  + ?,
                    age_4_7  = age_4_7  + ?,
                    age_8_11 = age_8_11 + ?,
                    age_12_14= age_12_14+ ?,
                    age_15_17= age_15_17+ ?,
                    total_meninos = total_meninos + ?,
                    total_meninas = total_meninas + ?,
                    portaria_data = ?,
                    comum_data    = ?,
                    updated_at    = ?
                 WHERE user_id = ? AND date = ?'
            );
            $upd->execute([
                $delta['cadastros']  ?? 0, $delta['impressoes'] ?? 0, $delta['saidas'] ?? 0,
                $delta['age_0_3']    ?? 0, $delta['age_4_7']    ?? 0, $delta['age_8_11'] ?? 0,
                $delta['age_12_14']  ?? 0, $delta['age_15_17']  ?? 0,
                $delta['total_meninos'] ?? 0, $delta['total_meninas'] ?? 0,
                $pj, $cj, $now, $user_id, $today,
            ]);
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO admin_daily_stats
                    (user_id, date, cidade, comum,
                     total_cadastros, total_impressoes, total_saidas,
                     age_0_3, age_4_7, age_8_11, age_12_14, age_15_17,
                     total_meninos, total_meninas,
                     portaria_data, comum_data, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $ins->execute([
                $user_id, $today, $cidade, $comum,
                $delta['cadastros']  ?? 0, $delta['impressoes'] ?? 0, $delta['saidas'] ?? 0,
                $delta['age_0_3']    ?? 0, $delta['age_4_7']    ?? 0, $delta['age_8_11'] ?? 0,
                $delta['age_12_14']  ?? 0, $delta['age_15_17']  ?? 0,
                $delta['total_meninos'] ?? 0, $delta['total_meninas'] ?? 0,
                $pj, $cj, $now,
            ]);
        }
    } catch (PDOException $e) {
        error_log('[EBI Stats] admin stat error: ' . $e->getMessage());
    }
}

// ── Estatísticas dos geradores públicos de QR ──────────────────────────────

function db_qr_stats_normalizar_texto(mixed $value, string $fallback = 'Não informado'): string {
    $text = trim((string)$value);
    $text = preg_replace('/[\p{C}]/u', '', $text) ?? '';
    $text = preg_replace('/\s+/u', ' ', $text) ?? '';

    if ($text === '') {
        return $fallback;
    }

    return function_exists('mb_substr') ? mb_substr($text, 0, 120) : substr($text, 0, 120);
}

/**
 * Registra uma geração de QR somente como contagens agregadas.
 * Nenhum nome, telefone ou conteúdo de QR é aceito ou armazenado.
 */
function db_registrar_qr_stat(string $source, array $event, ?string $overridePath = null, ?string $date = null): bool {
    $allowedSources = ['infantil', 'reuniao'];
    if (!in_array($source, $allowedSources, true)) {
        return false;
    }

    $today = $date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
        ? $date
        : date('Y-m-d');
    $increments = [
        'qrcode\0total' => ['qrcode', 'total', 1],
        'comum\0' . db_qr_stats_normalizar_texto($event['comum'] ?? '') => ['comum', db_qr_stats_normalizar_texto($event['comum'] ?? ''), 1],
        'cidade\0' . db_qr_stats_normalizar_texto($event['cidade'] ?? '') => ['cidade', db_qr_stats_normalizar_texto($event['cidade'] ?? ''), 1],
    ];

    if ($source === 'reuniao') {
        $allowedFunctions = [
            'Coordenadora',
            'Colaboradora',
            'Ancião',
            'Cooperador do Ofício',
            'Cooperador de Jovens',
            'Diácono',
            'Adm',
            'Outros',
        ];
        $function = (string)($event['funcao'] ?? '');
        if (!in_array($function, $allowedFunctions, true)) {
            return false;
        }
        $increments['funcao\0' . $function] = ['funcao', $function, 1];
    } else {
        $children = $event['criancas'] ?? null;
        if (!is_array($children) || count($children) < 1 || count($children) > 5) {
            return false;
        }

        $increments['crianca\0total'] = ['crianca', 'total', count($children)];
        foreach ($children as $child) {
            if (!is_array($child)) {
                return false;
            }

            $age = filter_var($child['idade'] ?? null, FILTER_VALIDATE_INT);
            $sex = $child['sexo'] ?? '';
            if ($age === false || $age < 3 || $age > 11 || !in_array($sex, ['M', 'F'], true)) {
                return false;
            }

            $ageKey = 'idade\0' . $age;
            $sexKey = 'sexo\0' . $sex;
            if (!isset($increments[$ageKey])) {
                $increments[$ageKey] = ['idade', (string)$age, 0];
            }
            if (!isset($increments[$sexKey])) {
                $increments[$sexKey] = ['sexo', $sex, 0];
            }
            $increments[$ageKey][2]++;
            $increments[$sexKey][2]++;
        }
    }

    try {
        $pdo = central_db($overridePath);
        $statement = $pdo->prepare(
            'INSERT INTO qr_daily_stats (source, date, dimension, dimension_value, total, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)
             ON CONFLICT(source, date, dimension, dimension_value) DO UPDATE SET
                total = total + excluded.total,
                updated_at = excluded.updated_at'
        );

        $pdo->beginTransaction();
        foreach ($increments as [$dimension, $value, $count]) {
            $statement->execute([$source, $today, $dimension, $value, $count, date('Y-m-d H:i:s')]);
        }
        $pdo->commit();
        return true;
    } catch (Throwable $error) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[EBI QR Stats] registrar: ' . $error->getMessage());
        return false;
    }
}

function db_qr_stats_totais(string $source, string $desde): array {
    $stmt = central_db()->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN dimension = 'qrcode' THEN total ELSE 0 END), 0) AS qrcodes,
            COALESCE(SUM(CASE WHEN dimension = 'crianca' THEN total ELSE 0 END), 0) AS criancas
         FROM qr_daily_stats
         WHERE source = ? AND date >= ?"
    );
    $stmt->execute([$source, $desde]);
    return $stmt->fetch() ?: ['qrcodes' => 0, 'criancas' => 0];
}

function db_qr_stats_por_dia(string $source, string $desde): array {
    $stmt = central_db()->prepare(
        "SELECT date,
                COALESCE(SUM(CASE WHEN dimension = 'qrcode' THEN total ELSE 0 END), 0) AS qrcodes,
                COALESCE(SUM(CASE WHEN dimension = 'crianca' THEN total ELSE 0 END), 0) AS criancas
         FROM qr_daily_stats
         WHERE source = ? AND date >= ?
         GROUP BY date
         ORDER BY date ASC"
    );
    $stmt->execute([$source, $desde]);
    return $stmt->fetchAll();
}

function db_qr_stats_por_dimensao(string $source, string $desde, string $dimension): array {
    $allowedDimensions = ['comum', 'cidade', 'funcao', 'sexo', 'idade'];
    if (!in_array($dimension, $allowedDimensions, true)) {
        return [];
    }

    $orderBy = $dimension === 'idade'
        ? 'CAST(dimension_value AS INTEGER) ASC'
        : 'total DESC, dimension_value ASC';
    $stmt = central_db()->prepare(
        "SELECT dimension_value, SUM(total) AS total, COUNT(DISTINCT date) AS dias_ativos
         FROM qr_daily_stats
         WHERE source = ? AND date >= ? AND dimension = ?
         GROUP BY dimension_value
         ORDER BY {$orderBy}"
    );
    $stmt->execute([$source, $desde, $dimension]);
    return $stmt->fetchAll();
}

// ── Queries para o painel admin ──────────────────────────────────────────────

function db_stats_por_dia(string $desde): array {
    $stmt = central_db()->prepare(
        'SELECT date,
                SUM(total_cadastros)  as total_cadastros,
                SUM(total_impressoes) as total_impressoes,
                SUM(total_saidas)     as total_saidas,
                SUM(age_0_3)  as age_0_3,  SUM(age_4_7)   as age_4_7,
                SUM(age_8_11) as age_8_11, SUM(age_12_14) as age_12_14,
                SUM(age_15_17) as age_15_17,
                SUM(total_meninos) as total_meninos,
                SUM(total_meninas) as total_meninas
         FROM admin_daily_stats
         WHERE date >= ?
         GROUP BY date ORDER BY date ASC'
    );
    $stmt->execute([$desde]);
    return $stmt->fetchAll();
}

function db_stats_por_comum(string $desde): array {
    $stmt = central_db()->prepare(
        'SELECT comum,
                SUM(total_cadastros)  as total_cadastros,
                SUM(total_impressoes) as total_impressoes,
                SUM(total_saidas)     as total_saidas,
                COUNT(DISTINCT date)  as dias_ativos
         FROM admin_daily_stats
         WHERE date >= ? AND comum != \'\'
         GROUP BY comum
         ORDER BY total_cadastros DESC'
    );
    $stmt->execute([$desde]);
    return $stmt->fetchAll();
}

function db_stats_por_instancia(string $desde): array {
    $stmt = central_db()->prepare(
        'SELECT user_id,
                comum,
                cidade,
                SUM(total_cadastros)  as total_cadastros,
                SUM(total_impressoes) as total_impressoes,
                SUM(total_saidas)     as total_saidas
         FROM admin_daily_stats
         WHERE date >= ?
         GROUP BY user_id
         ORDER BY total_cadastros DESC'
    );
    $stmt->execute([$desde]);
    return $stmt->fetchAll();
}

function db_stats_por_cidade(string $desde): array {
    $stmt = central_db()->prepare(
        'SELECT cidade,
                SUM(total_cadastros)  as total_cadastros,
                COUNT(DISTINCT user_id) as instancias
         FROM admin_daily_stats
         WHERE date >= ? AND cidade != \'\'
         GROUP BY cidade
         ORDER BY total_cadastros DESC'
    );
    $stmt->execute([$desde]);
    return $stmt->fetchAll();
}

function db_stats_totais_geral(): array {
    $row = central_db()->query(
        'SELECT
            COALESCE(SUM(total_cadastros), 0)  as total_cadastros,
            COALESCE(SUM(total_impressoes),0)  as total_impressoes,
            COALESCE(SUM(total_saidas),    0)  as total_saidas,
            COALESCE(SUM(total_meninos),   0)  as total_meninos,
            COALESCE(SUM(total_meninas),   0)  as total_meninas,
            COUNT(DISTINCT user_id)            as instancias_com_dados
         FROM admin_daily_stats'
    )->fetch();
    return $row ?: [];
}

function db_stats_totais_hoje(): array {
    $today = date('Y-m-d');
    $stmt  = central_db()->prepare(
        'SELECT
            COALESCE(SUM(total_cadastros), 0)  as cadastros_hoje,
            COALESCE(SUM(total_impressoes),0)  as impressoes_hoje,
            COALESCE(SUM(total_saidas),    0)  as saidas_hoje,
            COALESCE(SUM(age_0_3),0)  as age_0_3,
            COALESCE(SUM(age_4_7),0)  as age_4_7,
            COALESCE(SUM(age_8_11),0) as age_8_11,
            COALESCE(SUM(age_12_14),0)as age_12_14,
            COALESCE(SUM(age_15_17),0)as age_15_17,
            COALESCE(SUM(total_meninos),0) as total_meninos,
            COALESCE(SUM(total_meninas),0) as total_meninas
         FROM admin_daily_stats WHERE date = ?'
    );
    $stmt->execute([$today]);
    return $stmt->fetch() ?: [];
}

function db_stats_mensal(int $meses = 12): array {
    $stmt = central_db()->prepare(
        "SELECT strftime('%Y-%m', date) as mes,
                SUM(total_cadastros)    as total_cadastros,
                SUM(total_impressoes)   as total_impressoes,
                SUM(total_saidas)       as total_saidas
         FROM admin_daily_stats
         WHERE date >= date('now', '-' || ? || ' months')
         GROUP BY mes ORDER BY mes ASC"
    );
    $stmt->execute([$meses]);
    return $stmt->fetchAll();
}
