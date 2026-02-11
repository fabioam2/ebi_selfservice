<?php
/**
 * Autenticação: login, logout e exibição da tela de login.
 */

$mensagemLoginErro = '';
$loginPageMensagemSucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tentativa_login'])) {
    csrf_validate();
    if (isset($_POST['senha_login']) && $_POST['senha_login'] === SENHA_LOGIN) {
        $_SESSION['logado'] = true;
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

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    if (isset($_SESSION['logout_mensagem_sucesso'])) {
        $loginPageMensagemSucesso = $_SESSION['logout_mensagem_sucesso'];
        unset($_SESSION['logout_mensagem_sucesso']);
    }
    require __DIR__ . '/../views/login.php';
    exit;
}
