<?php

function qr_stats_iniciar_sessao(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function qr_stats_csrf_token(): string {
    qr_stats_iniciar_sessao();
    if (empty($_SESSION['qr_stats_csrf_token'])) {
        $_SESSION['qr_stats_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['qr_stats_csrf_token'];
}

function qr_stats_csrf_validate(mixed $token): bool {
    return is_string($token)
        && $token !== ''
        && hash_equals(qr_stats_csrf_token(), $token);
}

function qr_stats_rate_limit(): bool {
    qr_stats_iniciar_sessao();
    $now = time();
    $events = array_values(array_filter(
        $_SESSION['qr_stats_events'] ?? [],
        static fn($timestamp): bool => is_int($timestamp) && ($now - $timestamp) < 60
    ));

    if (count($events) >= 20) {
        $_SESSION['qr_stats_events'] = $events;
        return false;
    }

    $events[] = $now;
    $_SESSION['qr_stats_events'] = $events;
    return true;
}