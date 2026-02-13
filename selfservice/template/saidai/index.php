<?php
// A lógica de login com senha permanece a mesma
session_start();
$senha_fixa = 'Sumare$!';
$erro_senha = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['senha'])) {
    if ($_POST['senha'] === $senha_fixa) { $_SESSION['autenticado'] = true; header("Location: inserir.php"); exit; } else { $erro_senha = 'Senha incorreta!'; }
}
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login</title><style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;background-color:#f0f2f5;margin:0;padding:20px;display:flex;justify-content:center;align-items:center;height:100vh}.login-container{background-color:white;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);text-align:center;width:300px}h2{margin-top:0}input[type=password]{width:100%;padding:10px;margin-top:10px;margin-bottom:20px;border:1px solid #ccc;border-radius:5px;box-sizing:border-box}button{width:100%;padding:12px;font-size:1rem;color:white;background-color:#007bff;border:none;border-radius:5px;cursor:pointer}.error{color:#dc3545;font-weight:700;margin-top:10px}</style></head><body><div class="login-container"><h2>Acesso Restrito</h2><form method="post" action="inserir.php"><label for="senha">Senha:</label><input type="password" id="senha" name="senha" required autofocus><button type="submit">Entrar</button><?php if($erro_senha):?><p class="error"><?php echo $erro_senha;?></p><?php endif;?></form></div></body></html>
<?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Entrada</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 450px; margin: 0 auto; background-color: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center; }
        .entry-section { margin-bottom: 20px; }
        label { font-weight: bold; margin-bottom: 5px; display: block; text-align: left; }
        input[type="number"], select { padding: 10px; font-size: 1.1rem; border-radius: 5px; border: 1px solid #ccc; width: 100%; box-sizing: border-box; }
        #lookup-btn { margin-top: 10px; padding: 10px; width: 100%; font-size: 1rem; color: white; background-color: #007bff; border: none; border-radius: 5px; cursor: pointer; }
        .link-painel { font-size: 1.1em; margin-top: 25px; display: block; }

        #confirmation-area { display: none; margin-top: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 8px; background-color: #f8f9fa; }
        #confirmation-data { text-align: left; margin-bottom: 20px; }
        #confirmation-data p { margin: 5px 0; }
        .confirmation-buttons { display: flex; gap: 10px; }
        .confirmation-buttons button { flex: 1; padding: 10px; font-size: 1rem; border-radius: 5px; cursor: pointer; border: none; }
        #confirm-register-btn { background-color: #28a745; color: white; }
        #cancel-register-btn { background-color: #dc3545; color: white; }

        #qr-reader, .separator { display: none; }
        #modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); display: none; justify-content: center; align-items: center; z-index: 1000; transition: opacity 0.3s ease; }
        #modal-box { background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); text-align: center; max-width: 80%; }
        #modal-message { font-size: 1.2rem; margin: 0; color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <div id="initial-area">
            <div class="entry-section">
                <label for="codigo-resp-input">Código do Responsável:</label>
                <input type="number" id="codigo-resp-input" inputmode="numeric" pattern="[0-9]*" placeholder="Digite o código">
                <button id="lookup-btn">Consultar</button>
            </div>
        </div>

        <div id="confirmation-area">
            <div id="confirmation-data"></div>
            <div class="confirmation-buttons">
                <button id="confirm-register-btn">Registrar</button>
                <button id="cancel-register-btn">Cancelar</button>
            </div>
        </div>
        
        <div class="entry-section">
            <label for="portaria">PORTARIA:</label>
            <select id="portaria">
                <option value="">-- Selecione --</option>
                <option value="M">Masculino</option>
                <option value="F">Feminino</option>
            </select>
        </div>
        
        <div class="link-painel"><a href="painel.php">Ver Painel de Entradas</a></div>
    </div>

    <!-- ALTERAÇÃO: O botão OK foi removido do HTML do modal -->
    <div id="modal-overlay"><div id="modal-box"><p id="modal-message"></p></div></div>

    <script>
        // Elementos do DOM
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
            let html = `<p><strong>Responsável:</strong> ${data.responsavel}</p>`;
            html += `<p><strong>Criança(s):</strong> ${data.criancas.join(', ')}</p>`;
            confirmationDataEl.innerHTML = html;
            dadosParaRegistrar = `${data.codResp};${data.responsavel};${data.criancas.join(';')}`;
        }
        
        function handleCodigoLookup() {
            const codigo = codigoInput.value.trim();
            if (!codigo) { alert('Por favor, digite o Código do Responsável.'); return; }

            fetch('processar_qr.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ type: 'consultar', codigo: codigo }) })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success_lookup') {
                    showConfirmationView(data);
                } else {
                    showModal(data.message, false); // Mostra erro, não reseta a view
                }
            })
            .catch(error => showModal('Erro de comunicação.', false));
        }
        lookupBtn.addEventListener('click', handleCodigoLookup);
        codigoInput.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); handleCodigoLookup(); } });

        cancelBtn.addEventListener('click', showInitialView);

        confirmBtn.addEventListener('click', () => {
            const selectedPortaria = portariaSelect.value;
            if (!selectedPortaria) { alert('Por favor, selecione a Portaria.'); return; }

            fetch('processar_qr.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ type: 'registrar', registroData: dadosParaRegistrar, portaria: selectedPortaria }) })
            .then(response => response.json())
            .then(data => {
                showModal(data.message, true); // Mostra status e reseta a view
            })
            .catch(error => showModal('Erro de comunicação.', true));
        });

        /**
         * ALTERAÇÃO: A lógica do botão OK foi movida para um setTimeout aqui dentro.
         */
        function showModal(message, resetViewAfter) {
            modalMessage.textContent = message;
            modalOverlay.style.display = 'flex';
            modalOverlay.style.opacity = 1;

            // Define um timer para esconder o modal e resetar a interface
            setTimeout(() => {
                modalOverlay.style.opacity = 0; // Inicia o fade out
                setTimeout(() => {
                    modalOverlay.style.display = 'none'; // Esconde após a animação
                    if (resetViewAfter) {
                        showInitialView();
                    }
                }, 300); // Tempo da animação de fade out
            }, 1500); // 2.5 segundos para a mensagem ficar visível
        }

        localStorage.getItem('selectedPortaria') && (portariaSelect.value = localStorage.getItem('selectedPortaria'));
        portariaSelect.addEventListener('change', (event) => { localStorage.setItem('selectedPortaria', event.target.value); });
    </script>
</body>
</html>
