<?php
/**
 * Configuração de Caminhos Dinâmicos
 *
 * Este arquivo calcula automaticamente os caminhos absolutos a partir do .env,
 * permitindo que o sistema funcione em qualquer servidor sem hardcoded paths.
 *
 * @version 1.0
 */

// Horários administrativos, logs e tarefas devem usar o horário de Brasília.
date_default_timezone_set('America/Sao_Paulo');

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

/**
 * Obtém caminho para instâncias em quarentena.
 * Este diretório fica fora da árvore pública de instâncias.
 */
function getQuarantinePath(): string {
    $path = $_ENV['QUARANTINE_PATH'] ?? 'selfservice/data/quarantine';
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

// Aplicar modo debug conforme configurado no painel admin (.env: DEBUG_MODE)
// Controla exibição de erros PHP em tela (nunca deve ficar ligado em produção).
if (filter_var($_ENV['DEBUG_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

if (!defined('LOG_FILE_PATH')) {
    define('LOG_FILE_PATH', getLogFilePath());
}

if (!defined('BACKUP_PATH')) {
    define('BACKUP_PATH', getBackupPath());
}

if (!defined('QUARANTINE_PATH')) {
    define('QUARANTINE_PATH', getQuarantinePath());
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

/**
 * Data do último commit no formato aaaammddhhMM.
 *
 * shell_exec costuma estar em disable_functions nas hospedagens compartilhadas.
 * Nesse caso a função é removida da tabela de funções do PHP e chamá-la lança um
 * Error fatal — que o operador @ NÃO suprime. Por isso a checagem com
 * function_exists() é obrigatória antes da chamada.
 *
 * Sem shell_exec, cai para a data de modificação da referência do git, que muda
 * a cada commit/deploy e é bem mais estável que date() a cada minuto.
 */
function versaoDoUltimoCommit(string $gitRoot): string {
    if (function_exists('shell_exec')) {
        $saida = @shell_exec('git -C ' . escapeshellarg($gitRoot)
            . " log -1 --format=%cd --date=format:'%Y%m%d%H%M' 2>/dev/null");
        if ($saida !== null && preg_match('/^\d{12}$/', trim((string)$saida))) {
            return trim((string)$saida);
        }
    }

    $head = $gitRoot . '/.git/HEAD';
    if (is_file($head)) {
        $ref = trim((string)@file_get_contents($head));
        $alvo = (strpos($ref, 'ref:') === 0)
            ? $gitRoot . '/.git/' . trim(substr($ref, 4))
            : $head;
        $mtime = @filemtime(is_file($alvo) ? $alvo : $head);
        if ($mtime) return date('YmdHi', $mtime);
    }

    return date('YmdHi');
}

if (!defined('VERSAO_SISTEMA')) {
    define('VERSAO_SISTEMA', versaoDoUltimoCommit(PROJECT_ROOT));
}
