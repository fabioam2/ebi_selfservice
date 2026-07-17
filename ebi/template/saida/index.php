<?php
/**
 * Login e Interface de Registro de Saída de Crianças
 * Reutiliza autenticação e configuração do EBI via bootstrap.php
 */

require __DIR__ . '/inc/bootstrap.php';

$mensagemLoginErro = '';
$loginPageMensagemSucesso = '';

// --- AUTENTICAÇÃO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tentativa_login'])) {
    csrf_validate();

    // Validar tentativas de login
    if (!isset($_SESSION['tentativas_login_saida'])) {
        $_SESSION['tentativas_login_saida'] = 0;
        $_SESSION['ultimo_login_tentativa'] = time();
    }

    // Verificar bloqueio por muitas tentativas
    if ($_SESSION['tentativas_login_saida'] >= MAX_TENTATIVAS_LOGIN) {
        if (time() - $_SESSION['ultimo_login_tentativa'] < TEMPO_BLOQUEIO) {
            $tempo_restante = TEMPO_BLOQUEIO - (time() - $_SESSION['ultimo_login_tentativa']);
            $mensagemLoginErro = "Muitas tentativas. Aguarde " . ceil($tempo_restante) . " segundos.";
        } else {
            $_SESSION['tentativas_login_saida'] = 0;
        }
    }

    if (empty($mensagemLoginErro)) {
        if (verificar_senha_painel($_POST['senha_login'] ?? '')) {
            session_regenerate_id(true);
            $_SESSION['logado_saida'] = true;
            $_SESSION['ultimo_acesso_saida'] = time();
            $_SESSION['tentativas_login_saida'] = 0;
            csrf_regenerate();
            header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
            exit;
        } else {
            $_SESSION['tentativas_login_saida']++;
            $_SESSION['ultimo_login_tentativa'] = time();
            $mensagemLoginErro = 'Senha incorreta.';
        }
    }
}

if (isset($_GET['acao']) && $_GET['acao'] === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    $_SESSION['logout_mensagem'] = 'Você saiu do sistema.';
    header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
    exit;
}

// Redirecionar para login se não autenticado
if (!isset($_SESSION['logado_saida']) || $_SESSION['logado_saida'] !== true) {
    if (isset($_SESSION['logout_mensagem'])) {
        $loginPageMensagemSucesso = $_SESSION['logout_mensagem'];
        unset($_SESSION['logout_mensagem']);
    }
    ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <title>Login - Saída de Crianças</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #0f766e;
            --bg-2: #0b4f8a;
            --brand: #0e7490;
            --brand-strong: #0b5f76;
            --brand-soft: rgba(14, 116, 144, 0.14);
            --danger: #b91c1c;
            --success-border: #1f9d61;
            --text-main: #10273b;
            --text-soft: #4b647c;
        }
        * { box-sizing: border-box; }
        body {
            background: radial-gradient(circle at 8% 8%, rgba(245, 158, 11, 0.32) 0%, rgba(245, 158, 11, 0) 35%),
                        radial-gradient(circle at 94% 16%, rgba(20, 184, 166, 0.38) 0%, rgba(20, 184, 166, 0) 40%),
                        linear-gradient(130deg, var(--bg-1) 0%, var(--bg-2) 58%, #083358 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Manrope', sans-serif;
            padding: 20px;
        }
        .login-container {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.97));
            padding: 34px 28px;
            border-radius: 22px;
            box-shadow: 0 18px 48px rgba(1, 27, 49, 0.33);
            text-align: center;
            width: 100%;
            max-width: 320px;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-18px) scale(0.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .login-container .icon-header { font-size: 2.4rem; color: var(--brand); margin-bottom: 8px; }
        .login-container h2 {
            margin: 0 0 22px;
            color: var(--text-main);
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: -0.01em;
        }
        .login-container label {
            display: block;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            color: #173146;
            margin-bottom: 6px;
        }
        .login-container input[type="password"] {
            width: 100%;
            padding: 11px 13px;
            margin-bottom: 18px;
            border: 1px solid #ced9e4;
            border-radius: 11px;
            font-size: 1rem;
            background: #fbfdff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .login-container input[type="password"]:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 0.19rem var(--brand-soft);
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, var(--brand), var(--brand-strong));
            border: none;
            border-radius: 999px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .login-container button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px var(--brand-soft);
        }
        .error {
            color: var(--danger);
            font-weight: 700;
            font-size: 0.85rem;
            margin-top: 12px;
        }
        .success {
            color: var(--success-border);
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="icon-header"><i class="fas fa-door-open"></i></div>
        <h2>Acesso - Saída de Crianças</h2>
        <?php if (!empty($loginPageMensagemSucesso)): ?>
            <p class="success"><?php echo sanitize_for_html($loginPageMensagemSucesso); ?></p>
        <?php endif; ?>
        <form method="post" action="">
            <?php echo csrf_field(); ?>
            <label for="senha_login">Senha:</label>
            <input type="password" id="senha_login" name="senha_login" required autofocus>
            <button type="submit" name="tentativa_login" value="1"><i class="fas fa-sign-in-alt mr-1"></i> Entrar</button>
            <?php if (!empty($mensagemLoginErro)): ?>
                <p class="error"><?php echo sanitize_for_html($mensagemLoginErro); ?></p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
    <?php
    exit;
}

// --- INTERFACE DE REGISTRO (após autenticação) ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <title>Registrar Saída de Crianças</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #0f766e;
            --bg-2: #0b4f8a;
            --surface-border: rgba(15, 23, 42, 0.08);
            --text-main: #10273b;
            --text-soft: #4b647c;
            --brand: #0e7490;
            --brand-strong: #0b5f76;
            --brand-soft: rgba(14, 116, 144, 0.14);
            --success-bg: #dff8ea;
            --success-border: #1f9d61;
            --danger: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            background: radial-gradient(circle at 8% 8%, rgba(245, 158, 11, 0.32) 0%, rgba(245, 158, 11, 0) 35%),
                        radial-gradient(circle at 94% 16%, rgba(20, 184, 166, 0.38) 0%, rgba(20, 184, 166, 0) 40%),
                        linear-gradient(130deg, var(--bg-1) 0%, var(--bg-2) 58%, #083358 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            font-family: 'Manrope', sans-serif;
            padding: 20px 14px 40px;
        }
        .container {
            width: 100%;
            max-width: 460px;
            height: fit-content;
            margin: 20px auto 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.97));
            padding: 28px 24px;
            border-radius: 22px;
            border: 1px solid var(--surface-border);
            box-shadow: 0 18px 48px rgba(1, 27, 49, 0.33);
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-18px) scale(0.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .header h1 i { color: var(--brand); }
        .logout-link {
            background: var(--danger);
            color: white;
            padding: 8px 14px;
            text-decoration: none;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
            transition: transform 0.2s ease;
        }
        .logout-link:hover {
            color: white;
            transform: translateY(-1px);
        }
        .entry-section { margin-bottom: 18px; }
        label {
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
            text-align: left;
            color: #173146;
            font-size: 0.88rem;
        }
        input[type="number"], select {
            padding: 12px 13px;
            font-size: 1.1rem;
            border-radius: 11px;
            border: 1px solid #ced9e4;
            width: 100%;
            background: #fbfdff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input[type="number"]:focus, select:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 0.19rem var(--brand-soft);
        }
        #lookup-btn {
            margin-top: 12px;
            padding: 12px;
            width: 100%;
            font-size: 1rem;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, var(--brand), var(--brand-strong));
            border: none;
            border-radius: 999px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        #lookup-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px var(--brand-soft);
        }
        .link-painel {
            font-size: 0.95rem;
            margin-top: 22px;
            text-align: center;
        }
        .link-painel a {
            color: var(--brand-strong);
            text-decoration: none;
            font-weight: 700;
        }
        .link-painel a:hover {
            text-decoration: underline;
        }
        #confirmation-area {
            display: none;
            margin-top: 20px;
            border: 1px solid #e2e8f0;
            border-left: 4px solid var(--brand);
            padding: 16px;
            border-radius: 12px;
            background: #f8fafc;
        }
        #confirmation-data {
            text-align: left;
            margin-bottom: 18px;
        }
        #confirmation-data p {
            margin: 5px 0;
            color: var(--text-main);
            font-size: 0.98rem;
        }
        .confirmation-buttons {
            display: flex;
            gap: 10px;
        }
        .confirmation-buttons button {
            flex: 1;
            padding: 12px;
            font-size: 0.95rem;
            border-radius: 999px;
            cursor: pointer;
            border: none;
            font-weight: 700;
            transition: transform 0.2s ease;
        }
        .confirmation-buttons button:hover { transform: translateY(-1px); }
        #confirm-register-btn {
            background: var(--success-border);
            color: white;
        }
        #cancel-register-btn {
            background: #fff;
            color: var(--danger);
            border: 1px solid var(--danger) !important;
        }
        #modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(10, 25, 41, 0.55);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            transition: opacity 0.3s ease;
        }
        #modal-box {
            background-color: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 18px 48px rgba(1, 27, 49, 0.33);
            text-align: center;
            max-width: 85%;
        }
        #modal-message {
            font-size: 1.1rem;
            margin: 0;
            font-weight: 600;
            color: var(--text-main);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-child"></i> Saída de Crianças</h1>
            <a href="?acao=logout" class="logout-link">Sair</a>
        </div>

        <div id="initial-area">
            <div class="entry-section">
                <label for="codigo-resp-input">Código do Responsável:</label>
                <input type="number" id="codigo-resp-input" inputmode="numeric" pattern="[0-9]*" placeholder="Digite o código" autofocus>
                <button id="lookup-btn"><i class="fas fa-search mr-1"></i> Consultar</button>
            </div>
        </div>

        <div id="confirmation-area">
            <div id="confirmation-data"></div>
            <div class="entry-section">
                <label for="portaria">Portaria:</label>
                <select id="portaria">
                    <option value="">-- Selecione --</option>
                    <option value="M">Masculino</option>
                    <option value="F">Feminino</option>
                </select>
            </div>
            <div class="confirmation-buttons">
                <button id="confirm-register-btn"><i class="fas fa-check mr-1"></i> Registrar Saída</button>
                <button id="cancel-register-btn"><i class="fas fa-times mr-1"></i> Cancelar</button>
            </div>
        </div>

        <div class="link-painel"><a href="painel.php"><i class="fas fa-tv mr-1"></i>Ver Painel de Saídas</a></div>
    </div>

    <div id="modal-overlay"><div id="modal-box"><p id="modal-message"></p></div></div>

    <script>
        const initialArea = document.getElementById('initial-area');
        const confirmationArea = document.getElementById('confirmation-area');
        const confirmationDataEl = document.getElementById('confirmation-data');
        const codigoInput = document.getElementById('codigo-resp-input');
        const lookupBtn = document.getElementById('lookup-btn');
        const confirmBtn = document.getElementById('confirm-register-btn');
        const cancelBtn = document.getElementById('cancel-register-btn');
        const portariaSelect = document.getElementById('portaria');
        const modalOverlay = document.getElementById('modal-overlay');
        const modalMessage = document.getElementById('modal-message');

        let dadosParaRegistrar = '';

        function showInitialView() {
            initialArea.style.display = 'block';
            confirmationArea.style.display = 'none';
            codigoInput.value = '';
            codigoInput.focus();
        }

        function showConfirmationView(data) {
            initialArea.style.display = 'none';
            confirmationArea.style.display = 'block';
            let html = `<p><strong>Responsável:</strong> ${escapeHtml(data.responsavel)}</p>`;
            html += `<p><strong>Criança(s):</strong> ${data.criancas.map(c => escapeHtml(c)).join(', ')}</p>`;
            confirmationDataEl.innerHTML = html;
            dadosParaRegistrar = `${data.codResp};${data.responsavel};${data.criancas.join(';')}`;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function handleCodigoLookup() {
            const codigo = codigoInput.value.trim();
            if (!codigo) {
                showModal('Por favor, digite o Código do Responsável.', false);
                return;
            }

            fetch('processar_qr.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'consultar', codigo: codigo })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success_lookup') {
                    showConfirmationView(data);
                } else {
                    showModal(data.message, false);
                }
            })
            .catch(error => showModal('Erro de comunicação com o servidor.', false));
        }

        lookupBtn.addEventListener('click', handleCodigoLookup);
        codigoInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                handleCodigoLookup();
            }
        });

        cancelBtn.addEventListener('click', showInitialView);

        confirmBtn.addEventListener('click', () => {
            const selectedPortaria = portariaSelect.value;
            if (!selectedPortaria) {
                showModal('Por favor, selecione a Portaria.', false);
                return;
            }

            fetch('processar_qr.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'registrar', registroData: dadosParaRegistrar, portaria: selectedPortaria })
            })
            .then(response => response.json())
            .then(data => {
                showModal(data.message, data.status === 'success_registered');
            })
            .catch(error => showModal('Erro ao registrar saída.', true));
        });

        function showModal(message, resetViewAfter) {
            modalMessage.textContent = message;
            modalOverlay.style.display = 'flex';
            modalOverlay.style.opacity = 1;

            setTimeout(() => {
                modalOverlay.style.opacity = 0;
                setTimeout(() => {
                    modalOverlay.style.display = 'none';
                    if (resetViewAfter) {
                        showInitialView();
                    }
                }, 300);
            }, 1500);
        }

        localStorage.getItem('selectedPortaria') && (portariaSelect.value = localStorage.getItem('selectedPortaria'));
        portariaSelect.addEventListener('change', (event) => {
            localStorage.setItem('selectedPortaria', event.target.value);
        });
    </script>
    <div class="text-center mt-3 mb-2" style="font-size:9px;color:#b0b0b0;opacity:0.6">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</body>
</html>
