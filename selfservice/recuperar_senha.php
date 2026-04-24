<?php
/**
 * Recuperação de senha (auto-serviço, público).
 *
 * Fluxo:
 *  1. Usuário informa o e-mail cadastrado.
 *  2. Sistema procura em selfservice/data/selfservice_users.txt.
 *  3. Se encontrar, gera nova senha temporária, atualiza o config.ini
 *     da instância e envia por e-mail.
 *  4. Para evitar enumeração de e-mails, a resposta é SEMPRE a mesma,
 *     independente de o e-mail existir ou não.
 *
 * Proteções:
 *  - Rate limit (reusa o inc/rate_limit.php).
 *  - CSRF.
 *  - Log em selfservice/data/recuperacao_senha.log.
 */

session_start();

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// .env
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\\Dotenv') && file_exists(__DIR__ . '/../.env')) {
        Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
    }
}

require_once __DIR__ . '/inc/rate_limit.php';
require_once __DIR__ . '/inc/paths.php';
require_once __DIR__ . '/criar_instancia.php';
require_once __DIR__ . '/inc/email_manager.php';

// Rate limit — mais apertado neste endpoint: 5 tentativas / 5 min por IP.
$clientIP = getClientIP();
if (!checkRateLimit($clientIP, 5, 300)) {
    http_response_code(429);
    $status = getRateLimitStatus($clientIP, 5, 300);
    showRateLimitError($status['reset_in']);
    exit;
}

// CSRF
if (empty($_SESSION['rs_csrf'])) {
    $_SESSION['rs_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['rs_csrf'];

$mensagem = '';
$tipo = 'info';
$respostaGenerica = 'Se o e-mail informado estiver cadastrado, enviamos uma nova senha temporária para a caixa de entrada. Verifique também a pasta de spam.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenOk = hash_equals($_SESSION['rs_csrf'] ?? '', $_POST['csrf'] ?? '');
    $emailDigitado = trim((string)($_POST['email'] ?? ''));

    if (!$tokenOk) {
        $mensagem = 'Sessão expirada. Recarregue a página e tente novamente.';
        $tipo = 'danger';
    } elseif (!filter_var($emailDigitado, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Informe um e-mail válido.';
        $tipo = 'danger';
    } else {
        // Procurar usuário em selfservice_users.txt
        $dbFile = __DIR__ . '/data/selfservice_users.txt';
        $usuario = null;
        if (file_exists($dbFile)) {
            $linhas = file($dbFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($linhas as $linha) {
                $p = explode('|', $linha);
                if (count($p) >= 7 && strcasecmp(trim($p[1]), $emailDigitado) === 0) {
                    $usuario = [
                        'user_id' => $p[0],
                        'email'   => $p[1],
                        'nome'    => $p[2],
                        'cidade'  => $p[3],
                        'comum'   => $p[4],
                    ];
                    break; // primeiro registro com esse e-mail
                }
            }
        }

        $logFile = __DIR__ . '/data/recuperacao_senha.log';
        $logLinha = date('Y-m-d H:i:s') . " | IP: $clientIP | email: $emailDigitado | ";

        if ($usuario !== null) {
            try {
                $senhaTemp = gerarSenhaTemporaria(12);
                $reset = redefinirSenhaInstancia($usuario['user_id'], $senhaTemp);

                if (!$reset['sucesso']) {
                    $logLinha .= 'falha_reset: ' . ($reset['erro'] ?? '?');
                } else {
                    // Monta link da instância
                    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                             . '://' . $_SERVER['HTTP_HOST'];
                    $rootPath = dirname(dirname($_SERVER['PHP_SELF'])); // .../dev2
                    $pathPrefix = ($rootPath === '/' || $rootPath === '\\') ? '' : $rootPath;
                    $linkInstancia = $baseUrl . $pathPrefix . '/ebi/i/' . $usuario['user_id'] . '/index.php';

                    $mail = enviarEmailResetSenha($usuario['email'], $usuario['nome'], $linkInstancia, $senhaTemp);
                    $logLinha .= 'reset_ok | email: ' . ($mail['sucesso'] ? 'enviado' : 'falha: ' . ($mail['erro'] ?? '?'));
                }
            } catch (Throwable $e) {
                $logLinha .= 'excecao: ' . $e->getMessage();
            }
        } else {
            $logLinha .= 'email_nao_encontrado';
        }

        @file_put_contents($logFile, $logLinha . "\n", FILE_APPEND | LOCK_EX);

        // Sempre mostra a mesma mensagem (não vazar se e-mail existe).
        $mensagem = $respostaGenerica;
        $tipo = 'success';
    }
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha — EBI SelfService</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;
               display: flex; flex-direction: column; align-items: center; justify-content: center;
               font-family: 'Inter', sans-serif; padding: 20px; }
        .card-rec { background: #fff; padding: 36px; border-radius: 14px;
                    box-shadow: 0 10px 40px rgba(0,0,0,.3); width: 100%; max-width: 460px; }
        .card-rec h2 { color: #667eea; font-weight: 700; text-align: center; margin-bottom: 6px; }
        .card-rec p.sub { color: #6c757d; text-align: center; margin-bottom: 22px; }
        .btn-rec { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                   border: none; color: #fff; font-weight: 600; }
        .btn-rec:hover { filter: brightness(1.05); color: #fff; }
        .voltar { color: #fff; text-decoration: underline; margin-top: 14px; display: inline-block; }
    </style>
</head>
<body>
    <div class="card-rec">
        <h2><i class="fas fa-key mr-2"></i>Recuperar Senha</h2>
        <p class="sub">Informe o e-mail usado no cadastro. Enviaremos uma nova senha temporária.</p>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo; ?>"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope mr-1"></i>E-mail cadastrado</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="seu@email.com" required autofocus
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-rec btn-block">
                <i class="fas fa-paper-plane mr-1"></i>Enviar nova senha
            </button>
        </form>
    </div>

    <a class="voltar" href="./">← Voltar ao cadastro</a>
</body>
</html>
