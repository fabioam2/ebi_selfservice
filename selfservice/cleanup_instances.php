<?php
/**
 * Script de Limpeza Automática de Instâncias Inativas
 *
 * Remove automaticamente instâncias que ficaram sem uso por um período configurável.
 * Protege dados de crianças removendo instâncias antigas sem rastros.
 *
 * Uso:
 *   php cleanup_instances.php [--dry-run] [--force] [--verbose]
 *
 * Opções:
 *   --dry-run    Simula a execução sem remover nada
 *   --force      Remove sem confirmação (para uso em cron)
 *   --verbose    Mostra mais detalhes durante a execução
 *
 * Configuração no cron (exemplo):
 *   0 * * * * php /caminho/para/cleanup_instances.php --force >> /var/log/cleanup_instances.log 2>&1
 */

// Carregar .env (para respeitar CLEANUP_INACTIVE_HOURS configurado no painel admin)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/../.env')) {
        Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
    }
}

// Load paths configuration
require_once __DIR__ . '/inc/paths.php';

// Configurações
$dryRun = in_array('--dry-run', $argv ?? []);
$force = in_array('--force', $argv ?? []);
$verbose = in_array('--verbose', $argv ?? []);

// Diretórios
$baseDir = __DIR__;
$instancesDir = INSTANCE_BASE_PATH . '/';
$templateConfigFile = TEMPLATE_PATH . '/config.ini';

// Log
function logMessage($message, $isVerbose = false) {
    global $verbose;
    if (!$isVerbose || $verbose) {
        echo "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
    }
}

// Verificar se diretório de instâncias existe
if (!is_dir($instancesDir)) {
    logMessage("❌ Diretório de instâncias não encontrado: $instancesDir");
    exit(1);
}

// Carregar configuração do template
if (!file_exists($templateConfigFile)) {
    logMessage("❌ Arquivo de configuração não encontrado: $templateConfigFile");
    exit(1);
}

$config = parse_ini_file($templateConfigFile, true, INI_SCANNER_TYPED);

if (!isset($config['LIMPEZA_AUTOMATICA'])) {
    logMessage("⚠️  Seção [LIMPEZA_AUTOMATICA] não encontrada no config.ini");
    exit(1);
}

$limpezaConfig = $config['LIMPEZA_AUTOMATICA'];
$habilitado = $limpezaConfig['HABILITAR_LIMPEZA'] ?? true;
// CLEANUP_INACTIVE_HOURS (.env, configurável via painel admin) tem prioridade sobre o config.ini estático
$horasInatividade = isset($_ENV['CLEANUP_INACTIVE_HOURS'])
    ? (int)$_ENV['CLEANUP_INACTIVE_HOURS']
    : ($limpezaConfig['HORAS_INATIVIDADE'] ?? 6);
$fazerBackup = $limpezaConfig['BACKUP_ANTES_REMOVER'] ?? true;
$dirBackup = $limpezaConfig['DIRETORIO_BACKUP'] ?? '/../../backups_removed/';
$manterLogs = $limpezaConfig['MANTER_LOGS'] ?? false;

// Verificar se está habilitado
if (!$habilitado) {
    logMessage("ℹ️  Limpeza automática está DESABILITADA no config.ini");
    exit(0);
}

logMessage("🚀 Iniciando limpeza de instâncias inativas...");
logMessage("⚙️  Configuração: " . $horasInatividade . " horas de inatividade", true);
if ($dryRun) {
    logMessage("⚠️  MODO DRY-RUN: Nenhuma instância será removida");
}

// Calcular timestamp limite
$timestampLimite = time() - ($horasInatividade * 3600);
$dataLimite = date('Y-m-d H:i:s', $timestampLimite);
logMessage("📅 Removendo instâncias sem uso desde: $dataLimite", true);

// Escanear diretório de instâncias
$instancias = array_diff(scandir($instancesDir), ['.', '..']);
$totalInstancias = count($instancias);
$instanciasRemovidas = 0;
$instanciasAtivas = 0;

logMessage("📊 Total de instâncias encontradas: $totalInstancias");

foreach ($instancias as $instanceId) {
    $instancePath = $instancesDir . $instanceId;

    // Verificar se é um diretório
    if (!is_dir($instancePath)) {
        logMessage("⏭️  Ignorando arquivo: $instanceId", true);
        continue;
    }

    // Buscar arquivo .lastaccess
    $lastAccessFile = $instancePath . '/config/.lastaccess';
    $configDir = $instancePath . '/config/';

    // Determinar último acesso
    $ultimoAcesso = null;

    if (file_exists($lastAccessFile)) {
        $ultimoAcesso = (int)file_get_contents($lastAccessFile);
        logMessage("  [$instanceId] Último acesso via .lastaccess: " . date('Y-m-d H:i:s', $ultimoAcesso), true);
    } elseif (file_exists($configDir)) {
        // Fallback: usar data de modificação do diretório config
        $ultimoAcesso = filemtime($configDir);
        logMessage("  [$instanceId] Último acesso via filemtime: " . date('Y-m-d H:i:s', $ultimoAcesso), true);
    }

    if ($ultimoAcesso === null) {
        logMessage("  [$instanceId] ⚠️  Não foi possível determinar último acesso, pulando...");
        continue;
    }

    // Verificar se está inativo
    if ($ultimoAcesso < $timestampLimite) {
        $horasInativo = round((time() - $ultimoAcesso) / 3600, 1);
        logMessage("  [$instanceId] 🗑️  INATIVA há $horasInativo horas - será removida");

        if (!$dryRun) {
            // Fazer backup se configurado
            if ($fazerBackup) {
                $backupDir = $baseDir . $dirBackup;
                if (!file_exists($backupDir)) {
                    mkdir($backupDir, 0755, true);
                }

                $backupFile = $backupDir . $instanceId . '_' . date('Ymd_His') . '.tar.gz';
                $tarCommand = "cd " . escapeshellarg($instancesDir) . " && tar -czf " . escapeshellarg($backupFile) . " " . escapeshellarg($instanceId) . " 2>&1";

                $output = [];
                $returnCode = 0;
                exec($tarCommand, $output, $returnCode);

                if ($returnCode === 0 && file_exists($backupFile)) {
                    logMessage("  [$instanceId] 💾 Backup criado: " . basename($backupFile), true);
                } else {
                    logMessage("  [$instanceId] ⚠️  Falha ao criar backup: " . implode(' ', $output));
                }
            }

            // Remover instância
            $removeCommand = "rm -rf " . escapeshellarg($instancePath);
            $output = [];
            $returnCode = 0;
            exec($removeCommand, $output, $returnCode);

            if ($returnCode === 0) {
                logMessage("  [$instanceId] ✅ Instância removida com sucesso");
                $instanciasRemovidas++;
            } else {
                logMessage("  [$instanceId] ❌ Erro ao remover instância: " . implode(' ', $output));
            }
        } else {
            $instanciasRemovidas++;
        }
    } else {
        $horasDesdeAcesso = round((time() - $ultimoAcesso) / 3600, 1);
        logMessage("  [$instanceId] ✅ Ativa (última atividade há $horasDesdeAcesso horas)", true);
        $instanciasAtivas++;
    }
}

// Resumo
logMessage("");
logMessage("=" . str_repeat("=", 60));
logMessage("📊 RESUMO DA LIMPEZA");
logMessage("=" . str_repeat("=", 60));
logMessage("Total de instâncias analisadas: $totalInstancias");
logMessage("Instâncias ativas: $instanciasAtivas");
logMessage("Instâncias removidas: $instanciasRemovidas" . ($dryRun ? " (simulado)" : ""));
logMessage("=" . str_repeat("=", 60));

if ($dryRun && $instanciasRemovidas > 0) {
    logMessage("");
    logMessage("ℹ️  Execute sem --dry-run para remover as instâncias inativas");
}

exit(0);
