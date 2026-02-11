<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cadastro de Crianças</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #eef2f7; font-family: 'Inter', sans-serif; }
        .login-container { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 100%; max-width: 400px; }
        .login-container h2 { text-align: center; margin-bottom: 20px; color: #007bff; }
        .login-container h2 img { border: 1px solid #007bff; }
        .form-control:focus { border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
        .btn-primary { background-color: #007bff; border-color: #007bff; }
        .btn-primary:hover { background-color: #0056b3; border-color: #0056b3; }
        .alert-login { margin-top: 15px; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <h2><img src="https://placehold.co/40x40/007bff/white?text=Kids" alt="Ícone" style="vertical-align: middle; border-radius: 50%; margin-right: 10px;"> Acesso ao Sistema</h2>
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
                <input type="password" class="form-control" id="senha_login" name="senha_login" required autofocus>
            </div>
            <button type="submit" name="tentativa_login" class="btn btn-primary btn-block">Entrar</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
