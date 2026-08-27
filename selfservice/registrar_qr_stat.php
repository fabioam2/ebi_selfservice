<?php
require_once __DIR__ . '/inc/paths.php';
require_once __DIR__ . '/inc/db_manager.php';
require_once __DIR__ . '/inc/qr_stats.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');

function qr_stats_response(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    qr_stats_response(405, ['ok' => false]);
}

if (!qr_stats_csrf_validate($_SERVER['HTTP_X_QR_STATS_CSRF'] ?? null)) {
    qr_stats_response(403, ['ok' => false]);
}

if (!qr_stats_rate_limit()) {
    qr_stats_response(429, ['ok' => false]);
}

$rawPayload = file_get_contents('php://input');
if ($rawPayload === false || strlen($rawPayload) > 8192) {
    qr_stats_response(400, ['ok' => false]);
}

try {
    $payload = json_decode($rawPayload, true, 16, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    qr_stats_response(400, ['ok' => false]);
}

if (!is_array($payload)) {
    qr_stats_response(400, ['ok' => false]);
}

$source = $payload['source'] ?? '';
$event = [
    'comum' => $payload['comum'] ?? '',
    'cidade' => $payload['cidade'] ?? '',
    'funcao' => $payload['funcao'] ?? '',
    'criancas' => $payload['criancas'] ?? null,
];

if (!is_string($source) || !db_registrar_qr_stat($source, $event)) {
    qr_stats_response(422, ['ok' => false]);
}

qr_stats_response(201, ['ok' => true]);