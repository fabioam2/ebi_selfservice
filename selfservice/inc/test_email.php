<?php
/**
 * Teste de Conexão SMTP - Admin Panel
 *
 * Endpoint para testar conexão SMTP
 */

session_start();

// Verificar se está logado como admin
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Acesso negado'
    ]);
    exit;
}

// Carregar configurações de email
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require __DIR__ . '/../../vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/../../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->safeLoad();
    }
}

require_once __DIR__ . '/email_manager.php';

// Testar conexão
$resultado = testarConexaoSMTP();

header('Content-Type: application/json');
echo json_encode($resultado);
