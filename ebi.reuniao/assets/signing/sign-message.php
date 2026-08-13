<?php
// sign-message.php
header('Content-Type: text/plain');

$privateKeyPath = __DIR__ . '/private-key.pem';
$request        = $_GET['request'] ?? '';

if (empty($request)) {
    http_response_code(400);
    exit('Requisição vazia');
}

if (!is_file($privateKeyPath) || !is_readable($privateKeyPath)) {
    http_response_code(503);
    exit('Assinatura não configurada no servidor.');
}

$privateKey = file_get_contents($privateKeyPath);
$pkey = openssl_pkey_get_private($privateKey);
if (!$pkey) {
    http_response_code(500);
    exit('Erro ao carregar chave privada');
}

openssl_sign($request, $signature, $pkey, OPENSSL_ALGO_SHA512);
echo base64_encode($signature);
