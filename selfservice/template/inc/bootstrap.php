<?php
/**
 * Bootstrap: carrega configuração, constantes, sessão e helpers.
 * Assume que este arquivo está em template/inc/, config.ini em template/.
 */

$config_file = __DIR__ . '/../config.ini';
if (!file_exists($config_file)) {
    die("Erro: Arquivo de configuração não encontrado em: " . htmlspecialchars($config_file));
}

$config = parse_ini_file($config_file, true, INI_SCANNER_TYPED);

if (!isset($config['GERAL'], $config['SEGURANCA'], $config['IMPRESSORA_ZPL'])) {
    die("Erro: Falta uma ou mais seções ([GERAL], [SEGURANCA], [IMPRESSORA_ZPL]) no arquivo de configuração.");
}

$baseDir = dirname(__DIR__);
$data_file_path = $baseDir . $config['GERAL']['ARQUIVO_DADOS'];
define('ARQUIVO_DADOS', $data_file_path);
define('DELIMITADOR', $config['GERAL']['DELIMITADOR']);
define('MAX_BACKUPS', $config['GERAL']['MAX_BACKUPS']);
define('NUM_LINHAS_FORMULARIO_CADASTRO', $config['GERAL']['NUM_LINHAS_FORMULARIO_CADASTRO']);
define('NUM_CAMPOS_POR_LINHA_NO_ARQUIVO', $config['GERAL']['NUM_CAMPOS_POR_LINHA_NO_ARQUIVO']);

define('SENHA_ADMIN_REAL', $config['SEGURANCA']['SENHA_ADMIN_REAL']);
define('SENHA_LOGIN', SENHA_ADMIN_REAL);

define('PRINTER_NAME', $config['IMPRESSORA_ZPL']['PRINTER_NAME'] ?? 'ZDesigner 105SL');
define('TAMPULSEIRA', $config['IMPRESSORA_ZPL']['TAMPULSEIRA']);
define('DOTS', $config['IMPRESSORA_ZPL']['DOTS']);
define('FECHO', $config['IMPRESSORA_ZPL']['FECHO']);
define('FECHOINI', $config['IMPRESSORA_ZPL']['FECHOINI'] ?? 1);
define('PULSEIRAUTIL', (TAMPULSEIRA - FECHO) * DOTS);

$urlImpressora = $config['IMPRESSORA_ZPL']['URL_IMPRESSORA'] ?? 'http://127.0.0.1:9100/write';
define('URL_IMPRESSORA', rtrim($urlImpressora, '/'));

// Timeout de sessão configurável
$tempoSessao = $config['SEGURANCA']['TEMPO_SESSAO'] ?? 1800;
define('TEMPO_SESSAO', (int)$tempoSessao);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar timeout de sessão
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > TEMPO_SESSAO) {
        $_SESSION['logado'] = false;
        $_SESSION['logout_mensagem_sucesso'] = 'Sua sessão expirou por inatividade. Faça login novamente.';
        unset($_SESSION['ultimo_acesso']);
    } else {
        $_SESSION['ultimo_acesso'] = time();
    }
}

// Atualizar timestamp de último acesso da instância
$lastAccessFile = dirname(dirname(ARQUIVO_DADOS)) . '/.lastaccess';
@file_put_contents($lastAccessFile, time());

function sanitize_for_html($string) {
    return htmlspecialchars(trim((string)($string ?? '')), ENT_QUOTES, 'UTF-8');
}

function sanitize_for_file($string) {
    return str_replace(DELIMITADOR, '-', trim($string ?? ''));
}

// --- CSRF ---
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . sanitize_for_html(csrf_token()) . '">';
}

function csrf_validate() {
    $token = $_POST['csrf_token'] ?? '';
    $valid = !empty($token) && hash_equals(csrf_token(), $token);
    if (!$valid) {
        $_SESSION['mensagemErro'] = 'Requisição inválida (token de segurança). Tente novamente.';
        header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
        exit;
    }
}

/** Regenera o token após login (opcional, evita fixação). */
function csrf_regenerate() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/** Obtém a versão do sistema baseada no último commit git ou data de modificação. */
function obter_versao_sistema() {
    static $versao = null;

    if ($versao !== null) {
        return $versao;
    }

    // Tenta obter data do último commit git
    $gitDir = dirname(dirname(__DIR__));
    if (is_dir($gitDir . '/.git')) {
        $comando = "cd " . escapeshellarg($gitDir) . " && git log -1 --format=%cd --date=format:'%Y%m%d%H%M' 2>/dev/null";
        $output = @shell_exec($comando);
        if ($output && preg_match('/^\d{12}$/', trim($output))) {
            $versao = trim($output);
            return $versao;
        }
    }

    // Fallback: usa data de modificação do arquivo index.php
    $indexFile = dirname(__DIR__) . '/index.php';
    if (file_exists($indexFile)) {
        $versao = date('YmdHi', filemtime($indexFile));
        return $versao;
    }

    // Fallback final: data atual
    $versao = date('YmdHi');
    return $versao;
}

define('VERSAO_SISTEMA', obter_versao_sistema());
