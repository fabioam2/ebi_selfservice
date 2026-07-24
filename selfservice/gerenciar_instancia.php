<?php
/**
 * Confirma uma exclusão em quarentena ou recuperação enviada por e-mail.
 * Links GET apenas exibem a confirmação; toda ação é feita por POST com CSRF.
 */

session_start();

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/../.env')) {
        Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
    }
}

require_once __DIR__ . '/inc/paths.php';
require_once __DIR__ . '/inc/db_manager.php';
require_once __DIR__ . '/inc/instance_lifecycle.php';
require_once __DIR__ . '/inc/email_manager.php';

if (empty($_SESSION['instance_lifecycle_csrf'])) {
    $_SESSION['instance_lifecycle_csrf'] = bin2hex(random_bytes(32));
}

$acao = (string)($_REQUEST['acao'] ?? '');
$token = (string)($_REQUEST['token'] ?? '');
$acaoValida = in_array($acao, ['quarantine', 'recover'], true) ? $acao : '';
$registroToken = $acaoValida !== '' ? lifecycle_buscar_token_acao($token, $acaoValida) : null;
$mensagem = '';
$tipo = 'info';
$resultadoLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (!$registroToken || !hash_equals($_SESSION['instance_lifecycle_csrf'], $csrf)) {
        $mensagem = 'Esta solicitação expirou ou não é mais válida. Peça um novo link por e-mail.';
        $tipo = 'danger';
        $registroToken = null;
    } else {
        $userId = (string)$registroToken['user_id'];
        $usuario = db_buscar_usuario_por_id($userId);

        if ($acaoValida === 'quarantine') {
            $resultado = lifecycle_colocar_em_quarentena($userId, 'user_request');
            if (!empty($resultado['sucesso'])) {
                lifecycle_consumir_token_acao($token, $acaoValida);
                if ($usuario !== null) {
                    enviarEmailQuarentenaInstancia(
                        (string)$usuario['email'],
                        (string)$usuario['nome'],
                        (string)$resultado['recuperacao_url'],
                        (string)$resultado['expira_em']
                    );
                }
                $mensagem = 'A instância foi colocada em quarentena. Enviamos um link de recuperação para o e-mail cadastrado.';
                $tipo = 'success';
                $registroToken = null;
            } else {
                $mensagem = (string)($resultado['erro'] ?? 'Não foi possível colocar a instância em quarentena.');
                $tipo = 'danger';
            }
        } else {
            $resultado = lifecycle_restaurar_quarentena($userId);
            if (!empty($resultado['sucesso'])) {
                lifecycle_consumir_token_acao($token, $acaoValida);
                $resultadoLink = (string)($resultado['link'] ?? '');
                $mensagem = 'A instância foi recuperada e já pode ser acessada novamente.';
                $tipo = 'success';
                $registroToken = null;
            } else {
                $mensagem = (string)($resultado['erro'] ?? 'Não foi possível recuperar a instância.');
                $tipo = 'danger';
            }
        }
    }

    $_SESSION['instance_lifecycle_csrf'] = bin2hex(random_bytes(32));
}

$titulo = $acaoValida === 'recover' ? 'Recuperar Instância' : 'Excluir Instância';
$descricao = $acaoValida === 'recover'
    ? 'A instância voltará a ficar disponível com os dados preservados.'
    : 'A instância será retirada do ar e ficará em quarentena por um prazo de recuperação.';
$confirmacao = $acaoValida === 'recover' ? 'Recuperar instância' : 'Colocar em quarentena';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo); ?> - EBI Self-Service</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; background:#edf5f7; color:#173146; font-family:Arial,sans-serif; }
        .lifecycle-card { width:100%; max-width:540px; border:0; border-radius:10px; box-shadow:0 18px 42px rgba(15,44,67,.18); }
        .lifecycle-card .card-body { padding:34px; }
    </style>
</head>
<body>
    <main class="card lifecycle-card">
        <div class="card-body text-center">
            <i class="fas <?php echo $acaoValida === 'recover' ? 'fa-life-ring text-success' : 'fa-trash-alt text-danger'; ?> fa-3x mb-3"></i>
            <h1 class="h3 mb-3"><?php echo htmlspecialchars($titulo); ?></h1>

            <?php if ($mensagem !== ''): ?>
                <div class="alert alert-<?php echo htmlspecialchars($tipo); ?> text-left"><?php echo htmlspecialchars($mensagem); ?></div>
            <?php endif; ?>

            <?php if ($registroToken !== null): ?>
                <p class="text-muted mb-4"><?php echo htmlspecialchars($descricao); ?></p>
                <?php if ($acaoValida === 'quarantine'): ?>
                    <div class="alert alert-warning text-left small">Após a confirmação, a recuperação continuará disponível pelo prazo configurado. A exclusão definitiva só ocorre após esse período.</div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="acao" value="<?php echo htmlspecialchars($acaoValida); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['instance_lifecycle_csrf']); ?>">
                    <button type="submit" class="btn btn-<?php echo $acaoValida === 'recover' ? 'success' : 'danger'; ?> btn-block">
                        <i class="fas <?php echo $acaoValida === 'recover' ? 'fa-undo' : 'fa-shield-alt'; ?> mr-1"></i><?php echo htmlspecialchars($confirmacao); ?>
                    </button>
                </form>
            <?php elseif ($resultadoLink !== ''): ?>
                <a class="btn btn-success btn-block" href="<?php echo htmlspecialchars($resultadoLink); ?>">Acessar instância recuperada</a>
            <?php elseif ($mensagem === ''): ?>
                <div class="alert alert-danger text-left">Este link é inválido, já foi usado ou expirou. Solicite um novo e-mail de acesso.</div>
            <?php endif; ?>

            <a class="btn btn-link mt-3" href="selfservice.php">Voltar ao cadastro</a>
        </div>
    </main>
    <div class="text-center mt-4 mb-2" style="font-size:9px;color:#6b7280;opacity:0.6">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</body>
</html>