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
        if (isset($_POST['senha_login']) && $_POST['senha_login'] === SENHA_PAINEL) {
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
    $_SESSION['logado_saida'] = false;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Saída de Crianças</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
            width: 100%;
            max-width: 300px;
        }
        .login-container h2 {
            margin-top: 0;
            color: #333;
        }
        .login-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .login-container button:hover {
            background-color: #0056b3;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
            margin-top: 10px;
        }
        .success {
            color: #28a745;
            font-weight: bold;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Acesso - Saída de Crianças</h2>
        <?php if (!empty($loginPageMensagemSucesso)): ?>
            <p class="success"><?php echo sanitize_for_html($loginPageMensagemSucesso); ?></p>
        <?php endif; ?>
        <form method="post" action="">
            <?php echo csrf_field(); ?>
            <label for="senha_login">Senha:</label>
            <input type="password" id="senha_login" name="senha_login" required autofocus>
            <button type="submit" name="tentativa_login" value="1">Entrar</button>
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
$todosOsCadastros = lerTodosCadastros(ARQUIVO_DADOS);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Saída de Crianças</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 450px;
            margin: 0 auto;
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
        }
        .logout-link {
            background-color: #dc3545;
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .logout-link:hover {
            background-color: #b22222;
        }
        .entry-section {
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
            text-align: left;
            color: #333;
        }
        input[type="number"], select {
            padding: 10px;
            font-size: 1.1rem;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 100%;
            box-sizing: border-box;
        }
        #lookup-btn {
            margin-top: 10px;
            padding: 10px;
            width: 100%;
            font-size: 1rem;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        #lookup-btn:hover {
            background-color: #0056b3;
        }
        .link-painel {
            font-size: 1.1em;
            margin-top: 25px;
            text-align: center;
        }
        .link-painel a {
            color: #007bff;
            text-decoration: none;
        }
        .link-painel a:hover {
            text-decoration: underline;
        }
        #confirmation-area {
            display: none;
            margin-top: 20px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        #confirmation-data {
            text-align: left;
            margin-bottom: 20px;
        }
        #confirmation-data p {
            margin: 5px 0;
            color: #333;
        }
        .confirmation-buttons {
            display: flex;
            gap: 10px;
        }
        .confirmation-buttons button {
            flex: 1;
            padding: 10px;
            font-size: 1rem;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-weight: bold;
        }
        #confirm-register-btn {
            background-color: #28a745;
            color: white;
        }
        #confirm-register-btn:hover {
            background-color: #218838;
        }
        #cancel-register-btn {
            background-color: #dc3545;
            color: white;
        }
        #cancel-register-btn:hover {
            background-color: #b22222;
        }
        #modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            transition: opacity 0.3s ease;
        }
        #modal-box {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 80%;
        }
        #modal-message {
            font-size: 1.2rem;
            margin: 0;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Saída de Crianças</h1>
            <a href="?acao=logout" class="logout-link">Sair</a>
        </div>

        <div id="initial-area">
            <div class="entry-section">
                <label for="codigo-resp-input">Código do Responsável:</label>
                <input type="number" id="codigo-resp-input" inputmode="numeric" pattern="[0-9]*" placeholder="Digite o código" autofocus>
                <button id="lookup-btn">Consultar</button>
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
                <button id="confirm-register-btn">Registrar Saída</button>
                <button id="cancel-register-btn">Cancelar</button>
            </div>
        </div>

        <div class="link-painel"><a href="painel.php">Ver Painel de Saídas</a></div>
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
</body>
</html>
