<?php
/**
 * Download forçado do arquivo QZ Tray + Certificados.
 * Serve o arquivo via PHP para evitar problemas com configuração do servidor web.
 */

// Caminho do arquivo relativo ao diretório ebi/
$baseDir = defined('INSTANCE_DIR')
    ? dirname(dirname(INSTANCE_DIR))  // ebi/i/user_XXX → ebi/
    : dirname(__DIR__);               // ebi/template → ebi/

$filePath = $baseDir . '/driveQZtray/QZTrayCertificados.zip';

if (!file_exists($filePath)) {
    http_response_code(404);
    die('Arquivo não encontrado.');
}

$fileSize = filesize($filePath);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="QZTrayCertificados.zip"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($filePath);
exit;
