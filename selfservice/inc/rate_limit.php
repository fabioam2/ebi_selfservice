<?php

/**
 * Sistema de Rate Limiting (Limitação de Taxa de Requisições)
 *
 * Protege o sistema contra abuso e spam através de:
 * - Limitação de requisições por IP
 * - Janela de tempo deslizante
 * - Limpeza automática de dados antigos
 * - Armazenamento em arquivos JSON
 *
 * @package EBI\SelfService
 */

/**
 * Verifica se um IP excedeu o limite de requisições
 *
 * Implementa um rate limiter baseado em janela deslizante (sliding window).
 * As requisições antigas são automaticamente removidas da contagem.
 *
 * @param string $ip Endereço IP a ser verificado
 * @param int $maxRequests Número máximo de requisições permitidas (padrão: 5)
 * @param int $timeWindow Janela de tempo em segundos (padrão: 3600 = 1 hora)
 * @return bool True se a requisição é permitida, false se excedeu o limite
 */
function checkRateLimit(string $ip, int $maxRequests = 5, int $timeWindow = 3600): bool {
    // Sanitizar IP para uso em nome de arquivo
    $ipSafe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $ip);

    // Diretório de dados de rate limiting
    $dataDir = __DIR__ . '/../data/';
    if (!file_exists($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $rateLimitFile = $dataDir . 'ratelimit_' . $ipSafe . '.json';

    // Ler requisições anteriores
    $requests = [];
    if (file_exists($rateLimitFile)) {
        $content = file_get_contents($rateLimitFile);
        if ($content !== false) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $requests = $decoded;
            }
        }
    }

    // Timestamp atual
    $now = time();

    // Limpar requisições antigas (fora da janela de tempo)
    $requests = array_filter($requests, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });

    // Reindexar array após filtro
    $requests = array_values($requests);

    // Verificar se excedeu o limite
    if (count($requests) >= $maxRequests) {
        // Opcional: registrar tentativa de abuso
        logRateLimitViolation($ip, count($requests), $maxRequests);
        return false; // Bloqueado
    }

    // Adicionar nova requisição
    $requests[] = $now;

    // Salvar dados atualizados
    file_put_contents($rateLimitFile, json_encode($requests), LOCK_EX);

    return true; // Permitido
}

/**
 * Obtém o número de requisições restantes para um IP
 *
 * @param string $ip Endereço IP
 * @param int $maxRequests Número máximo permitido
 * @param int $timeWindow Janela de tempo em segundos
 * @return array{remaining: int, reset_in: int} Requisições restantes e tempo até reset
 */
function getRateLimitStatus(string $ip, int $maxRequests = 5, int $timeWindow = 3600): array {
    $ipSafe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $ip);
    $rateLimitFile = __DIR__ . '/../data/ratelimit_' . $ipSafe . '.json';

    $requests = [];
    if (file_exists($rateLimitFile)) {
        $content = file_get_contents($rateLimitFile);
        if ($content !== false) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $requests = $decoded;
            }
        }
    }

    $now = time();

    // Filtrar requisições válidas
    $validRequests = array_filter($requests, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });

    $count = count($validRequests);
    $remaining = max(0, $maxRequests - $count);

    // Calcular tempo até reset (baseado na requisição mais antiga)
    $resetIn = 0;
    if (!empty($validRequests)) {
        $oldestRequest = min($validRequests);
        $resetIn = max(0, $timeWindow - ($now - $oldestRequest));
    }

    return [
        'remaining' => $remaining,
        'reset_in' => $resetIn
    ];
}

/**
 * Registra violação de rate limit em log
 *
 * @param string $ip Endereço IP que violou o limite
 * @param int $requestCount Número de requisições feitas
 * @param int $maxAllowed Número máximo permitido
 * @return void
 */
function logRateLimitViolation(string $ip, int $requestCount, int $maxAllowed): void {
    $logFile = __DIR__ . '/../data/rate_limit_violations.log';
    $timestamp = date('Y-m-d H:i:s');
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $logEntry = sprintf(
        "[%s] IP: %s | Requisições: %d/%d | User-Agent: %s\n",
        $timestamp,
        $ip,
        $requestCount,
        $maxAllowed,
        $userAgent
    );

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Limpa arquivos de rate limiting antigos (para manutenção)
 *
 * Remove arquivos de rate limiting que não foram modificados há mais de X dias.
 * Útil para executar periodicamente via cron.
 *
 * @param int $daysOld Número de dias de inatividade (padrão: 7)
 * @return int Número de arquivos removidos
 */
function cleanupOldRateLimitFiles(int $daysOld = 7): int {
    $dataDir = __DIR__ . '/../data/';
    $count = 0;

    if (!is_dir($dataDir)) {
        return 0;
    }

    $files = glob($dataDir . 'ratelimit_*.json');
    $threshold = time() - ($daysOld * 24 * 60 * 60);

    foreach ($files as $file) {
        $mtime = filemtime($file);
        if ($mtime !== false && $mtime < $threshold) {
            if (unlink($file)) {
                $count++;
            }
        }
    }

    return $count;
}

/**
 * Obtém IP real do cliente, mesmo atrás de proxy/load balancer
 *
 * @return string Endereço IP do cliente
 */
function getClientIP(): string {
    // Verificar headers de proxy (em ordem de confiança)
    $headers = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_REAL_IP',        // Nginx proxy
        'HTTP_X_FORWARDED_FOR',  // Padrão de proxy
        'REMOTE_ADDR'            // Conexão direta
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];

            // X-Forwarded-For pode conter múltiplos IPs (pegar o primeiro)
            if (strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }

            // Validar se é um IP válido
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '0.0.0.0'; // Fallback
}

/**
 * Renderiza mensagem de erro de rate limit para o usuário
 *
 * @param int $resetIn Segundos até o reset do limite
 * @return void
 */
function showRateLimitError(int $resetIn): void {
    $minutes = ceil($resetIn / 60);

    http_response_code(429); // Too Many Requests
    header('Retry-After: ' . $resetIn);

    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Limite de Requisições Excedido</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                padding: 20px;
            }
            .error-container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                padding: 40px;
                max-width: 500px;
                text-align: center;
            }
            h1 {
                color: #e74c3c;
                font-size: 48px;
                margin: 0 0 10px 0;
            }
            h2 {
                color: #333;
                font-size: 24px;
                margin: 0 0 20px 0;
            }
            p {
                color: #666;
                line-height: 1.6;
                margin: 0 0 20px 0;
            }
            .countdown {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 15px;
                font-size: 18px;
                color: #333;
                font-weight: bold;
            }
            .icon {
                font-size: 64px;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="icon">⏱️</div>
            <h1>429</h1>
            <h2>Muitas Requisições</h2>
            <p>
                Você excedeu o limite de requisições permitidas.
                Por favor, aguarde alguns minutos antes de tentar novamente.
            </p>
            <div class="countdown">
                Tente novamente em aproximadamente <?= $minutes ?> minuto<?= $minutes > 1 ? 's' : '' ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
