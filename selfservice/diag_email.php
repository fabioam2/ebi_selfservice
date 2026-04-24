<?php
/**
 * Diagnóstico completo de envio de email (PHPMailer).
 *
 * Acesse após login no admin:
 *   /selfservice/diag_email.php             -> só diagnóstico
 *   /selfservice/diag_email.php?send=1      -> envia email de teste para o admin
 *
 * Tudo é registrado em selfservice/data/email_debug.log para análise posterior.
 */

session_start();

// --- Auth: precisa estar logado como admin ---
require_once __DIR__ . '/inc/paths.php';

if (empty($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    http_response_code(403);
    echo "<h3>403 — Acesso restrito</h3><p>Faça login no <a href='admin.php'>painel admin</a> antes.</p>";
    exit;
}

// --- Carregar .env e PHPMailer ---
$rootDir = realpath(__DIR__ . '/..');
$autoload = $rootDir . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
    if (class_exists('Dotenv\\Dotenv') && file_exists($rootDir . '/.env')) {
        Dotenv\Dotenv::createImmutable($rootDir)->safeLoad();
    } elseif (file_exists($rootDir . '/.env')) {
        // .env simples sem Dotenv: ler e popular $_ENV
        foreach (file($rootDir . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
            $linha = trim($linha);
            if ($linha === '' || $linha[0] === '#') continue;
            if (strpos($linha, '=') === false) continue;
            [$k, $v] = explode('=', $linha, 2);
            $k = trim($k); $v = trim($v);
            // remover aspas
            if (strlen($v) >= 2 && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
                $v = substr($v, 1, -1);
            }
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
            putenv("$k=$v");
        }
    }
}

require_once __DIR__ . '/inc/email_manager.php';

// --- Captura PHP errors/warnings durante esse script ---
$logFile = __DIR__ . '/data/email_debug.log';
@mkdir(dirname($logFile), 0755, true);
ini_set('log_errors', '1');
ini_set('error_log', $logFile);
error_reporting(E_ALL);

$marca = date('Y-m-d H:i:s');
$sessao = bin2hex(random_bytes(4));
$logBuffer = [];
$log = function (string $msg) use (&$logBuffer, $logFile, $marca, $sessao) {
    $linha = "[$marca][$sessao] $msg";
    $logBuffer[] = $linha;
    @file_put_contents($logFile, $linha . "\n", FILE_APPEND | LOCK_EX);
};

$log("=== INICIO DIAGNOSTICO EMAIL ===");
$log("PHP: " . PHP_VERSION . " | SAPI: " . PHP_SAPI . " | OS: " . PHP_OS);

// --- Checagens ---
$checks = [];
$add = function (string $rotulo, bool $ok, string $detalhe = '') use (&$checks, $log) {
    $checks[] = compact('rotulo', 'ok', 'detalhe');
    $log(($ok ? "[OK] " : "[FAIL] ") . $rotulo . ($detalhe ? " | $detalhe" : ''));
};

$add('PHP >= 7.4', version_compare(PHP_VERSION, '7.4', '>='), 'versao=' . PHP_VERSION);
$add('Extensão openssl',  extension_loaded('openssl'));
$add('Extensão mbstring', extension_loaded('mbstring'));
$add('Extensão curl',     extension_loaded('curl'));
$add('vendor/autoload.php presente', file_exists($autoload), $autoload);
$add('Classe PHPMailer carregável',
    class_exists('PHPMailer\\PHPMailer\\PHPMailer'),
    class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? 'versão ' . (PHPMailer\PHPMailer\PHPMailer::VERSION ?? '?') : ''
);
$add('.env presente',           file_exists($rootDir . '/.env'), $rootDir . '/.env');

$cfg = function_exists('carregarConfigEmail') ? carregarConfigEmail() : [];
$add('EMAIL_ENABLED=true',      !empty($cfg['habilitado']));
$add('SMTP_HOST configurado',   !empty($cfg['smtp_host']),  $cfg['smtp_host'] ?? '');
$add('SMTP_USER configurado',   !empty($cfg['smtp_user']),  $cfg['smtp_user'] ?? '');
$add('SMTP_PASSWORD presente',  !empty($cfg['smtp_password']), 'tamanho=' . strlen($cfg['smtp_password'] ?? ''));
$add('SMTP_PORT plausível',     !empty($cfg['smtp_port']) && in_array((int)$cfg['smtp_port'], [25, 465, 587, 2525], true),
                                'porta=' . ($cfg['smtp_port'] ?? '?'));
$add('proc_open habilitado',    function_exists('proc_open'));

// --- Teste de conexão SMTP ---
$resultadoConn = null;
$resultadoEnvio = null;

if (!empty($cfg['habilitado']) && !empty($cfg['smtp_host']) && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    $log("Testando conexao SMTP...");
    try {
        $resultadoConn = testarConexaoSMTP();
        $log("Resultado conexao: " . json_encode($resultadoConn, JSON_UNESCAPED_UNICODE));
    } catch (Throwable $e) {
        $resultadoConn = ['sucesso' => false, 'mensagem' => 'Exception: ' . $e->getMessage()];
        $log("Excecao na conexao: " . $e->getMessage());
    }
}

// --- Envio de teste (se ?send=1) ---
if (isset($_GET['send']) && $_GET['send'] == '1') {
    $destinatario = $_GET['to'] ?? ($cfg['email_from'] ?? $cfg['smtp_user'] ?? '');
    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        $resultadoEnvio = ['sucesso' => false, 'mensagem' => "Destinatario invalido: $destinatario"];
        $log($resultadoEnvio['mensagem']);
    } else {
        $log("Enviando email de teste para $destinatario ...");
        try {
            // Usa enviarEmailAcesso como prova (formato HTML completo)
            $r = enviarEmailAcesso(
                $destinatario,
                'Diagnóstico',
                'https://exemplo.com/dev2/diagnostico',
                'Cidade-Teste',
                'Comum-Teste'
            );
            $resultadoEnvio = $r;
            $log("Resultado envio: " . json_encode($r, JSON_UNESCAPED_UNICODE));
        } catch (Throwable $e) {
            $resultadoEnvio = ['sucesso' => false, 'erro' => 'Exception: ' . $e->getMessage()];
            $log("Excecao no envio: " . $e->getMessage());
        }
    }
}

$log("=== FIM ===");

// Tail do log para exibição
$tail = '';
if (file_exists($logFile)) {
    $linhasArq = @file($logFile, FILE_IGNORE_NEW_LINES) ?: [];
    $ultimas = array_slice($linhasArq, -120);
    $tail = implode("\n", $ultimas);
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Diagnóstico de Email</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
  body { background: #f5f6fa; padding: 30px; font-family: 'Inter', sans-serif; }
  .container { max-width: 920px; }
  pre { background: #1e1e1e; color: #d4d4d4; padding: 14px; border-radius: 6px;
        font-size: .8rem; max-height: 400px; overflow: auto; }
  .badge-yes { background: #28a745; color: #fff; }
  .badge-no  { background: #dc3545; color: #fff; }
</style>
</head>
<body>
<div class="container">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h2><i class="fas fa-stethoscope mr-2"></i>Diagnóstico de Envio de Email</h2>
      <p class="text-muted mb-0">Sessão: <code><?php echo $sessao; ?></code> · <?php echo $marca; ?></p>
    </div>
    <a href="admin.php?page=settings" class="btn btn-sm btn-outline-secondary">← Voltar ao admin</a>
  </div>

  <div class="card mb-3">
    <div class="card-header bg-primary text-white"><strong>Checagens de ambiente</strong></div>
    <table class="table table-sm mb-0">
      <thead class="thead-light"><tr><th>Item</th><th>Status</th><th>Detalhe</th></tr></thead>
      <tbody>
        <?php foreach ($checks as $c): ?>
          <tr>
            <td><?php echo htmlspecialchars($c['rotulo']); ?></td>
            <td><span class="badge <?php echo $c['ok'] ? 'badge-yes' : 'badge-no'; ?>">
                <?php echo $c['ok'] ? 'OK' : 'FALHA'; ?>
            </span></td>
            <td><code><?php echo htmlspecialchars($c['detalhe']); ?></code></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($resultadoConn): ?>
    <div class="card mb-3">
      <div class="card-header <?php echo $resultadoConn['sucesso'] ? 'bg-success' : 'bg-danger'; ?> text-white">
        <strong>Conexão SMTP:</strong> <?php echo $resultadoConn['sucesso'] ? 'sucesso' : 'falha'; ?>
      </div>
      <div class="card-body">
        <?php echo htmlspecialchars($resultadoConn['mensagem'] ?? ''); ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($resultadoEnvio): ?>
    <div class="card mb-3">
      <div class="card-header <?php echo !empty($resultadoEnvio['sucesso']) ? 'bg-success' : 'bg-danger'; ?> text-white">
        <strong>Envio de teste:</strong> <?php echo !empty($resultadoEnvio['sucesso']) ? 'enviado' : 'falhou'; ?>
      </div>
      <div class="card-body">
        <pre><?php echo htmlspecialchars(json_encode($resultadoEnvio, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
      </div>
    </div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-header bg-secondary text-white"><strong>Enviar email de teste</strong></div>
    <div class="card-body">
      <form method="get" class="form-inline">
        <input type="hidden" name="send" value="1">
        <input type="email" name="to" class="form-control form-control-sm mr-2"
               placeholder="destinatario@exemplo.com"
               value="<?php echo htmlspecialchars($cfg['smtp_user'] ?? ''); ?>" required>
        <button type="submit" class="btn btn-sm btn-primary">
          <i class="fas fa-paper-plane mr-1"></i>Enviar email de teste
        </button>
      </form>
      <small class="text-muted d-block mt-2">
        Usa o template de "acesso à instância". A saída e os erros vão para o log abaixo.
      </small>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header bg-dark text-white">
      <strong>Log:</strong> <code>selfservice/data/email_debug.log</code> (últimas 120 linhas)
    </div>
    <div class="card-body p-2">
      <pre><?php echo htmlspecialchars($tail); ?></pre>
    </div>
  </div>

  <p class="text-muted small">
    Erros do PHP (warnings/notices) gerados durante o diagnóstico também são gravados nesse mesmo arquivo,
    pois <code>error_log</code> foi apontado para ele temporariamente.
  </p>
</div>
</body>
</html>
