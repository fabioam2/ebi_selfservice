<?php
// sign-message.php
header('Content-Type: text/plain');

$privateKeyPath = __DIR__ . '/private-key.pem';
$privateKey     = file_get_contents($privateKeyPath);
$request        = $_GET['request'] ?? '';

if (empty($request)) {
    http_response_code(400);
    exit('Requisição vazia');
}

$pkey = openssl_pkey_get_private($privateKey);
if (!$pkey) {
    http_response_code(500);
    exit('Erro ao carregar chave privada');
}

openssl_sign($request, $signature, $pkey, OPENSSL_ALGO_SHA512);
echo base64_encode($signature);
