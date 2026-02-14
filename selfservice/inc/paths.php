<?php
/**
 * Configuração de Caminhos Dinâmicos
 *
 * Este arquivo calcula automaticamente os caminhos absolutos a partir do .env,
 * permitindo que o sistema funcione em qualquer servidor sem hardcoded paths.
 *
 * @version 1.0
 */

// Diretório raiz do projeto (pai de selfservice/)
define('PROJECT_ROOT', dirname(__DIR__, 2));

// Diretório selfservice/
define('SELFSERVICE_ROOT', dirname(__DIR__));

/**
 * Resolve caminho relativo para absoluto
 *
 * @param string $relativePath Caminho relativo (ex: 'selfservice/instances')
 * @return string Caminho absoluto
 */
function resolvePath(string $relativePath): string {
    // Se já for um caminho absoluto, retorna como está
    if (substr($relativePath, 0, 1) === '/' || preg_match('/^[A-Za-z]:/', $relativePath)) {
        return $relativePath;
    }

    // Resolve como relativo ao PROJECT_ROOT
    return PROJECT_ROOT . '/' . ltrim($relativePath, '/');
}

/**
 * Obtém caminho de instâncias
 *
 * @return string Caminho absoluto do diretório de instâncias
 */
function getInstanceBasePath(): string {
    $path = $_ENV['INSTANCE_BASE_PATH'] ?? 'ebi/i';
    return resolvePath($path);
}

/**
 * Obtém caminho do template
 *
 * @return string Caminho absoluto do diretório do template
 */
function getTemplatePath(): string {
    $path = $_ENV['TEMPLATE_PATH'] ?? 'ebi/template';
    return resolvePath($path);
}

/**
 * Obtém caminho de dados
 *
 * @return string Caminho absoluto do diretório de dados
 */
function getDataPath(): string {
    $path = $_ENV['DATA_PATH'] ?? 'selfservice/data';
    return resolvePath($path);
}

/**
 * Obtém caminho do arquivo de log
 *
 * @return string Caminho absoluto do arquivo de log
 */
function getLogFilePath(): string {
    $logFile = $_ENV['LOG_FILE'] ?? 'app.log';

    // Se for apenas nome de arquivo, adiciona ao DATA_PATH
    if (basename($logFile) === $logFile) {
        return getDataPath() . '/' . $logFile;
    }

    return resolvePath($logFile);
}

/**
 * Obtém caminho de backups
 *
 * @return string Caminho absoluto do diretório de backups
 */
function getBackupPath(): string {
    $path = $_ENV['BACKUP_PATH'] ?? 'selfservice/backups';
    return resolvePath($path);
}

// Definir constantes globais para fácil acesso
if (!defined('INSTANCE_BASE_PATH')) {
    define('INSTANCE_BASE_PATH', getInstanceBasePath());
}

if (!defined('TEMPLATE_PATH')) {
    define('TEMPLATE_PATH', getTemplatePath());
}

if (!defined('DATA_PATH')) {
    define('DATA_PATH', getDataPath());
}

if (!defined('LOG_FILE_PATH')) {
    define('LOG_FILE_PATH', getLogFilePath());
}

if (!defined('BACKUP_PATH')) {
    define('BACKUP_PATH', getBackupPath());
}

// Criar diretórios se não existirem
$directories = [
    INSTANCE_BASE_PATH,
    TEMPLATE_PATH,
    DATA_PATH,
    BACKUP_PATH
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }
}
