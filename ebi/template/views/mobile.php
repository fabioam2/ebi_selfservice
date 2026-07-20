<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <title>EBI Mobile – Cadastro por QR Code</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
            --danger-bg: #fee2e2;
        }
        * { box-sizing: border-box; }
        body {
            background: linear-gradient(130deg, var(--bg-1) 0%, var(--bg-2) 58%, #083358 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-family: 'Manrope', sans-serif;
            padding: 16px 12px 30px;
        }
        .mobile-page {
            width: 100%;
            max-width: 440px;
        }
        .mobile-header {
            text-align: center;
            color: #fff;
            margin-bottom: 16px;
        }
        .mobile-header h1 {
            font-size: 1.4rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.02em;
        }
        .mobile-header p {
            font-size: 0.8rem;
            opacity: 0.75;
            margin: 4px 0 0;
        }
        .mobile-card {
            background: rgba(255,255,255,0.98);
            padding: 20px 18px;
            border-radius: 18px;
            border: 1px solid var(--surface-border);
            box-shadow: 0 12px 36px rgba(1,27,49,0.3);
            margin-bottom: 14px;
        }
        .mobile-card h2 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0 0 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .mobile-card h2 i {
            color: var(--brand);
            font-size: 1.1rem;
        }
        #qr-reader {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        #qr-reader video {
            border-radius: 12px;
        }
        .scan-status {
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-soft);
            padding: 8px;
        }
        .scan-status.success {
            color: var(--success-border);
            font-weight: 700;
        }
        .scan-status.error {
            color: var(--danger);
            font-weight: 600;
        }
        .result-box {
            display: none;
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            border-radius: 12px;
            padding: 14px;
            margin-top: 10px;
        }
        .result-box.show { display: block; }
        .result-box .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid rgba(31,157,97,0.15);
            font-size: 0.82rem;
        }
        .result-box .item:last-child { border-bottom: none; }
        .result-box .item .label {
            color: var(--text-soft);
            font-weight: 500;
        }
        .result-box .item .value {
            color: var(--text-main);
            font-weight: 700;
            text-align: right;
            max-width: 60%;
        }
        .btn-cadastrar {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--brand), var(--brand-strong));
            color: #fff;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            margin-top: 12px;
            transition: transform 0.15s, box-shadow 0.15s;
            display: none;
        }
        .btn-cadastrar.show {
            display: block;
        }
        .btn-cadastrar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(14,116,144,0.35);
        }
        .btn-cadastrar:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .btn-scan {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-scan:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(245,158,11,0.35);
        }
        .btn-scan.scanning {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }
        .portaria-input {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
        }
        .portaria-input label {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-main);
            margin: 0;
            white-space: nowrap;
        }
        .portaria-input input {
            width: 50px;
            text-align: center;
            text-transform: uppercase;
            font-weight: 700;
            font-size: 1.1rem;
            border: 2px solid var(--brand-soft);
            border-radius: 8px;
            padding: 6px;
            transition: border-color 0.2s;
        }
        .portaria-input input:focus {
            outline: none;
            border-color: var(--brand);
        }
        .msg-toast {
            display: none;
            position: fixed;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            z-index: 9999;
            animation: slideDown 0.3s ease-out;
            max-width: 90%;
            text-align: center;
        }
        .msg-toast.success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: #166534;
        }
        .msg-toast.error {
            background: var(--danger-bg);
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translate(-50%, -20px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }
        .counter-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--brand-soft);
            color: var(--brand-strong);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-top: 8px;
        }
        .btn-voltar {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: rgba(255,255,255,0.7);
            font-size: 0.78rem;
            text-decoration: none;
            margin-bottom: 12px;
        }
        .btn-voltar:hover { color: #fff; text-decoration: none; }
        .version-footer {
            text-align: center;
            margin-top: 16px;
            font-size: 9px;
            color: rgba(255,255,255,0.35);
        }
    </style>
</head>
<body>
<div class="mobile-page">
    <a href="<?php echo sanitize_for_html($_SERVER['PHP_SELF']); ?>" class="btn-voltar">
        <i class="fas fa-arrow-left"></i> Voltar ao cadastro
    </a>

    <div class="mobile-header">
        <h1><i class="fas fa-mobile-alt mr-1"></i> EBI Mobile</h1>
        <p>Cadastro rápido por QR Code</p>
    </div>

    <div id="msgToast" class="msg-toast"></div>

    <?php if ($mensagemSucesso): ?>
        <script>document.addEventListener('DOMContentLoaded',function(){showToast('<?php echo addslashes($mensagemSucesso); ?>','success')});</script>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <script>document.addEventListener('DOMContentLoaded',function(){showToast('<?php echo addslashes($mensagemErro); ?>','error')});</script>
    <?php endif; ?>

    <!-- Scanner -->
    <div class="mobile-card">
        <h2><i class="fas fa-camera"></i> Escanear QR Code</h2>
        <div id="qr-reader" style="display:none;"></div>
        <div id="scanStatus" class="scan-status">Pressione o botão abaixo para abrir a câmera</div>
        <button type="button" class="btn-scan" id="btnScan" onclick="toggleScanner()">
            <i class="fas fa-camera mr-2"></i> Scan
        </button>
    </div>

    <!-- Resultado -->
    <div class="mobile-card">
        <h2><i class="fas fa-child"></i> Dados Lidos</h2>
        <div id="resultBox" class="result-box">
            <div id="resultItems"></div>
        </div>
        <div id="emptyState" style="text-align:center;color:var(--text-soft);font-size:0.82rem;padding:16px 0;">
            <i class="fas fa-qrcode" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:8px;"></i>
            Nenhum QR lido ainda
        </div>

        <form method="post" action="<?php echo sanitize_for_html($_SERVER['PHP_SELF']); ?>" id="formMobile">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="cadastrar" value="1">
            <input type="hidden" name="mobile" value="1">
            <div id="hiddenFields"></div>

            <div class="portaria-input">
                <label for="portaria_mobile">Portaria:</label>
                <input type="text" id="portaria_mobile" name="portaria_cadastro" maxlength="1" autocomplete="off" placeholder="A" required>
                <span class="counter-badge" id="counterBadge" style="display:none;">
                    <i class="fas fa-users"></i> <span id="childCount">0</span> criança(s)
                </span>
            </div>

            <button type="submit" class="btn-cadastrar" id="btnCadastrar">
                <i class="fas fa-check-circle mr-1"></i> Cadastrar
            </button>
        </form>
    </div>

    <div class="version-footer">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function() {
    let scannedData = [];
    let scanner = null;
    let isScanning = false;
    const resultBox = document.getElementById('resultBox');
    const resultItems = document.getElementById('resultItems');
    const emptyState = document.getElementById('emptyState');
    const hiddenFields = document.getElementById('hiddenFields');
    const btnCadastrar = document.getElementById('btnCadastrar');
    const counterBadge = document.getElementById('counterBadge');
    const childCount = document.getElementById('childCount');
    const scanStatus = document.getElementById('scanStatus');
    const btnScan = document.getElementById('btnScan');
    const qrReader = document.getElementById('qr-reader');
    const portariaInput = document.getElementById('portaria_mobile');

    // Persistir portaria no localStorage
    const savedPortaria = localStorage.getItem('ebi_mobile_portaria');
    if (savedPortaria) {
        portariaInput.value = savedPortaria;
    }
    portariaInput.addEventListener('input', function() {
        const val = this.value.toUpperCase();
        this.value = val;
        if (val) localStorage.setItem('ebi_mobile_portaria', val);
    });

    function showToast(msg, type) {
        const t = document.getElementById('msgToast');
        t.textContent = msg;
        t.className = 'msg-toast ' + type;
        t.style.display = 'block';
        setTimeout(function() { t.style.display = 'none'; }, 3500);
    }
    window.showToast = showToast;

    window.toggleScanner = function() {
        if (isScanning) {
            stopScanner();
        } else {
            startScanner();
        }
    };

    function startScanner() {
        qrReader.style.display = 'block';
        scanner = new Html5Qrcode('qr-reader');
        scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 220, height: 220 } },
            onScanSuccess
        ).then(function() {
            isScanning = true;
            btnScan.innerHTML = '<i class="fas fa-stop mr-2"></i> Parar';
            btnScan.classList.add('scanning');
            scanStatus.textContent = 'Aponte a câmera para o QR Code';
            scanStatus.className = 'scan-status';
        }).catch(function(err) {
            scanStatus.textContent = 'Não foi possível acessar a câmera';
            scanStatus.className = 'scan-status error';
            qrReader.style.display = 'none';
        });
    }

    function stopScanner() {
        if (scanner && isScanning) {
            scanner.stop().then(function() {
                isScanning = false;
                btnScan.innerHTML = '<i class="fas fa-camera mr-2"></i> Scan';
                btnScan.classList.remove('scanning');
                qrReader.style.display = 'none';
                if (scannedData.length === 0) {
                    scanStatus.textContent = 'Pressione o botão abaixo para abrir a câmera';
                    scanStatus.className = 'scan-status';
                }
            });
        }
    }

    function parseQRData(text) {
        // Formato: NomeCriança\tResponsável\tIdade\tTelefone\tComum
        // Ou com 6 campos: NomeCriança\tResponsável\tDataNasc\tIdade\tTelefone\tComum
        // Múltiplas crianças separadas por \r
        const lines = text.split(/\r|\n/).filter(function(l) { return l.trim() !== ''; });
        const results = [];

        for (let i = 0; i < lines.length; i++) {
            const parts = lines[i].split('\t');
            let entry = {};

            if (parts.length === 5) {
                // Formato 5 colunas: nome, responsavel, idade, telefone, comum
                entry = {
                    nome_crianca: parts[0].trim(),
                    nome_responsavel: parts[1].trim(),
                    idade: parts[2].trim(),
                    telefone: parts[3].trim(),
                    comum: parts[4].trim()
                };
            } else if (parts.length === 6) {
                // Formato 6 colunas: nome, responsavel, dataNasc, idade, telefone, comum
                entry = {
                    nome_crianca: parts[0].trim(),
                    nome_responsavel: parts[1].trim(),
                    idade: parts[3].trim(),
                    telefone: parts[4].trim(),
                    comum: parts[5].trim()
                };
            } else {
                continue; // formato inválido
            }

            if (entry.nome_crianca && entry.nome_responsavel) {
                results.push(entry);
            }
        }
        return results;
    }

    function renderResults() {
        if (scannedData.length === 0) {
            resultBox.classList.remove('show');
            emptyState.style.display = 'block';
            btnCadastrar.classList.remove('show');
            counterBadge.style.display = 'none';
            hiddenFields.innerHTML = '';
            return;
        }

        emptyState.style.display = 'none';
        resultBox.classList.add('show');
        btnCadastrar.classList.add('show');
        counterBadge.style.display = 'inline-flex';
        childCount.textContent = scannedData.length;

        let html = '';
        let fields = '';

        for (let i = 0; i < scannedData.length; i++) {
            const d = scannedData[i];
            html += '<div class="item"><span class="label">Criança</span><span class="value">' + escHtml(d.nome_crianca) + '</span></div>';
            html += '<div class="item"><span class="label">Responsável</span><span class="value">' + escHtml(d.nome_responsavel) + '</span></div>';
            html += '<div class="item"><span class="label">Idade</span><span class="value">' + escHtml(d.idade) + ' anos</span></div>';
            html += '<div class="item"><span class="label">Telefone</span><span class="value">' + escHtml(d.telefone) + '</span></div>';
            html += '<div class="item"><span class="label">Comum</span><span class="value">' + escHtml(d.comum) + '</span></div>';
            if (i < scannedData.length - 1) {
                html += '<hr style="margin:6px 0;border-color:rgba(31,157,97,0.2)">';
            }

            fields += '<input type="hidden" name="nome_crianca[]" value="' + escAttr(d.nome_crianca) + '">';
            fields += '<input type="hidden" name="nome_responsavel[]" value="' + escAttr(d.nome_responsavel) + '">';
            fields += '<input type="hidden" name="idade[]" value="' + escAttr(d.idade) + '">';
            fields += '<input type="hidden" name="telefone[]" value="' + escAttr(d.telefone) + '">';
            fields += '<input type="hidden" name="comum[]" value="' + escAttr(d.comum) + '">';
        }

        resultItems.innerHTML = html;
        hiddenFields.innerHTML = fields;
    }

    function escHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function escAttr(s) {
        return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function onScanSuccess(decodedText) {
        const parsed = parseQRData(decodedText);
        if (parsed.length === 0) {
            scanStatus.textContent = 'QR Code inválido — formato não reconhecido';
            scanStatus.className = 'scan-status error';
            return;
        }

        scannedData = parsed;
        renderResults();
        scanStatus.textContent = parsed.length + ' criança(s) lida(s) com sucesso!';
        scanStatus.className = 'scan-status success';

        // Parar scanner após leitura bem-sucedida
        stopScanner();

        // Vibrar o celular para feedback
        if (navigator.vibrate) navigator.vibrate(200);
    }

})();
</script>
</body>
</html>
