<?php
/**
 * Bootstrap do módulo Saída/Portaria.
 * Suporta modo direto (config.ini 2 níveis acima) e modo thin stub (INSTANCE_DIR).
 */

// ── Localizar a raiz da instância ─────────────────────────────────────────────
// Em modo thin stub: INSTANCE_DIR é definido pelo stub saida/index.php.
// Em modo direto (template):  saida/inc/ → saida/ → template/ = root.
$_saida_instance_root = defined('INSTANCE_DIR') ? INSTANCE_DIR : dirname(dirname(__DIR__));

$config_file = $_saida_instance_root . '/config.ini';
if (!file_exists($config_file)) {
    die('Erro: config.ini não encontrado em: ' . htmlspecialchars($config_file));
}

$config = parse_ini_file($config_file, true, INI_SCANNER_TYPED);
if (!isset($config['GERAL'], $config['SEGURANCA'])) {
    die('Erro: Seções [GERAL] e [SEGURANCA] ausentes no config.ini.');
}

// ── Constantes de BD ──────────────────────────────────────────────────────────
$_saida_data_dir = $_saida_instance_root . '/data';

define('DB_INSTANCE_PATH', $_saida_data_dir . '/instance.db');
define('ARQUIVO_DADOS',    $_saida_data_dir . '/cadastro_criancas.txt'); // compat legado
define('ARQUIVO_SAIDAS',   $_saida_data_dir . '/saidas.log');            // compat legado

// Template: $_saida_instance_root = ebi/template/  → 2×dirname → raiz do projeto
// Instância: $_saida_instance_root = ebi/i/user_XXX/ → 3×dirname → raiz do projeto
$_saida_central_base = defined('INSTANCE_DIR')
    ? dirname(dirname(dirname($_saida_instance_root)))
    : dirname(dirname($_saida_instance_root));
define('CENTRAL_DB_PATH', $_saida_central_base . '/selfservice/data/ebi.db');

$_saida_info = $config['INFO_USUARIO'] ?? [];
if (!defined('INSTANCE_USER_ID')) define('INSTANCE_USER_ID', (string)($_saida_info['USER_ID'] ?? ''));
if (!defined('INSTANCE_CIDADE'))  define('INSTANCE_CIDADE',  (string)($_saida_info['CIDADE']  ?? ''));
if (!defined('INSTANCE_COMUM'))   define('INSTANCE_COMUM',   (string)($_saida_info['COMUM']   ?? ''));

// ── Constantes de segurança e sessão ─────────────────────────────────────────
define('DELIMITADOR',         $config['GERAL']['DELIMITADOR']                ?? '|');
define('MAX_BACKUPS',         $config['GERAL']['MAX_BACKUPS']                 ?? 10);
define('SENHA_PAINEL_HASH',   (string)($config['SEGURANCA']['SENHA_PAINEL_HASH'] ?? ''));
define('SENHA_PAINEL',        (string)($config['SEGURANCA']['SENHA_PAINEL']      ?? '')); // legado
define('CAMINHO_CONFIG_INI',  $config_file);
define('TEMPO_SESSAO',        (int)($config['SEGURANCA']['TEMPO_SESSAO']     ?? 1800));
define('MAX_TENTATIVAS_LOGIN',(int)($config['SEGURANCA']['MAX_TENTATIVAS_LOGIN'] ?? 5));
define('TEMPO_BLOQUEIO',      (int)($config['SEGURANCA']['TEMPO_BLOQUEIO']   ?? 300));

// ── Sessão ────────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = [
        'lifetime' => 0, 'path' => '/', 'domain' => '',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, 'samesite' => 'Lax',
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

// ── Headers de segurança ──────────────────────────────────────────────────────
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
if (isset($_SESSION['logado_saida']) && $_SESSION['logado_saida'] === true) {
    if (isset($_SESSION['ultimo_acesso_saida'])
        && (time() - $_SESSION['ultimo_acesso_saida']) > TEMPO_SESSAO
    ) {
        $_SESSION['logado_saida']    = false;
        $_SESSION['logout_mensagem'] = 'Sessão expirada por inatividade.';
        unset($_SESSION['ultimo_acesso_saida']);
    } else {
        $_SESSION['ultimo_acesso_saida'] = time();
    }
}

// ── Funções utilitárias ───────────────────────────────────────────────────────

function sanitize_for_html(string $s): string {
    return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
}

function sanitize_for_file(string $s): string {
    return str_replace('|', '-', trim($s));
}

// ── CSRF ──────────────────────────────────────────────────────────────────────

function csrf_token(): string {
    if (empty($_SESSION['csrf_token_saida'])) {
        $_SESSION['csrf_token_saida'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token_saida'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . sanitize_for_html(csrf_token()) . '">';
}

function csrf_validate(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals(csrf_token(), $token)) {
        $_SESSION['mensagemErro'] = 'Requisição inválida (token de segurança).';
        header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
        exit;
    }
}

function csrf_regenerate(): void {
    $_SESSION['csrf_token_saida'] = bin2hex(random_bytes(32));
}

// ── Verificação de senha do painel ────────────────────────────────────────────

function verificar_senha_painel(string $senhaDigitada): bool {
    if ($senhaDigitada === '') return false;
    $hash  = defined('SENHA_PAINEL_HASH') ? SENHA_PAINEL_HASH : '';
    if ($hash !== '' && preg_match('/^\$2[aby]\$/', $hash)) {
        return password_verify($senhaDigitada, $hash);
    }
    $plain = defined('SENHA_PAINEL') ? SENHA_PAINEL : '';
    if ($plain !== '' && hash_equals($plain, $senhaDigitada)) {
        _migrar_senha_painel_hash($senhaDigitada);
        return true;
    }
    return false;
}

function _migrar_senha_painel_hash(string $senhaPlana): void {
    if (!defined('CAMINHO_CONFIG_INI') || !is_writable(CAMINHO_CONFIG_INI)) return;
    $hash     = password_hash($senhaPlana, PASSWORD_BCRYPT, ['cost' => 12]);
    $conteudo = file_get_contents(CAMINHO_CONFIG_INI);
    if ($conteudo === false) return;
    $linhas   = preg_split("/\r?\n/", $conteudo);
    $dentroSeg = false;
    $setHash   = false;
    foreach ($linhas as $i => $ln) {
        if (preg_match('/^\s*\[([^\]]+)\]/', $ln, $m)) {
            if ($dentroSeg && !$setHash) {
                array_splice($linhas, $i, 0, ['SENHA_PAINEL_HASH = "' . $hash . '"']);
                $setHash = true;
            }
            $dentroSeg = strcasecmp(trim($m[1]), 'SEGURANCA') === 0;
            continue;
        }
        if ($dentroSeg) {
            if (preg_match('/^\s*SENHA_PAINEL_HASH\s*=/', $ln)) {
                $linhas[$i] = 'SENHA_PAINEL_HASH = "' . $hash . '"';
                $setHash    = true;
            } elseif (preg_match('/^\s*SENHA_PAINEL\s*=/', $ln)) {
                $linhas[$i] = 'SENHA_PAINEL = ""';
            }
        }
    }
    @file_put_contents(CAMINHO_CONFIG_INI, implode("\n", $linhas), LOCK_EX);
    @chmod(CAMINHO_CONFIG_INI, 0600);
}
