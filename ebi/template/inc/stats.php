<?php
/**
 * Funções de registro de estatísticas.
 * Grava em stats_daily (instância, sem nomes) e propaga para admin_daily_stats (central).
 *
 * Depende de db_instance.php (ebi_db, db_stats_upsert) e das constantes:
 *   INSTANCE_USER_ID, INSTANCE_CIDADE, INSTANCE_COMUM, CENTRAL_DB_PATH (opcionais)
 */

require_once __DIR__ . '/db_instance.php';

// ── Classificação de idade em faixa etária ───────────────────────────────────

function _stats_age_bucket(int $idade): string {
    if ($idade <= 3)  return 'age_0_3';
    if ($idade <= 7)  return 'age_4_7';
    if ($idade <= 11) return 'age_8_11';
    if ($idade <= 14) return 'age_12_14';
    return 'age_15_17';
}

// ── Propagação para o BD central ─────────────────────────────────────────────

function _stats_push_central(array $adminDelta): void {
    if (!defined('CENTRAL_DB_PATH') || !defined('INSTANCE_USER_ID')) return;

    try {
        static $managerLoaded = false;
        if (!$managerLoaded) {
            $mp = defined('SELFSERVICE_DB_MANAGER')
                ? SELFSERVICE_DB_MANAGER
                : dirname(dirname(dirname(__DIR__))) . '/selfservice/inc/db_manager.php';
            if (file_exists($mp)) {
                require_once $mp;
                $managerLoaded = true;
            }
        }
        if ($managerLoaded && function_exists('db_registrar_admin_stat')) {
            db_registrar_admin_stat(
                INSTANCE_USER_ID,
                defined('INSTANCE_CIDADE') ? INSTANCE_CIDADE : '',
                defined('INSTANCE_COMUM')  ? INSTANCE_COMUM  : '',
                $adminDelta,
                CENTRAL_DB_PATH
            );
        }
    } catch (Throwable $e) {
        error_log('[EBI Stats] central push error: ' . $e->getMessage());
    }
}

// ── API pública ──────────────────────────────────────────────────────────────

/**
 * Registra novos cadastros.
 * $cadastros: array de ['idade'=>int, 'comum'=>string, 'portaria'=>string]
 */
function stats_on_cadastro(array $cadastros): void {
    if (empty($cadastros)) return;

    $today = date('Y-m-d');
    $delta = [
        'cadastros'  => count($cadastros),
        'impressoes' => 0,
        'saidas'     => 0,
        'age_0_3' => 0, 'age_4_7' => 0, 'age_8_11' => 0, 'age_12_14' => 0, 'age_15_17' => 0,
        'portaria_counts' => [],
        'comum_counts'    => [],
    ];

    foreach ($cadastros as $c) {
        $idade   = (int)($c['idade']    ?? 0);
        $portaria = strtoupper(trim($c['portaria'] ?? ''));
        $comum    = trim($c['comum']    ?? '');

        $bucket = _stats_age_bucket($idade);
        $delta[$bucket]++;

        if ($portaria) {
            $delta['portaria_counts'][$portaria] = ($delta['portaria_counts'][$portaria] ?? 0) + 1;
        }
        if ($comum) {
            $delta['comum_counts'][$comum] = ($delta['comum_counts'][$comum] ?? 0) + 1;
        }
    }

    try {
        db_stats_upsert($today, $delta);
    } catch (Throwable $e) {
        error_log('[EBI Stats] instance upsert error: ' . $e->getMessage());
    }

    _stats_push_central([
        'cadastros'       => $delta['cadastros'],
        'impressoes'      => 0,
        'saidas'          => 0,
        'age_0_3'         => $delta['age_0_3'],
        'age_4_7'         => $delta['age_4_7'],
        'age_8_11'        => $delta['age_8_11'],
        'age_12_14'       => $delta['age_12_14'],
        'age_15_17'       => $delta['age_15_17'],
        'portaria_counts' => $delta['portaria_counts'],
        'comum_counts'    => $delta['comum_counts'],
    ]);
}

/**
 * Registra impressões de etiquetas.
 */
function stats_on_impressao(int $quantidade = 1): void {
    if ($quantidade <= 0) return;

    $today = date('Y-m-d');
    $delta = [
        'cadastros' => 0, 'impressoes' => $quantidade, 'saidas' => 0,
        'age_0_3' => 0, 'age_4_7' => 0, 'age_8_11' => 0, 'age_12_14' => 0, 'age_15_17' => 0,
        'portaria_counts' => [], 'comum_counts' => [],
    ];

    try {
        db_stats_upsert($today, $delta);
    } catch (Throwable $e) {
        error_log('[EBI Stats] impressao upsert: ' . $e->getMessage());
    }

    _stats_push_central(['cadastros' => 0, 'impressoes' => $quantidade, 'saidas' => 0,
        'age_0_3' => 0, 'age_4_7' => 0, 'age_8_11' => 0, 'age_12_14' => 0, 'age_15_17' => 0,
        'portaria_counts' => [], 'comum_counts' => []]);
}

/**
 * Registra saída pela portaria.
 */
function stats_on_saida(string $portaria): void {
    $today = date('Y-m-d');
    $portaria = strtoupper(trim($portaria));
    $delta = [
        'cadastros' => 0, 'impressoes' => 0, 'saidas' => 1,
        'age_0_3' => 0, 'age_4_7' => 0, 'age_8_11' => 0, 'age_12_14' => 0, 'age_15_17' => 0,
        'portaria_counts' => $portaria ? [$portaria => 1] : [],
        'comum_counts' => [],
    ];

    try {
        db_stats_upsert($today, $delta);
    } catch (Throwable $e) {
        error_log('[EBI Stats] saida upsert: ' . $e->getMessage());
    }

    _stats_push_central(['cadastros' => 0, 'impressoes' => 0, 'saidas' => 1,
        'age_0_3' => 0, 'age_4_7' => 0, 'age_8_11' => 0, 'age_12_14' => 0, 'age_15_17' => 0,
        'portaria_counts' => $portaria ? [$portaria => 1] : [], 'comum_counts' => []]);
}
