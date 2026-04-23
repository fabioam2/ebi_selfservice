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

define('SENHA_ADMIN_HASH', (string)($config['SEGURANCA']['SENHA_ADMIN_HASH'] ?? ''));
define('SENHA_ADMIN_REAL', (string)($config['SEGURANCA']['SENHA_ADMIN_REAL'] ?? '')); // legado (texto plano)
define('SENHA_LOGIN', SENHA_ADMIN_REAL); // compat legado
define('CAMINHO_CONFIG_INI', $config_file);

define('PRINTER_NAME', $config['IMPRESSORA_ZPL']['PRINTER_NAME'] ?? 'ZDesigner 105SL');
define('PALAVRA_CONTADOR_COMUM', $config['IMPRESSORA_ZPL']['PALAVRA_CONTADOR_COMUM'] ?? 'bonfim');
define('LISTA_PALAVRAS_CONTADOR_COMUM', $config['IMPRESSORA_ZPL']['LISTA_PALAVRAS_CONTADOR_COMUM'] ?? 'parque, parqui, par que');
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
    // Hardening de cookie da sessão
    $cookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($cookieParams);
    } else {
        session_set_cookie_params(
            $cookieParams['lifetime'], $cookieParams['path'],
            $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']
        );
    }
    session_start();
}

// Headers de segurança HTTP (best-effort; só envia se ainda não há saída)
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

/**
 * Verifica uma senha (texto plano) contra as credenciais configuradas.
 * Aceita hash bcrypt em SENHA_ADMIN_HASH; como fallback compara com SENHA_ADMIN_REAL
 * (modo legado). Se o legado for usado, migra automaticamente para hash gravando no config.ini.
 *
 * @param string $senhaDigitada
 * @return bool
 */
function verificar_senha_admin($senhaDigitada) {
    $senhaDigitada = (string)$senhaDigitada;
    if ($senhaDigitada === '') return false;

    $hash = defined('SENHA_ADMIN_HASH') ? SENHA_ADMIN_HASH : '';
    if ($hash !== '' && preg_match('/^\$2[aby]\$/', $hash)) {
        return password_verify($senhaDigitada, $hash);
    }

    // Legado — texto plano (somente se o admin não migrou ainda).
    $plain = defined('SENHA_ADMIN_REAL') ? SENHA_ADMIN_REAL : '';
    if ($plain !== '' && hash_equals($plain, $senhaDigitada)) {
        // Migra automaticamente para hash.
        migrar_senha_legada_para_hash('SENHA_ADMIN_HASH', 'SENHA_ADMIN_REAL', $senhaDigitada);
        return true;
    }
    return false;
}

/**
 * Verifica uma senha para o painel/saída (similar ao admin, mas usa SENHA_PAINEL_HASH).
 */
function verificar_senha_painel($senhaDigitada) {
    $senhaDigitada = (string)$senhaDigitada;
    if ($senhaDigitada === '') return false;

    global $config;
    $hash = (string)($config['SEGURANCA']['SENHA_PAINEL_HASH'] ?? '');
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

/**
 * Grava hash bcrypt no config.ini e limpa a chave legada de texto plano.
 * Operação best-effort: se não houver permissão de escrita, apenas ignora.
 */
function migrar_senha_legada_para_hash($chaveHash, $chaveLegado, $senhaPlana) {
    if (!defined('CAMINHO_CONFIG_INI')) return;
    $arq = CAMINHO_CONFIG_INI;
    if (!is_writable($arq)) return;

    $novoHash = password_hash($senhaPlana, PASSWORD_BCRYPT, ['cost' => 12]);
    $conteudo = file_get_contents($arq);
    if ($conteudo === false) return;

    // Atualiza/insere chaves dentro do bloco [SEGURANCA]
    $linhas = preg_split("/\r?\n/", $conteudo);
    $dentroSeg = false;
    $setHash = false;
    $setLegado = false;
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
                $setHash = true;
            } elseif (preg_match('/^\s*' . preg_quote($chaveLegado, '/') . '\s*=/', $ln)) {
                $linhas[$i] = $chaveLegado . ' = ""';
                $setLegado = true;
            }
        }
    }
    if (!$setHash) {
        // Se não havia seção [SEGURANCA], anexa uma.
        $linhas[] = '[SEGURANCA]';
        $linhas[] = $chaveHash . ' = "' . $novoHash . '"';
    }
    @file_put_contents($arq, implode("\n", $linhas), LOCK_EX);
    @chmod($arq, 0600);
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
