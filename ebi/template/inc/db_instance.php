<?php
/**
 * Gerenciador do banco de dados por instância (SQLite: data/instance.db).
 * Substitui cadastro_criancas.txt e saidas.log; adiciona stats diárias sem guardar nomes.
 *
 * Usa a constante DB_INSTANCE_PATH definida pelo bootstrap.
 * Acesso via singleton ebi_db().
 */

function ebi_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    if (!defined('DB_INSTANCE_PATH')) {
        throw new RuntimeException('DB_INSTANCE_PATH não definido. Inclua bootstrap.php antes de db_instance.php.');
    }

    $pdo = new PDO('sqlite:' . DB_INSTANCE_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA synchronous=NORMAL; PRAGMA foreign_keys=ON;');
    _ebi_db_init($pdo);

    return $pdo;
}

function _ebi_db_init(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cadastros (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            nome_crianca     TEXT    NOT NULL,
            nome_responsavel TEXT    NOT NULL,
            telefone         TEXT    NOT NULL DEFAULT '',
            idade            INTEGER NOT NULL DEFAULT 0,
            comum            TEXT    NOT NULL DEFAULT '',
            status_impresso  TEXT    NOT NULL DEFAULT 'N',
            portaria         TEXT    NOT NULL DEFAULT '',
            cod_resp         INTEGER NOT NULL DEFAULT 0,
            data_nascimento  TEXT    NOT NULL DEFAULT '',
            sexo             TEXT    NOT NULL DEFAULT '',
            created_at       TEXT    NOT NULL
                DEFAULT (strftime('%Y-%m-%d %H:%M:%S','now','localtime'))
        );

        CREATE INDEX IF NOT EXISTS idx_cadastros_cod_resp ON cadastros(cod_resp);
        CREATE INDEX IF NOT EXISTS idx_cadastros_date     ON cadastros(date(created_at));
        CREATE INDEX IF NOT EXISTS idx_cadastros_portaria ON cadastros(portaria);

        CREATE TABLE IF NOT EXISTS saidas (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            cod_resp         INTEGER NOT NULL,
            nome_responsavel TEXT    NOT NULL DEFAULT '',
            portaria         TEXT    NOT NULL DEFAULT '',
            registered_at    TEXT    NOT NULL
                DEFAULT (strftime('%Y-%m-%d %H:%M:%S','now','localtime'))
        );

        CREATE INDEX IF NOT EXISTS idx_saidas_cod_resp ON saidas(cod_resp);
        CREATE INDEX IF NOT EXISTS idx_saidas_date     ON saidas(date(registered_at));

        CREATE TABLE IF NOT EXISTS stats_daily (
            date             TEXT    PRIMARY KEY,
            total_cadastros  INTEGER NOT NULL DEFAULT 0,
            total_impressoes INTEGER NOT NULL DEFAULT 0,
            total_saidas     INTEGER NOT NULL DEFAULT 0,
            age_0_3          INTEGER NOT NULL DEFAULT 0,
            age_4_7          INTEGER NOT NULL DEFAULT 0,
            age_8_11         INTEGER NOT NULL DEFAULT 0,
            age_12_14        INTEGER NOT NULL DEFAULT 0,
            age_15_17        INTEGER NOT NULL DEFAULT 0,
            portaria_data    TEXT    NOT NULL DEFAULT '{}',
            comum_data       TEXT    NOT NULL DEFAULT '{}',
            updated_at       TEXT
        );
    ");

    // Migration: adicionar coluna data_nascimento em BDs existentes
    $cols = $pdo->query("PRAGMA table_info(cadastros)")->fetchAll();
    $hasDataNasc = false;
    foreach ($cols as $col) {
        if ($col['name'] === 'data_nascimento') { $hasDataNasc = true; break; }
    }
    if (!$hasDataNasc) {
        $pdo->exec("ALTER TABLE cadastros ADD COLUMN data_nascimento TEXT NOT NULL DEFAULT ''");
    }

    // Migration: adicionar coluna sexo em BDs existentes
    $hasSexo = false;
    foreach ($cols as $col) {
        if ($col['name'] === 'sexo') { $hasSexo = true; break; }
    }
    if (!$hasSexo) {
        $pdo->exec("ALTER TABLE cadastros ADD COLUMN sexo TEXT NOT NULL DEFAULT ''");
    }
}

// ── Cadastros ────────────────────────────────────────────────────────────────

function db_inserir_cadastro(
    string $nomeCrianca,
    string $nomeResponsavel,
    string $telefone,
    int    $idade,
    string $comum,
    string $portaria,
    int    $codResp,
    string $dataNascimento = '',
    string $sexo = ''
): int {
    $pdo  = ebi_db();
    $stmt = $pdo->prepare(
        'INSERT INTO cadastros
            (nome_crianca, nome_responsavel, telefone, idade, comum, portaria, cod_resp, data_nascimento, sexo)
         VALUES (?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([$nomeCrianca, $nomeResponsavel, $telefone, $idade, $comum, $portaria, $codResp, $dataNascimento, $sexo]);
    return (int)$pdo->lastInsertId();
}

function db_listar_cadastros(): array {
    $rows = ebi_db()->query(
        'SELECT id, nome_crianca, nome_responsavel, telefone, idade, comum,
                status_impresso, portaria, cod_resp, data_nascimento, sexo, created_at
         FROM cadastros ORDER BY id ASC'
    )->fetchAll();

    $result = [];
    foreach ($rows as $r) {
        $result[(int)$r['id']] = [
            'id'              => (int)$r['id'],
            'nomeCrianca'     => $r['nome_crianca'],
            'nomeResponsavel' => $r['nome_responsavel'],
            'telefone'        => $r['telefone'],
            'idade'           => (string)$r['idade'],
            'comum'           => $r['comum'],
            'statusImpresso'  => $r['status_impresso'],
            'portaria'        => strtoupper(trim($r['portaria'])),
            'cod_resp'        => (string)$r['cod_resp'],
            'dataNascimento'  => $r['data_nascimento'] ?? '',
            'sexo'            => $r['sexo'] ?? '',
            'created_at'      => $r['created_at'] ?? '',
        ];
    }
    return $result;
}

function db_total_cadastros(): int {
    return (int)ebi_db()->query('SELECT COUNT(*) FROM cadastros')->fetchColumn();
}

function db_proximo_cod_resp(): int {
    $max = ebi_db()->query('SELECT MAX(cod_resp) FROM cadastros')->fetchColumn();
    return ($max !== null && $max !== false) ? ((int)$max + 1) : 1;
}

function db_apagar_cadastro(int $id): bool {
    $stmt = ebi_db()->prepare('DELETE FROM cadastros WHERE id = ?');
    return $stmt->execute([$id]);
}

function db_marcar_impresso(array $ids): void {
    if (empty($ids)) return;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    ebi_db()->prepare(
        "UPDATE cadastros SET status_impresso = 'S' WHERE id IN ($placeholders)"
    )->execute(array_values($ids));
}

function db_zerar_cadastros(): void {
    $pdo = ebi_db();
    $pdo->exec('DELETE FROM cadastros');
    $pdo->exec('DELETE FROM sqlite_sequence WHERE name = \'cadastros\'');
}

/** Retorna cadastros de um cod_resp para o módulo de saída. */
function db_listar_por_cod_resp(int $codResp): array {
    $stmt = ebi_db()->prepare(
        'SELECT id, nome_crianca, nome_responsavel, idade FROM cadastros WHERE cod_resp = ? ORDER BY id ASC'
    );
    $stmt->execute([$codResp]);
    return $stmt->fetchAll();
}

// ── Backup (cópia do arquivo SQLite) ────────────────────────────────────────

function db_backup_create(): ?string {
    if (!defined('DB_INSTANCE_PATH')) return null;

    $base  = DB_INSTANCE_PATH;
    $maxBk = defined('MAX_BACKUPS') ? (int)MAX_BACKUPS : 10;

    // Rotate existing backups
    for ($i = $maxBk; $i >= 1; $i--) {
        $cur  = $base . '.bkp.' . $i;
        $next = $base . '.bkp.' . ($i + 1);
        if (file_exists($cur)) {
            if ($i == $maxBk) {
                @unlink($cur);
            } else {
                @rename($cur, $next);
            }
        }
    }

    $dest = $base . '.bkp.1';
    if (@copy($base, $dest)) {
        return basename($dest);
    }
    return null;
}

function db_backup_list(): array {
    if (!defined('DB_INSTANCE_PATH')) return [];

    $base  = DB_INSTANCE_PATH;
    $maxBk = defined('MAX_BACKUPS') ? (int)MAX_BACKUPS : 10;
    $list  = [];

    for ($i = 1; $i <= $maxBk; $i++) {
        $f = $base . '.bkp.' . $i;
        if (file_exists($f)) {
            $list[] = basename($f);
        }
    }
    return $list;
}

function db_backup_restore(string $backupBasename): bool {
    if (!defined('DB_INSTANCE_PATH')) return false;

    $dir  = dirname(DB_INSTANCE_PATH);
    $real = realpath($dir . '/' . $backupBasename);
    $base = realpath(DB_INSTANCE_PATH);

    // Validate: must be in same dir, must match pattern *.bkp.N
    if ($real === false || strpos($real, $dir) !== 0) return false;
    if (!preg_match('/\.bkp\.\d{1,4}$/', $backupBasename)) return false;

    // Backup current before restore
    db_backup_create();

    return @copy($real, DB_INSTANCE_PATH);
}

// ── Saídas ──────────────────────────────────────────────────────────────────

function db_inserir_saida(int $codResp, string $nomeResponsavel, string $portaria): int {
    $pdo  = ebi_db();
    $stmt = $pdo->prepare(
        'INSERT INTO saidas (cod_resp, nome_responsavel, portaria) VALUES (?,?,?)'
    );
    $stmt->execute([$codResp, $nomeResponsavel, $portaria]);
    return (int)$pdo->lastInsertId();
}

function db_listar_saidas(int $limite = 50): array {
    $stmt = ebi_db()->prepare(
        'SELECT * FROM saidas ORDER BY registered_at DESC LIMIT ?'
    );
    $stmt->execute([$limite]);
    return $stmt->fetchAll();
}

// ── Stats diárias (sem nomes) ────────────────────────────────────────────────

function db_stats_upsert(string $date, array $delta): void {
    $pdo = ebi_db();

    $stmt = $pdo->prepare('SELECT * FROM stats_daily WHERE date = ?');
    $stmt->execute([$date]);
    $existing = $stmt->fetch();

    $portariaData = $existing ? (json_decode($existing['portaria_data'], true) ?: []) : [];
    $comumData    = $existing ? (json_decode($existing['comum_data'],    true) ?: []) : [];

    foreach (($delta['portaria_counts'] ?? []) as $p => $cnt) {
        $portariaData[$p] = ($portariaData[$p] ?? 0) + (int)$cnt;
    }
    foreach (($delta['comum_counts'] ?? []) as $c => $cnt) {
        $comumData[$c] = ($comumData[$c] ?? 0) + (int)$cnt;
    }

    $pj  = json_encode($portariaData, JSON_UNESCAPED_UNICODE);
    $cj  = json_encode($comumData,    JSON_UNESCAPED_UNICODE);
    $now = date('Y-m-d H:i:s');

    if ($existing) {
        $pdo->prepare(
            'UPDATE stats_daily SET
                total_cadastros  = total_cadastros  + ?,
                total_impressoes = total_impressoes + ?,
                total_saidas     = total_saidas     + ?,
                age_0_3   = age_0_3   + ?,
                age_4_7   = age_4_7   + ?,
                age_8_11  = age_8_11  + ?,
                age_12_14 = age_12_14 + ?,
                age_15_17 = age_15_17 + ?,
                portaria_data = ?,
                comum_data    = ?,
                updated_at    = ?
             WHERE date = ?'
        )->execute([
            $delta['cadastros']  ?? 0, $delta['impressoes'] ?? 0, $delta['saidas'] ?? 0,
            $delta['age_0_3']    ?? 0, $delta['age_4_7']    ?? 0,
            $delta['age_8_11']   ?? 0, $delta['age_12_14']  ?? 0, $delta['age_15_17'] ?? 0,
            $pj, $cj, $now, $date,
        ]);
    } else {
        $pdo->prepare(
            'INSERT INTO stats_daily
                (date, total_cadastros, total_impressoes, total_saidas,
                 age_0_3, age_4_7, age_8_11, age_12_14, age_15_17,
                 portaria_data, comum_data, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $date,
            $delta['cadastros']  ?? 0, $delta['impressoes'] ?? 0, $delta['saidas'] ?? 0,
            $delta['age_0_3']    ?? 0, $delta['age_4_7']    ?? 0,
            $delta['age_8_11']   ?? 0, $delta['age_12_14']  ?? 0, $delta['age_15_17'] ?? 0,
            $pj, $cj, $now,
        ]);
    }
}

function db_stats_listar_diario(int $dias = 30): array {
    $desde = date('Y-m-d', strtotime("-{$dias} days"));
    $stmt  = ebi_db()->prepare(
        'SELECT * FROM stats_daily WHERE date >= ? ORDER BY date ASC'
    );
    $stmt->execute([$desde]);
    return $stmt->fetchAll();
}

function db_stats_mensal_instancia(int $meses = 12): array {
    $stmt = ebi_db()->prepare(
        "SELECT strftime('%Y-%m', date) as mes,
                SUM(total_cadastros)    as total_cadastros,
                SUM(total_impressoes)   as total_impressoes,
                SUM(total_saidas)       as total_saidas
         FROM stats_daily
         WHERE date >= date('now', '-' || ? || ' months')
         GROUP BY mes ORDER BY mes ASC"
    );
    $stmt->execute([$meses]);
    return $stmt->fetchAll();
}

function db_stats_totais_instancia(): array {
    $row = ebi_db()->query(
        'SELECT
            COALESCE(SUM(total_cadastros),  0) as total_cadastros,
            COALESCE(SUM(total_impressoes), 0) as total_impressoes,
            COALESCE(SUM(total_saidas),     0) as total_saidas,
            COALESCE(SUM(age_0_3),  0) as age_0_3,
            COALESCE(SUM(age_4_7),  0) as age_4_7,
            COALESCE(SUM(age_8_11), 0) as age_8_11,
            COALESCE(SUM(age_12_14),0) as age_12_14,
            COALESCE(SUM(age_15_17),0) as age_15_17
         FROM stats_daily'
    )->fetch();
    return $row ?: [];
}
