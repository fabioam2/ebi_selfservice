<?php
/**
 * Bootstrap para Módulo Saida: carrega configuração compartilhada do EBI.
 * Reutiliza o mesmo config.ini do sistema principal (template/).
 */

// Carrega config.ini do diretório template (pai de saida)
$config_file = __DIR__ . '/../../config.ini';
if (!file_exists($config_file)) {
    die("Erro: Arquivo de configuração não encontrado em: " . htmlspecialchars($config_file));
}

$config = parse_ini_file($config_file, true, INI_SCANNER_TYPED);

if (!isset($config['GERAL'], $config['SEGURANCA'])) {
    die("Erro: Falta uma ou mais seções ([GERAL], [SEGURANCA]) no arquivo de configuração.");
}

$baseDir = dirname(dirname(__DIR__));
$data_file_path = $baseDir . $config['GERAL']['ARQUIVO_DADOS'];

// Define constantes compartilhadas com EBI
define('ARQUIVO_DADOS', $data_file_path);
define('DELIMITADOR', $config['GERAL']['DELIMITADOR']);
define('MAX_BACKUPS', $config['GERAL']['MAX_BACKUPS']);
define('SENHA_PAINEL_HASH', (string)($config['SEGURANCA']['SENHA_PAINEL_HASH'] ?? ''));
define('SENHA_PAINEL', (string)($config['SEGURANCA']['SENHA_PAINEL'] ?? '')); // legado
define('CAMINHO_CONFIG_INI', $config_file);
define('TEMPO_SESSAO', (int)($config['SEGURANCA']['TEMPO_SESSAO'] ?? 1800));
define('MAX_TENTATIVAS_LOGIN', (int)($config['SEGURANCA']['MAX_TENTATIVAS_LOGIN'] ?? 5));
define('TEMPO_BLOQUEIO', (int)($config['SEGURANCA']['TEMPO_BLOQUEIO'] ?? 300));

// Define arquivo de dados para saidas (fora do public_html recomendado)
$saida_dir = dirname($data_file_path);
define('ARQUIVO_SAIDAS', $saida_dir . DIRECTORY_SEPARATOR . 'saidas.log');

if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = [
        'lifetime' => 0, 'path' => '/', 'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, 'samesite' => 'Lax',
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

// Headers de segurança HTTP
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
if (isset($_SESSION['logado_saida']) && $_SESSION['logado_saida'] === true) {
    if (isset($_SESSION['ultimo_acesso_saida']) && (time() - $_SESSION['ultimo_acesso_saida']) > TEMPO_SESSAO) {
        $_SESSION['logado_saida'] = false;
        $_SESSION['logout_mensagem'] = 'Sua sessão expirou por inatividade. Faça login novamente.';
        unset($_SESSION['ultimo_acesso_saida']);
    } else {
        $_SESSION['ultimo_acesso_saida'] = time();
    }
}

// --- FUNÇÕES DE SANITIZAÇÃO (mesmo padrão do EBI) ---

function sanitize_for_html($string) {
    return htmlspecialchars(trim((string)($string ?? '')), ENT_QUOTES, 'UTF-8');
}

function sanitize_for_file($string) {
    return str_replace(DELIMITADOR, '-', trim($string ?? ''));
}

// --- CSRF ---

function csrf_token() {
    if (empty($_SESSION['csrf_token_saida'])) {
        $_SESSION['csrf_token_saida'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token_saida'];
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
    $_SESSION['csrf_token_saida'] = bin2hex(random_bytes(32));
}

/**
 * Verifica senha do painel/saída.
 * Aceita bcrypt em SENHA_PAINEL_HASH ou legado em SENHA_PAINEL (texto plano).
 */
function verificar_senha_painel($senhaDigitada) {
    $senhaDigitada = (string)$senhaDigitada;
    if ($senhaDigitada === '') return false;

    $hash = defined('SENHA_PAINEL_HASH') ? SENHA_PAINEL_HASH : '';
    if ($hash !== '' && preg_match('/^\$2[aby]\$/', $hash)) {
        return password_verify($senhaDigitada, $hash);
    }
    $plain = defined('SENHA_PAINEL') ? SENHA_PAINEL : '';
    if ($plain !== '' && hash_equals($plain, $senhaDigitada)) {
        migrar_senha_painel_para_hash($senhaDigitada);
        return true;
    }
    return false;
}

function migrar_senha_painel_para_hash($senhaPlana) {
    if (!defined('CAMINHO_CONFIG_INI')) return;
    $arq = CAMINHO_CONFIG_INI;
    if (!is_writable($arq)) return;
    $novoHash = password_hash($senhaPlana, PASSWORD_BCRYPT, ['cost' => 12]);
    $conteudo = file_get_contents($arq);
    if ($conteudo === false) return;
    $linhas = preg_split("/\r?\n/", $conteudo);
    $dentroSeg = false;
    $setHash = false;
    foreach ($linhas as $i => $ln) {
        if (preg_match('/^\s*\[([^\]]+)\]/', $ln, $m)) {
            if ($dentroSeg && !$setHash) {
                array_splice($linhas, $i, 0, ['SENHA_PAINEL_HASH = "' . $novoHash . '"']);
                $setHash = true;
            }
            $dentroSeg = strcasecmp(trim($m[1]), 'SEGURANCA') === 0;
            continue;
        }
        if ($dentroSeg) {
            if (preg_match('/^\s*SENHA_PAINEL_HASH\s*=/', $ln)) {
                $linhas[$i] = 'SENHA_PAINEL_HASH = "' . $novoHash . '"';
                $setHash = true;
            } elseif (preg_match('/^\s*SENHA_PAINEL\s*=/', $ln)) {
                $linhas[$i] = 'SENHA_PAINEL = ""';
            }
        }
    }
    @file_put_contents($arq, implode("\n", $linhas), LOCK_EX);
    @chmod($arq, 0600);
}

// --- FUNÇÕES DO EBI (reutilizadas) ---

function lerTodosCadastros($caminhoArquivo) {
    $cadastros = [];
    if (file_exists($caminhoArquivo) && filesize($caminhoArquivo) > 0) {
        $linhasFile = file($caminhoArquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($linhasFile === false) return [];
        foreach ($linhasFile as $linha) {
            if (isset($linha[0]) && $linha[0] === '#') continue;
            $dados = explode(DELIMITADOR, $linha);
            if (count($dados) >= 9) {
                $id = intval(trim($dados[0]));
                $cadastros[$id] = [
                    'id' => $id,
                    'nomeCrianca'     => $dados[1] ?? '',
                    'nomeResponsavel' => $dados[2] ?? '',
                    'telefone'        => $dados[3] ?? '',
                    'idade'           => $dados[4] ?? '',
                    'comum'           => $dados[5] ?? '',
                    'statusImpresso'  => $dados[6] ?? 'N',
                    'portaria'        => strtoupper(trim($dados[7] ?? '')),
                    'cod_resp'        => $dados[8] ?? ''
                ];
            }
        }
    }
    return $cadastros;
}
