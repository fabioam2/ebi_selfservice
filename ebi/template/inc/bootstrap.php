<?php
/**
 * Bootstrap: carrega configuração, constantes, sessão e helpers.
 *
 * Suporta dois modos de operação:
 *  1. Modo template direto  — config.ini em __DIR__/../config.ini
 *  2. Modo thin stub        — INSTANCE_DIR definido pelo stub; config.ini em INSTANCE_DIR/config.ini
 *
 * Após este arquivo, ebi_db() retorna o PDO da instância.
 */

// ── Localizar a raiz da instância ────────────────────────────────────────────
$_ebi_instance_root = defined('INSTANCE_DIR') ? INSTANCE_DIR : dirname(__DIR__);

$config_file = $_ebi_instance_root . '/config.ini';
if (!file_exists($config_file)) {
    die('Erro: config.ini não encontrado em: ' . htmlspecialchars($config_file));
}

$config = parse_ini_file($config_file, true, INI_SCANNER_TYPED);

if (!isset($config['GERAL'], $config['SEGURANCA'], $config['IMPRESSORA_ZPL'])) {
    die('Erro: Seções obrigatórias ([GERAL], [SEGURANCA], [IMPRESSORA_ZPL]) ausentes no config.ini.');
}

// ── Constantes de banco de dados ─────────────────────────────────────────────
$_ebi_data_dir = $_ebi_instance_root . '/data';

define('DB_INSTANCE_PATH', $_ebi_data_dir . '/instance.db');
define('ARQUIVO_DADOS',    $_ebi_data_dir . '/cadastro_criancas.txt'); // compat legado

// Caminho para o BD central: a profundidade depende do modo de operação.
// Template (ebi/template/):     2 dirname() → raiz do projeto
// Instância (ebi/i/user_XXX/): 3 dirname() → raiz do projeto
$_ebi_central_base = defined('INSTANCE_DIR')
    ? dirname(dirname(dirname($_ebi_instance_root)))  // ebi/i/user_XXX/ → ebi/i/ → ebi/ → raiz
    : dirname(dirname($_ebi_instance_root));           // ebi/template/   → ebi/   → raiz
define('CENTRAL_DB_PATH', $_ebi_central_base . '/selfservice/data/ebi.db');

// Identificação da instância para propagar stats ao BD central
$_ebi_info_usuario = $config['INFO_USUARIO'] ?? [];
if (!defined('INSTANCE_USER_ID')) {
    define('INSTANCE_USER_ID', (string)($_ebi_info_usuario['USER_ID'] ?? ''));
}
if (!defined('INSTANCE_CIDADE')) {
    define('INSTANCE_CIDADE', (string)($_ebi_info_usuario['CIDADE'] ?? ''));
}
if (!defined('INSTANCE_COMUM')) {
    define('INSTANCE_COMUM', (string)($_ebi_info_usuario['COMUM'] ?? ''));
}

// ── Constantes gerais ─────────────────────────────────────────────────────────
define('DELIMITADOR',                   $config['GERAL']['DELIMITADOR']             ?? '|');
define('MAX_BACKUPS',                   $config['GERAL']['MAX_BACKUPS']              ?? 10);
define('NUM_LINHAS_FORMULARIO_CADASTRO',$config['GERAL']['NUM_LINHAS_FORMULARIO_CADASTRO'] ?? 5);
define('NUM_CAMPOS_POR_LINHA_NO_ARQUIVO',$config['GERAL']['NUM_CAMPOS_POR_LINHA_NO_ARQUIVO'] ?? 8);

// ── Constantes de segurança ───────────────────────────────────────────────────
define('SENHA_ADMIN_HASH',  (string)($config['SEGURANCA']['SENHA_ADMIN_HASH']  ?? ''));
define('SENHA_ADMIN_REAL',  (string)($config['SEGURANCA']['SENHA_ADMIN_REAL']  ?? '')); // legado
define('SENHA_LOGIN',        SENHA_ADMIN_REAL);
define('CAMINHO_CONFIG_INI', $config_file);

// ── Constantes da impressora ZPL ──────────────────────────────────────────────
define('PRINTER_NAME',                 $config['IMPRESSORA_ZPL']['PRINTER_NAME']                  ?? 'ZDesigner 105SL');
define('PALAVRA_CONTADOR_COMUM',       $config['IMPRESSORA_ZPL']['PALAVRA_CONTADOR_COMUM']         ?? 'bonfim');
define('LISTA_PALAVRAS_CONTADOR_COMUM',$config['IMPRESSORA_ZPL']['LISTA_PALAVRAS_CONTADOR_COMUM'] ?? '');
define('TAMPULSEIRA',                  $config['IMPRESSORA_ZPL']['TAMPULSEIRA']);
define('DOTS',                         $config['IMPRESSORA_ZPL']['DOTS']);
define('FECHO',                        $config['IMPRESSORA_ZPL']['FECHO']);
define('FECHOINI',                     $config['IMPRESSORA_ZPL']['FECHOINI'] ?? 1);
define('PULSEIRAUTIL',                 (TAMPULSEIRA - FECHO) * DOTS);
define('URL_IMPRESSORA',               rtrim($config['IMPRESSORA_ZPL']['URL_IMPRESSORA'] ?? 'http://127.0.0.1:9100/write', '/'));

// ── Sessão ────────────────────────────────────────────────────────────────────
$_ebi_tempo_sessao = (int)($config['SEGURANCA']['TEMPO_SESSAO'] ?? 1800);
define('TEMPO_SESSAO', $_ebi_tempo_sessao);

if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = [
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($cookieParams);
    } else {
        session_set_cookie_params(
            $cookieParams['lifetime'], $cookieParams['path'],
            $cookieParams['domain'],   $cookieParams['secure'],
            $cookieParams['httponly']
        );
    }
    session_start();
}

// ── Headers de segurança HTTP ─────────────────────────────────────────────────
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// ── Timeout de sessão ─────────────────────────────────────────────────────────
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > TEMPO_SESSAO) {
        $_SESSION['logado'] = false;
        $_SESSION['logout_mensagem_sucesso'] = 'Sua sessão expirou por inatividade. Faça login novamente.';
        unset($_SESSION['ultimo_acesso']);
    } else {
        $_SESSION['ultimo_acesso'] = time();
    }
}

// ── Timestamp de último acesso da instância ───────────────────────────────────
@file_put_contents($_ebi_instance_root . '/.lastaccess', time());

// ── Funções utilitárias ───────────────────────────────────────────────────────

function sanitize_for_html($string) {
    return htmlspecialchars(trim((string)($string ?? '')), ENT_QUOTES, 'UTF-8');
}

function sanitize_for_file($string) {
    return str_replace(defined('DELIMITADOR') ? DELIMITADOR : '|', '-', trim($string ?? ''));
}

// ── CSRF ──────────────────────────────────────────────────────────────────────

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

function csrf_regenerate() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Verificação de senha ──────────────────────────────────────────────────────

function verificar_senha_admin(string $senhaDigitada): bool {
    if ($senhaDigitada === '') return false;
    $hash  = defined('SENHA_ADMIN_HASH') ? SENHA_ADMIN_HASH : '';
    if ($hash !== '' && preg_match('/^\$2[aby]\$/', $hash)) {
        return password_verify($senhaDigitada, $hash);
    }
    $plain = defined('SENHA_ADMIN_REAL') ? SENHA_ADMIN_REAL : '';
    if ($plain !== '' && hash_equals($plain, $senhaDigitada)) {
        migrar_senha_legada_para_hash('SENHA_ADMIN_HASH', 'SENHA_ADMIN_REAL', $senhaDigitada);
        return true;
    }
    return false;
}

function verificar_senha_painel(string $senhaDigitada): bool {
    if ($senhaDigitada === '') return false;
    global $config;
    $hash  = (string)($config['SEGURANCA']['SENHA_PAINEL_HASH'] ?? '');
    if ($hash !== '' && preg_match('/^\$2[aby]\$/', $hash)) {
        return password_verify($senhaDigitada, $hash);
    }
    $plain = (string)($config['SEGURANCA']['SENHA_PAINEL'] ?? '');
    if ($plain !== '' && hash_equals($plain, $senhaDigitada)) {
        migrar_senha_legada_para_hash('SENHA_PAINEL_HASH', 'SENHA_PAINEL', $senhaDigitada);
        return true;
    }
    return false;
}

function migrar_senha_legada_para_hash(string $chaveHash, string $chaveLegado, string $senhaPlana): void {
    if (!defined('CAMINHO_CONFIG_INI')) return;
    $arq = CAMINHO_CONFIG_INI;
    if (!is_writable($arq)) return;
    $novoHash = password_hash($senhaPlana, PASSWORD_BCRYPT, ['cost' => 12]);
    $conteudo = file_get_contents($arq);
    if ($conteudo === false) return;
    $linhas    = preg_split("/\r?\n/", $conteudo);
    $dentroSeg = false;
    $setHash   = false;
    foreach ($linhas as $i => $ln) {
        if (preg_match('/^\s*\[([^\]]+)\]/', $ln, $m)) {
            if ($dentroSeg && !$setHash) {
                array_splice($linhas, $i, 0, [$chaveHash . ' = "' . $novoHash . '"']);
                $setHash = true;
            }
            $dentroSeg = strcasecmp(trim($m[1]), 'SEGURANCA') === 0;
            continue;
        }
        if ($dentroSeg) {
            if (preg_match('/^\s*' . preg_quote($chaveHash, '/') . '\s*=/', $ln)) {
                $linhas[$i] = $chaveHash . ' = "' . $novoHash . '"';
                $setHash    = true;
            } elseif (preg_match('/^\s*' . preg_quote($chaveLegado, '/') . '\s*=/', $ln)) {
                $linhas[$i] = $chaveLegado . ' = ""';
            }
        }
    }
    if (!$setHash) {
        $linhas[] = '[SEGURANCA]';
        $linhas[] = $chaveHash . ' = "' . $novoHash . '"';
    }
    @file_put_contents($arq, implode("\n", $linhas), LOCK_EX);
    @chmod($arq, 0600);
}

// ── Versão do sistema ─────────────────────────────────────────────────────────

function obter_versao_sistema(): string {
    static $versao = null;
    if ($versao !== null) return $versao;
    // Raiz da instância (evita capturar $_ebi_instance_root do escopo externo)
    $instanceRoot = defined('INSTANCE_DIR') ? INSTANCE_DIR : dirname(dirname(__DIR__));
    // Raiz do projeto (3 níveis acima de qualquer modo)
    // Template: ebi/template/inc/ → ebi/template/ → ebi/ → raiz
    // Instância: ebi/i/user_XXX/  → ebi/i/        → ebi/ → raiz
    $gitDir = defined('INSTANCE_DIR')
        ? dirname(dirname(dirname(INSTANCE_DIR)))
        : dirname(dirname(dirname(__DIR__)));
    if (is_dir($gitDir . '/.git')) {
        $cmd = "cd " . escapeshellarg($gitDir) . " && git log -1 --format=%cd --date=format:'%Y%m%d%H%M' 2>/dev/null";
        $out = @shell_exec($cmd);
        if ($out && preg_match('/^\d{12}$/', trim($out))) {
            return $versao = trim($out);
        }
    }
    $indexFile = $instanceRoot . '/index.php';
    if (file_exists($indexFile)) {
        return $versao = date('YmdHi', filemtime($indexFile));
    }
    return $versao = date('YmdHi');
}

define('VERSAO_SISTEMA', obter_versao_sistema());
