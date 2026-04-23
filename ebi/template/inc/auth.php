<?php
/**
 * Autenticação: login, logout e exibição da tela de login.
 */

$mensagemLoginErro = '';
$loginPageMensagemSucesso = '';

// Rate limiting simples por sessão
if (!isset($_SESSION['tentativas_login'])) {
    $_SESSION['tentativas_login'] = 0;
    $_SESSION['ultima_tentativa_login'] = 0;
}
$maxTent = (int)($config['SEGURANCA']['MAX_TENTATIVAS_LOGIN'] ?? 5);
$tempoBloq = (int)($config['SEGURANCA']['TEMPO_BLOQUEIO'] ?? 300);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tentativa_login'])) {
    csrf_validate();

    if ($_SESSION['tentativas_login'] >= $maxTent
        && (time() - $_SESSION['ultima_tentativa_login']) < $tempoBloq) {
        $restante = $tempoBloq - (time() - $_SESSION['ultima_tentativa_login']);
        $mensagemLoginErro = 'Muitas tentativas. Aguarde ' . ceil($restante) . ' segundos.';
    } elseif (verificar_senha_admin($_POST['senha_login'] ?? '')) {
        // Sucesso: regenera sessão para evitar fixation
        session_regenerate_id(true);
        $_SESSION['logado'] = true;
        $_SESSION['ultimo_acesso'] = time();
        $_SESSION['tentativas_login'] = 0;
        csrf_regenerate();
        header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
        exit;
    } else {
        $_SESSION['tentativas_login']++;
        $_SESSION['ultima_tentativa_login'] = time();
        $mensagemLoginErro = 'Senha incorreta.';
    }
}

if (isset($_GET['acao']) && $_GET['acao'] === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    $_SESSION['logout_mensagem_sucesso'] = 'Você saiu do sistema.';
    header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
    exit;
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    if (isset($_SESSION['logout_mensagem_sucesso'])) {
        $loginPageMensagemSucesso = $_SESSION['logout_mensagem_sucesso'];
        unset($_SESSION['logout_mensagem_sucesso']);
    }
    require __DIR__ . '/../views/login.php';
    exit;
}

