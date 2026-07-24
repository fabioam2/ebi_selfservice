<?php
/**
 * Migra instancias legadas (public_html/ebi) para o modelo de stubs compartilhados.
 *
 * Uso: php selfservice/migrar_instancias_legadas.php [--dry-run]
 *
 * O processo preserva IDs, cadastros, status de impressao e configuracao. As
 * estatisticas reconstituem somente contagens, faixas etarias e chaves de
 * portaria/comum; nenhum nome e gravado nas tabelas de estatisticas.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script deve ser executado pela linha de comando.\n");
}

require_once __DIR__ . '/inc/paths.php';
require_once TEMPLATE_PATH . '/inc/db_instance.php';
require_once __DIR__ . '/inc/db_manager.php';

/** @return array<string, mixed> */
function legacy_empty_stats_delta(): array {
    return [
        'cadastros' => 0,
        'impressoes' => 0,
        'saidas' => 0,
        'age_0_3' => 0,
        'age_4_7' => 0,
        'age_8_11' => 0,
        'age_12_14' => 0,
        'age_15_17' => 0,
        'total_meninos' => 0,
        'total_meninas' => 0,
        'portaria_counts' => [],
        'comum_counts' => [],
    ];
}

/** @param array<string, mixed> $delta */
function legacy_add_cadastro_to_stats(array &$delta, array $cadastro): void {
    $idade = (int)($cadastro['idade'] ?? 0);
    if ($idade <= 3) $delta['age_0_3']++;
    elseif ($idade <= 7) $delta['age_4_7']++;
    elseif ($idade <= 11) $delta['age_8_11']++;
    elseif ($idade <= 14) $delta['age_12_14']++;
    else $delta['age_15_17']++;

    $delta['cadastros']++;
    if (($cadastro['status_impresso'] ?? 'N') === 'S') $delta['impressoes']++;

    $portaria = strtoupper(trim((string)($cadastro['portaria'] ?? '')));
    if ($portaria !== '') {
        $delta['portaria_counts'][$portaria] = ($delta['portaria_counts'][$portaria] ?? 0) + 1;
    }

    $comum = trim((string)($cadastro['comum'] ?? ''));
    if ($comum !== '') {
        $delta['comum_counts'][$comum] = ($delta['comum_counts'][$comum] ?? 0) + 1;
    }

    $sexo = strtoupper(trim((string)($cadastro['sexo'] ?? '')));
    if ($sexo === 'M') $delta['total_meninos']++;
    elseif ($sexo === 'F') $delta['total_meninas']++;
}

/** @return array<int, array<string, mixed>> */
function legacy_read_cadastros(string $path): array {
    if (!is_file($path)) return [];

    $result = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return [];

    foreach ($lines as $line) {
        if (str_starts_with($line, '#')) continue;

        $fields = explode('|', $line);
        if (count($fields) < 9) continue;

        $id = (int)trim($fields[0]);
        if ($id <= 0) continue;

        $result[] = [
            'id' => $id,
            'nome_crianca' => trim($fields[1]),
            'nome_responsavel' => trim($fields[2]),
            'telefone' => trim($fields[3]),
            'idade' => (int)trim($fields[4]),
            'comum' => trim($fields[5]),
            'status_impresso' => strtoupper(trim($fields[6])) === 'S' ? 'S' : 'N',
            'portaria' => strtoupper(trim($fields[7])),
            'cod_resp' => (int)trim($fields[8]),
            'data_nascimento' => '',
            'sexo' => '',
        ];
    }

    return $result;
}

/** @return array<int, array{cod_resp:int,nome_responsavel:string,portaria:string,registered_at:string}> */
function legacy_read_saidas(string $path): array {
    if (!is_file($path)) return [];

    $result = [];
    $seen = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return [];

    foreach ($lines as $line) {
        if (str_starts_with($line, '#')) continue;

        $fields = explode('|', $line);
        if (count($fields) < 5) continue;

        $timestamp = (int)trim($fields[0]);
        $codResp = (int)trim($fields[1]);
        $portaria = strtoupper(trim($fields[4]));
        if ($codResp <= 0) continue;

        $key = $timestamp . '|' . $codResp . '|' . $portaria;
        if (isset($seen[$key])) continue;
        $seen[$key] = true;

        $result[] = [
            'cod_resp' => $codResp,
            'nome_responsavel' => trim($fields[2]),
            'portaria' => $portaria,
            'registered_at' => $timestamp > 0
                ? date('Y-m-d H:i:s', $timestamp)
                : date('Y-m-d H:i:s'),
        ];
    }

    return $result;
}

function legacy_copy_file(string $source, string $destination): void {
    if (!is_file($source) || !copy($source, $destination)) {
        throw new RuntimeException("Nao foi possivel copiar {$source}.");
    }
}

function legacy_normalize_config(string $source, string $destination): array {
    $content = file_get_contents($source);
    if ($content === false) {
        throw new RuntimeException("Nao foi possivel ler {$source}.");
    }

    $normalized = preg_replace([
        '/^ARQUIVO_DADOS\s*=.*$/m',
        '/^ARQUIVO_DADOS_PAINEL\s*=.*$/m',
        '/^ARQUIVO_LOG\s*=.*$/m',
    ], [
        'ARQUIVO_DADOS = "/data/cadastro_criancas.txt"',
        'ARQUIVO_DADOS_PAINEL = "/data/painel_criancas.txt"',
        'ARQUIVO_LOG = "/data/sistema.log"',
    ], $content);

    if ($normalized === null || file_put_contents($destination, $normalized, LOCK_EX) === false) {
        throw new RuntimeException("Nao foi possivel gravar {$destination}.");
    }
    @chmod($destination, 0600);

    $config = parse_ini_file($destination, true, INI_SCANNER_TYPED);
    if ($config === false || !isset($config['INFO_USUARIO'], $config['GERAL'], $config['SEGURANCA'], $config['IMPRESSORA_ZPL'])) {
        throw new RuntimeException("Configuracao invalida em {$source}.");
    }

    return $config;
}

function legacy_stats_date(array $config): string {
    $raw = (string)($config['INFO_USUARIO']['DATA_CRIACAO'] ?? $config['INFO_SISTEMA']['DATA_INSTALACAO'] ?? '');
    $timestamp = strtotime($raw);
    return $timestamp === false ? date('Y-m-d') : date('Y-m-d', $timestamp);
}

/** @param array<string, array<string, mixed>> $statsByDate */
function legacy_write_instance_stats(PDO $pdo, array $statsByDate): void {
    $statement = $pdo->prepare(
        'INSERT INTO stats_daily
            (date, total_cadastros, total_impressoes, total_saidas,
             age_0_3, age_4_7, age_8_11, age_12_14, age_15_17,
             total_meninos, total_meninas, portaria_data, comum_data, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );

    foreach ($statsByDate as $date => $delta) {
        $statement->execute([
            $date,
            $delta['cadastros'], $delta['impressoes'], $delta['saidas'],
            $delta['age_0_3'], $delta['age_4_7'], $delta['age_8_11'],
            $delta['age_12_14'], $delta['age_15_17'],
            $delta['total_meninos'], $delta['total_meninas'],
            json_encode($delta['portaria_counts'], JSON_UNESCAPED_UNICODE),
            json_encode($delta['comum_counts'], JSON_UNESCAPED_UNICODE),
            date('Y-m-d H:i:s'),
        ]);
    }
}

function legacy_write_stubs(string $instanceDir): void {
    $projectPrefix = '../../../../../';
    $rootStubs = [
        'index.php' => "<?php\ndefine('INSTANCE_DIR', __DIR__);\ndefine('INSTANCE_PROJECT_URL_PREFIX', '{$projectPrefix}');\nrequire dirname(dirname(dirname(dirname(__DIR__)))) . '/template/index.php';\n",
        'calibrar.php' => "<?php\ndefine('INSTANCE_DIR', __DIR__);\ndefine('INSTANCE_PROJECT_URL_PREFIX', '{$projectPrefix}');\nrequire dirname(dirname(dirname(dirname(__DIR__)))) . '/template/calibrar.php';\n",
        'recuperar_senha.php' => "<?php\ndefine('INSTANCE_DIR', __DIR__);\ndefine('INSTANCE_PROJECT_URL_PREFIX', '{$projectPrefix}');\nrequire dirname(dirname(dirname(dirname(__DIR__)))) . '/template/recuperar_senha.php';\n",
        'zerar_dados.php' => "<?php\ndefine('INSTANCE_DIR', __DIR__);\ndefine('INSTANCE_PROJECT_URL_PREFIX', '{$projectPrefix}');\nrequire dirname(dirname(dirname(dirname(__DIR__)))) . '/template/zerar_dados.php';\n",
    ];

    foreach ($rootStubs as $name => $content) {
        if (file_put_contents($instanceDir . '/' . $name, $content, LOCK_EX) === false) {
            throw new RuntimeException("Nao foi possivel criar o stub {$name}.");
        }
    }

    $saidaDir = $instanceDir . '/saida';
    if (!is_dir($saidaDir) && !mkdir($saidaDir, 0755, true) && !is_dir($saidaDir)) {
        throw new RuntimeException("Nao foi possivel criar {$saidaDir}.");
    }

    $saidaStubs = [
        'index.php' => 'index.php',
        'painel.php' => 'painel.php',
        'processar_qr.php' => 'processar_qr.php',
    ];
    foreach ($saidaStubs as $name => $templateFile) {
        $content = "<?php\ndefine('INSTANCE_DIR', dirname(__DIR__));\ndefine('INSTANCE_PROJECT_URL_PREFIX', '{$projectPrefix}');\nrequire dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/template/saida/{$templateFile}';\n";
        if (file_put_contents($saidaDir . '/' . $name, $content, LOCK_EX) === false) {
            throw new RuntimeException("Nao foi possivel criar o stub saida/{$name}.");
        }
    }
}

function legacy_backup_sources(string $userDir, string $instanceDir, string $configDir): string {
    $backupDir = $userDir . '/migration_backup_' . date('YmdHis');
    if (!mkdir($backupDir, 0700, true) && !is_dir($backupDir)) {
        throw new RuntimeException("Nao foi possivel criar {$backupDir}.");
    }

    foreach (['config.ini', 'cadastro_criancas.txt', 'painel_criancas.txt'] as $name) {
        $source = $configDir . '/' . $name;
        if (is_file($source)) legacy_copy_file($source, $backupDir . '/' . $name);
    }
    foreach (['index.php', 'calibrar.php', 'recuperar_senha.php', 'zerar_dados.php'] as $name) {
        $source = $instanceDir . '/' . $name;
        if (is_file($source)) legacy_copy_file($source, $backupDir . '/' . $name);
    }

    return $backupDir;
}

function legacy_migrate_instance(string $instanceDir, bool $dryRun): array {
    $userDir = dirname(dirname($instanceDir));
    $configDir = $userDir . '/config';
    $sourceConfig = $configDir . '/config.ini';
    $marker = $instanceDir . '/.legacy_migration.json';
    $databasePath = $instanceDir . '/data/instance.db';

    if (is_file($marker)) {
        return ['status' => 'skip', 'message' => 'ja migrada'];
    }
    if (!is_file($sourceConfig)) {
        return ['status' => 'skip', 'message' => 'configuracao legada ausente'];
    }
    if (file_exists($databasePath)) {
        return ['status' => 'skip', 'message' => 'SQLite existente sem marcador; requer revisao manual'];
    }

    $cadastros = legacy_read_cadastros($configDir . '/cadastro_criancas.txt');
    $saidas = legacy_read_saidas($configDir . '/painel_criancas.txt');
    if ($dryRun) {
        return ['status' => 'dry-run', 'message' => count($cadastros) . ' cadastros e ' . count($saidas) . ' saidas'];
    }

    $backupDir = legacy_backup_sources($userDir, $instanceDir, $configDir);
    $config = legacy_normalize_config($sourceConfig, $instanceDir . '/config.ini');
    $dataDir = dirname($databasePath);
    if (!mkdir($dataDir, 0755, true) && !is_dir($dataDir)) {
        throw new RuntimeException("Nao foi possivel criar {$dataDir}.");
    }

    $pdo = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA synchronous=NORMAL; PRAGMA foreign_keys=ON;');
    _ebi_db_init($pdo);

    $statsDate = legacy_stats_date($config);
    $statsByDate = [];
    $statsByDate[$statsDate] = legacy_empty_stats_delta();
    $pdo->beginTransaction();
    try {
        $insertCadastro = $pdo->prepare(
            'INSERT INTO cadastros
                (id, nome_crianca, nome_responsavel, telefone, idade, comum, status_impresso,
                 portaria, cod_resp, data_nascimento, sexo, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        foreach ($cadastros as $cadastro) {
            $insertCadastro->execute([
                $cadastro['id'], $cadastro['nome_crianca'], $cadastro['nome_responsavel'],
                $cadastro['telefone'], $cadastro['idade'], $cadastro['comum'],
                $cadastro['status_impresso'], $cadastro['portaria'], $cadastro['cod_resp'],
                '', '', $statsDate . ' 00:00:00',
            ]);
            legacy_add_cadastro_to_stats($statsByDate[$statsDate], $cadastro);
        }

        $insertSaida = $pdo->prepare(
            'INSERT INTO saidas (cod_resp, nome_responsavel, portaria, registered_at) VALUES (?,?,?,?)'
        );
        foreach ($saidas as $saida) {
            $insertSaida->execute([$saida['cod_resp'], $saida['nome_responsavel'], $saida['portaria'], $saida['registered_at']]);
            $saidaDate = substr($saida['registered_at'], 0, 10);
            if (!isset($statsByDate[$saidaDate])) {
                $statsByDate[$saidaDate] = legacy_empty_stats_delta();
            }
            $statsByDate[$saidaDate]['saidas']++;
            if ($saida['portaria'] !== '') {
                $statsByDate[$saidaDate]['portaria_counts'][$saida['portaria']] = ($statsByDate[$saidaDate]['portaria_counts'][$saida['portaria']] ?? 0) + 1;
            }
        }

        legacy_write_instance_stats($pdo, array_filter($statsByDate, static function (array $delta): bool {
            return $delta['cadastros'] > 0 || $delta['impressoes'] > 0 || $delta['saidas'] > 0;
        }));
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        @unlink($databasePath);
        throw $error;
    }

    @chmod($databasePath, 0600);
    foreach ($statsByDate as $date => $delta) {
        if ($delta['cadastros'] === 0 && $delta['impressoes'] === 0 && $delta['saidas'] === 0) continue;
        db_registrar_admin_stat(
            (string)$config['INFO_USUARIO']['USER_ID'],
            (string)($config['INFO_USUARIO']['CIDADE'] ?? ''),
            (string)($config['INFO_USUARIO']['COMUM'] ?? ''),
            $delta,
            null,
            $date
        );
    }

    legacy_write_stubs($instanceDir);
    $markerData = json_encode([
        'migrated_at' => date('c'),
        'backup_dir' => basename($backupDir),
        'cadastros' => count($cadastros),
        'saidas' => count($saidas),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($markerData === false || file_put_contents($marker, $markerData . "\n", LOCK_EX) === false) {
        throw new RuntimeException("Nao foi possivel gravar {$marker}.");
    }
    @chmod($marker, 0600);

    return ['status' => 'ok', 'message' => count($cadastros) . ' cadastros e ' . count($saidas) . ' saidas'];
}

$options = getopt('', ['dry-run']);
$dryRun = array_key_exists('dry-run', $options);
$legacyDirs = glob(INSTANCE_BASE_PATH . '/*/public_html/ebi', GLOB_ONLYDIR) ?: [];

central_db();
foreach (glob(INSTANCE_BASE_PATH . '/*/data/instance.db') ?: [] as $databasePath) {
    $pdo = new PDO('sqlite:' . $databasePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    _ebi_db_init($pdo);
    @chmod($databasePath, 0600);
}

if (empty($legacyDirs)) {
    echo "Nenhuma instancia legada encontrada.\n";
    exit(0);
}

$errors = 0;
foreach ($legacyDirs as $instanceDir) {
    $userId = basename(dirname(dirname($instanceDir)));
    try {
        $result = legacy_migrate_instance($instanceDir, $dryRun);
        echo "[{$result['status']}] {$userId}: {$result['message']}\n";
    } catch (Throwable $error) {
        $errors++;
        fwrite(STDERR, "[erro] {$userId}: {$error->getMessage()}\n");
    }
}

exit($errors === 0 ? 0 : 1);