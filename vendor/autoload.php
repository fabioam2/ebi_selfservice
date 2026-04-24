<?php
/**
 * Autoload mínimo (fallback sem Composer).
 *
 * Permite usar PHPMailer sem `composer install`. Quando o Composer for
 * executado, ele sobrescreve este arquivo automaticamente com seu próprio
 * autoload (mais completo, com suporte a Dotenv, Monolog, etc.).
 *
 * Se o arquivo do Composer existir (vendor/composer/autoload_real.php),
 * delegamos para ele em vez de usar este fallback.
 */

if (file_exists(__DIR__ . '/composer/autoload_real.php')) {
    // Composer instalado: deixar o autoload real assumir.
    require_once __DIR__ . '/composer/autoload_real.php';
    return;
}

spl_autoload_register(function ($class) {
    // PHPMailer\PHPMailer\*
    $prefix = 'PHPMailer\\PHPMailer\\';
    if (strpos($class, $prefix) === 0) {
        $rel = substr($class, strlen($prefix));
        $file = __DIR__ . '/phpmailer/phpmailer/src/' . str_replace('\\', '/', $rel) . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});
