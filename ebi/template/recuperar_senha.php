<?php
/**
 * Reset de senha do EBI via e-mail.
 * Valida o e-mail contra [INFO_USUARIO] EMAIL no config.ini da instância.
 * Gera nova senha temporária, atualiza config.ini e envia por e-mail.
 */

require __DIR__ . '/inc/bootstrap.php';

// Não exige login — é a tela de recuperação pública.

// Carrega PHPMailer via autoload do projeto raiz
$_ebi_root_dir = defined('INSTANCE_DIR')
    ? dirname(dirname(dirname(INSTANCE_DIR)))
    : dirname(dirname(__DIR__));
if (file_exists($_ebi_root_dir . '/vendor/autoload.php')) {
    require_once $_ebi_root_dir . '/vendor/autoload.php';

    if (class_exists('Dotenv\\Dotenv') && file_exists($_ebi_root_dir . '/.env')) {
        Dotenv\Dotenv::createImmutable($_ebi_root_dir)->safeLoad();
    }
}
require_once $_ebi_root_dir . '/selfservice/inc/email_manager.php';

// CSRF próprio para esta página
if (empty($_SESSION['ebi_rs_csrf'])) {
    $_SESSION['ebi_rs_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['ebi_rs_csrf'];

$config = parse_ini_file(CAMINHO_CONFIG_INI, true, INI_SCANNER_TYPED);
$emailInstancia = trim((string)($config['INFO_USUARIO']['EMAIL'] ?? ''));

$mensagem = '';
$tipo     = 'info';
$resposta = 'Se o e-mail informado estiver cadastrado nesta instância, uma nova senha temporária foi enviada. Verifique também o spam.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenOk       = hash_equals($_SESSION['ebi_rs_csrf'] ?? '', $_POST['csrf'] ?? '');
    $emailDigitado = strtolower(trim((string)($_POST['email'] ?? '')));

    if (!$tokenOk) {
        $mensagem = 'Sessão expirada. Recarregue e tente novamente.';
        $tipo     = 'danger';
    } elseif (!filter_var($emailDigitado, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Informe um e-mail válido.';
        $tipo     = 'danger';
    } else {
        // Regenerar CSRF após uso
        $_SESSION['ebi_rs_csrf'] = bin2hex(random_bytes(32));
        $csrf = $_SESSION['ebi_rs_csrf'];

        $emailMatch = ($emailInstancia !== '' && strtolower($emailInstancia) === $emailDigitado);

        if ($emailMatch) {
            // Gerar senha temporária
            $senhaTemp = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#'), 0, 12);
            $novoHash  = password_hash($senhaTemp, PASSWORD_BCRYPT, ['cost' => 12]);

            // Atualizar config.ini com novo hash
            $ini       = file_get_contents(CAMINHO_CONFIG_INI);
            $linhas    = preg_split("/\r?\n/", $ini);
            $dentroSeg = false;
            $setHash   = false;
            foreach ($linhas as $i => $ln) {
                if (preg_match('/^\s*\[([^\]]+)\]/', $ln, $m)) {
                    $dentroSeg = strcasecmp(trim($m[1]), 'SEGURANCA') === 0;
                    continue;
                }
                if ($dentroSeg) {
                    if (preg_match('/^\s*SENHA_ADMIN_HASH\s*=/', $ln)) {
                        $linhas[$i] = 'SENHA_ADMIN_HASH = "' . $novoHash . '"';
                        $setHash    = true;
                    }
                    if (preg_match('/^\s*SENHA_PAINEL_HASH\s*=/', $ln)) {
                        $linhas[$i] = 'SENHA_PAINEL_HASH = "' . $novoHash . '"';
                    }
                    if (preg_match('/^\s*SENHA_ADMIN_REAL\s*=/', $ln)) {
                        $linhas[$i] = 'SENHA_ADMIN_REAL = ""';
                    }
                    if (preg_match('/^\s*SENHA_PAINEL\s*=/', $ln)) {
                        $linhas[$i] = 'SENHA_PAINEL = ""';
                    }
                }
            }
            if ($setHash) {
                @file_put_contents(CAMINHO_CONFIG_INI, implode("\n", $linhas), LOCK_EX);
                @chmod(CAMINHO_CONFIG_INI, 0600);
            }

            // Enviar e-mail
            $nomeInstancia = trim((string)($config['INFO_USUARIO']['NOME'] ?? 'EBI'));
            $linkSistema   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                           . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                           . sanitize_for_html($_SERVER['PHP_SELF']);
            enviarEmailResetSenha($emailInstancia, $nomeInstancia, $linkSistema, $senhaTemp);
        }

        // Resposta sempre genérica (não vazar se e-mail existe)
        $mensagem = $resposta;
        $tipo     = 'success';
    }
}

$tituloSistema = defined('INSTANCE_COMUM') && INSTANCE_COMUM
    ? 'EBI — ' . ucfirst(INSTANCE_COMUM)
    : 'EBI — Cadastro de Crianças';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha — <?php echo sanitize_for_html($tituloSistema); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); min-height:100vh;
               display:flex; flex-direction:column; align-items:center; justify-content:center; padding:20px; }
        .card-rec { background:#fff; padding:36px; border-radius:14px; box-shadow:0 10px 40px rgba(0,0,0,.3);
                    width:100%; max-width:460px; }
        .card-rec h2 { color:#667eea; font-weight:700; text-align:center; margin-bottom:6px; }
        .card-rec p.sub { color:#6c757d; text-align:center; margin-bottom:22px; }
        .btn-rec { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
                   border:none; color:#fff; font-weight:600; }
        .btn-rec:hover { filter:brightness(1.05); color:#fff; }
        .voltar { color:#fff; text-decoration:underline; margin-top:14px; display:inline-block; }
    </style>
</head>
<body>
    <div class="card-rec">
        <h2><i class="fas fa-key mr-2"></i>Recuperar Senha</h2>
        <p class="sub">Informe o e-mail cadastrado nesta instância. Uma nova senha temporária será enviada.</p>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo; ?>"><?php echo sanitize_for_html($mensagem); ?></div>
        <?php endif; ?>

        <?php if ($tipo !== 'success'): ?>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf" value="<?php echo sanitize_for_html($csrf); ?>">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope mr-1"></i>E-mail da instância</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="seu@email.com" required autofocus
                       value="<?php echo sanitize_for_html($_POST['email'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-rec btn-block">
                <i class="fas fa-paper-plane mr-1"></i>Enviar nova senha
            </button>
        </form>
        <?php endif; ?>
    </div>

    <a class="voltar" href="index.php">← Voltar ao sistema</a>
    <div style="font-size:9px;color:rgba(255,255,255,.4);text-align:center;margin-top:12px">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</body>
</html>
