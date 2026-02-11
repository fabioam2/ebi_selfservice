<?php
/**
 * Autenticação: login, logout, timeout de sessão e exibição da tela de login.
 */

$mensagemLoginErro = '';
$loginPageMensagemSucesso = '';

/** Verifica se a senha informada confere (suporta hash bcrypt ou texto plano para compatibilidade). */
function senha_valida($senha_informada, $senha_config) {
    if ($senha_informada === '' || $senha_config === '') {
        return false;
    }
    if (preg_match('/^\$2[ay]\$\d{2}\$/', $senha_config)) {
        return password_verify($senha_informada, $senha_config);
    }
    return hash_equals($senha_config, $senha_informada);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tentativa_login'])) {
    csrf_validate();
    if (isset($_POST['senha_login']) && senha_valida($_POST['senha_login'], SENHA_LOGIN)) {
        $_SESSION['logado'] = true;
        $_SESSION['created_at'] = time();
        csrf_regenerate();
        header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
        exit;
    }
    $mensagemLoginErro = 'Senha incorreta.';
}

if (isset($_GET['acao']) && $_GET['acao'] === 'logout') {
    $_SESSION['logado'] = false;
    $_SESSION['logout_mensagem_sucesso'] = 'Você saiu do sistema.';
    header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
    exit;
}

if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    if (isset($_SESSION['created_at']) && (time() - $_SESSION['created_at'] > TEMPO_SESSAO)) {
        $_SESSION['logado'] = false;
        $_SESSION['logout_mensagem_sucesso'] = 'Sessão expirada. Faça login novamente.';
        header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
        exit;
    }
    $_SESSION['created_at'] = time();
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    if (isset($_SESSION['logout_mensagem_sucesso'])) {
        $loginPageMensagemSucesso = $_SESSION['logout_mensagem_sucesso'];
        unset($_SESSION['logout_mensagem_sucesso']);
    }
    require __DIR__ . '/../views/login.php';
    exit;
}
