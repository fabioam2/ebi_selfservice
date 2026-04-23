<?php
/**
 * Router para `php -S` que emula proteções de .htaccess.
 * Uso (a partir da raiz do workspace):
 *   php -S 127.0.0.1:8080 -t . test-env/router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = __DIR__ . '/..' . $uri;
$real = realpath($path);
$base = realpath(__DIR__ . '/..');

// Impede saída do diretório raiz (path traversal)
if ($real !== false && strpos($real, $base) !== 0) {
    http_response_code(403);
    echo "403 Forbidden (fora da raiz)";
    return true;
}

// Bloqueia arquivos sensíveis — replica o .htaccess
$blockedExt = ['ini', 'txt', 'log', 'bak', 'sql', 'sqlite', 'env', 'md', 'htaccess'];
$basename = basename($uri);

// Arquivos ocultos (.env, .lastaccess, .htaccess, .instalado...)
if ($basename !== '' && $basename[0] === '.') {
    http_response_code(403);
    echo "403 Forbidden (arquivo oculto)";
    return true;
}

// Extensão proibida
$ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
if (in_array($ext, $blockedExt, true)) {
    http_response_code(403);
    echo "403 Forbidden (extensão bloqueada: .$ext)";
    return true;
}

// Backups rotativos .bkp.N
if (preg_match('/\.bkp\.\d+$/', $basename)) {
    http_response_code(403);
    echo "403 Forbidden (backup)";
    return true;
}

// Pastas internas sensíveis
if (preg_match('#^/selfservice/data/#', $uri) || preg_match('#^/selfservice/instances/[^/]+/config/#', $uri)) {
    http_response_code(403);
    echo "403 Forbidden (pasta interna)";
    return true;
}

// Se existe arquivo estático, deixa o servidor embutido servir.
if ($real !== false && is_file($real)) {
    return false;
}

// Raiz — mostra menu de teste
if ($uri === '/' || $uri === '') {
    header('Content-Type: text/html; charset=utf-8');
    echo file_get_contents(__DIR__ . '/index.html');
    return true;
}

http_response_code(404);
echo "404 Not Found: $uri";
return true;
