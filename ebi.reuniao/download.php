<?php
/**
 * Download de arquivos de instalação do QZ Tray.
 * Serve arquivos via PHP para evitar bloqueios do servidor web e do Windows.
 *
 * Parâmetros GET:
 *   ?file=qztray  → QZ Tray + Certificados (.zip)
 *   ?file=cert    → Certificado público zipado (evita bloqueio Windows)
 */

$file = $_GET['file'] ?? 'qztray';

// Caminho base: ebi/
$baseDir = defined('INSTANCE_DIR')
    ? dirname(dirname(INSTANCE_DIR))
    : dirname(__DIR__);

switch ($file) {
    case 'cert':
        // Zipar o certificado on-the-fly para evitar bloqueio do Windows
        $certPath = __DIR__ . '/assets/signing/digital-certificate.txt';
        if (!file_exists($certPath)) {
            http_response_code(404);
            die('Certificado não encontrado.');
        }

        $tmpZip = tempnam(sys_get_temp_dir(), 'cert_') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE) !== true) {
            http_response_code(500);
            die('Erro ao criar arquivo zip.');
        }
        $zip->addFile($certPath, 'digital-certificate.txt');
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="certificado-qztray.zip"');
        header('Content-Length: ' . filesize($tmpZip));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($tmpZip);
        @unlink($tmpZip);
        exit;

    case 'qztray':
    default:
        $filePath = $baseDir . '/driveQZtray/QZTrayCertificados.zip';
        if (!file_exists($filePath)) {
            http_response_code(404);
            die('Arquivo não encontrado.');
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="QZTrayCertificados.zip"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($filePath);
        exit;
}
