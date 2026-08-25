<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('EBI_REUNIAO_PRESENCIAL') ? 'Reunião Regional - EBI' : 'Acesso à Reunião - Espaço Bíblico Infantil'; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #0f766e;
            --bg-2: #0b4f8a;
            --brand: #0e7490;
            --brand-strong: #0b5f76;
            --surface: rgba(255,255,255,0.97);
            --text-main: #10273b;
            --text-soft: #4b647c;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--bg-1) 0%, var(--bg-2) 60%, #083358 100%);
            font-family: 'Manrope', sans-serif;
            color: var(--text-main);
        }
        .login-context {
            margin-bottom: 12px;
            color: rgba(255,255,255,0.76);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-align: center;
            text-transform: uppercase;
        }
        .login-container {
            width: 100%;
            max-width: 430px;
            padding: 34px;
            background: var(--surface);
            border: 1px solid rgba(255,255,255,0.32);
            border-radius: 14px;
            box-shadow: 0 18px 48px rgba(1,27,49,0.3);
        }
        .login-brand {
            width: 58px;
            height: 58px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #fff;
            background: linear-gradient(135deg, var(--brand), var(--brand-strong));
            box-shadow: 0 7px 18px rgba(14,116,144,0.28);
            font-size: 1.5rem;
        }
        .login-container h1 {
            margin: 0;
            color: var(--text-main);
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.25;
            text-align: center;
        }
        .login-subtitle {
            margin: 8px 0 26px;
            color: var(--text-soft);
            font-size: 0.88rem;
            text-align: center;
        }
        .login-container label {
            color: var(--text-main);
            font-size: 0.82rem;
            font-weight: 700;
        }
        .input-group-text {
            border-color: #d1d5db;
            color: var(--brand);
            background: #f8fafc;
        }
        .form-control {
            height: calc(1.5em + 1rem + 2px);
            border-color: #d1d5db;
            color: var(--text-main);
        }
        .form-control:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 0.2rem rgba(14,116,144,0.2);
        }
        .btn-login {
            min-height: 43px;
            border: 0;
            border-radius: 8px;
            color: #fff;
            background: linear-gradient(135deg, var(--brand), var(--brand-strong));
            font-size: 0.9rem;
            font-weight: 700;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .btn-login:hover,
        .btn-login:focus {
            color: #fff;
            background: linear-gradient(135deg, var(--brand-strong), #084c60);
            box-shadow: 0 5px 14px rgba(14,116,144,0.28);
            transform: translateY(-1px);
        }
        .btn-toggle-password {
            border-color: #d1d5db;
            color: var(--text-soft);
            background: #fff;
        }
        .btn-toggle-password:hover,
        .btn-toggle-password:focus {
            border-color: var(--brand);
            color: var(--brand);
            box-shadow: none;
        }
        .alert-login {
            margin-bottom: 18px;
            border: 0;
            border-radius: 8px;
            font-size: 0.84rem;
        }
        .login-help-link {
            color: var(--text-soft);
            font-size: 0.82rem;
            font-weight: 600;
        }
        .login-help-link:hover,
        .login-help-link:focus { color: var(--brand); }
        .version-footer {
            position: fixed;
            right: 16px;
            bottom: 10px;
            color: rgba(255,255,255,0.62);
            font-size: 9px;
            opacity: 0.78;
        }
        @media (max-width: 480px) {
            body { padding: 16px; }
            .login-container { padding: 28px 22px; }
            .version-footer { position: static; margin-top: 16px; }
        }
    </style>
</head>
<body>
    <?php
    $cidadeLogin = defined('INSTANCE_CIDADE') ? trim((string)INSTANCE_CIDADE) : '';
    $comumLogin = defined('INSTANCE_COMUM') ? trim((string)INSTANCE_COMUM) : '';
    $contextoLogin = trim($cidadeLogin . ' - ' . $comumLogin, ' -');
    ?>
    <div class="login-context"><?php echo sanitize_for_html($contextoLogin !== '' ? $contextoLogin : (defined('EBI_REUNIAO_PRESENCIAL') ? 'Reunião Regional - EBI' : 'Reunião do Espaço Bíblico Infantil')); ?></div>
    <main class="login-container" aria-labelledby="login-title">
        <div class="login-brand" aria-hidden="true"><i class="fas fa-child"></i></div>
        <h1 id="login-title"><?php echo defined('EBI_REUNIAO_PRESENCIAL') ? 'Reunião Regional - EBI' : 'Acesso à Reunião'; ?></h1>
        <p class="login-subtitle">Espaço Bíblico Infantil</p>
        <?php if (!empty($loginPageMensagemSucesso)): ?>
            <div class="alert alert-success alert-login"><?php echo $loginPageMensagemSucesso; ?></div>
        <?php endif; ?>
        <?php if (!empty($mensagemLoginErro)): ?>
            <div class="alert alert-danger alert-login"><?php echo sanitize_for_html($mensagemLoginErro); ?></div>
        <?php endif; ?>
        <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="senha_login">Senha de Acesso:</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-key" aria-hidden="true"></i></span>
                    </div>
                    <input type="password" class="form-control" id="senha_login" name="senha_login" autocomplete="current-password" required autofocus>
                    <div class="input-group-append">
                        <button class="btn btn-toggle-password" type="button" id="btnToggleSenha" aria-label="Mostrar senha" aria-pressed="false" title="Mostrar senha">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
            <button type="submit" name="tentativa_login" class="btn btn-login btn-block">Entrar <i class="fas fa-arrow-right ml-1" aria-hidden="true"></i></button>
        </form>
        <div class="text-center mt-3">
            <a href="index.php?acao=recuperar_senha" class="login-help-link">
                <i class="fas fa-key mr-1"></i>Esqueci minha senha
            </a>
        </div>
    </main>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('btnToggleSenha').addEventListener('click', function () {
            var senhaInput = document.getElementById('senha_login');
            var mostrarSenha = senhaInput.type === 'password';
            senhaInput.type = mostrarSenha ? 'text' : 'password';
            this.setAttribute('aria-label', mostrarSenha ? 'Ocultar senha' : 'Mostrar senha');
            this.setAttribute('aria-pressed', mostrarSenha ? 'true' : 'false');
            this.setAttribute('title', mostrarSenha ? 'Ocultar senha' : 'Mostrar senha');
            this.querySelector('i').className = mostrarSenha ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    </script>
    <div class="version-footer">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</body>
</html>
