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
        .mobile-page { width: 100%; max-width: 440px; }
        .mobile-header { text-align: center; color: #fff; margin-bottom: 16px; }
        .mobile-header h1 { font-size: 1.4rem; font-weight: 800; margin: 0; }
        .mobile-header p { font-size: 0.8rem; opacity: 0.75; margin: 4px 0 0; }
        .mobile-card {
            background: rgba(255,255,255,0.98);
            padding: 20px 18px;
            border-radius: 18px;
            border: 1px solid var(--surface-border);
            box-shadow: 0 12px 36px rgba(1,27,49,0.3);
            margin-bottom: 14px;
        }
        .mobile-card h2 {
            font-size: 0.95rem; font-weight: 700; color: var(--text-main);
            margin: 0 0 12px; display: flex; align-items: center; gap: 8px;
        }
        .mobile-card h2 i { color: var(--brand); font-size: 1.1rem; }
        #qr-reader { width: 100%; border-radius: 12px; overflow: hidden; margin-bottom: 12px; display: none; }
        .scan-status { text-align: center; font-size: 0.82rem; color: var(--text-soft); padding: 8px; }
        .scan-status.success { color: var(--success-border); font-weight: 700; }
        .scan-status.error { color: var(--danger); font-weight: 600; }

        .btn-scan {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff; font-weight: 700; font-size: 1.1rem;
            cursor: pointer; transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-scan:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(245,158,11,0.35); }
        .btn-scan.scanning { background: linear-gradient(135deg, #dc2626, #b91c1c); }

        .btn-lido {
            width: 100%; padding: 18px; border: none; border-radius: 12px;
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: #fff; font-weight: 800; font-size: 1.2rem;
            cursor: pointer; display: none;
            transition: transform 0.15s, box-shadow 0.15s;
            animation: pulse 1.5s infinite;
        }
        .btn-lido.show { display: block; }
        .btn-lido:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(22,163,74,0.4); }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(22,163,74,0.5); }
            50% { box-shadow: 0 0 0 10px rgba(22,163,74,0); }
        }

        .result-box {
            display: none; background: var(--success-bg); border: 1px solid var(--success-border);
            border-radius: 12px; padding: 14px; margin: 12px 0;
        }
        .result-box.show { display: block; }
        .result-box .item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 5px 0; border-bottom: 1px solid rgba(31,157,97,0.15); font-size: 0.8rem;
        }
        .result-box .item:last-child { border-bottom: none; }
        .result-box .item .label { color: var(--text-soft); font-weight: 500; }
        .result-box .item .value { color: var(--text-main); font-weight: 700; text-align: right; max-width: 60%; }

        .portaria-section {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 14px; padding: 12px 14px;
            background: var(--brand-soft); border-radius: 10px;
        }
        .portaria-section label { font-size: 0.82rem; font-weight: 600; color: var(--text-main); margin: 0; }
        .portaria-section input {
            width: 48px; text-align: center; text-transform: uppercase;
            font-weight: 700; font-size: 1.1rem;
            border: 2px solid var(--brand); border-radius: 8px; padding: 6px;
        }
        .portaria-section input:focus { outline: none; border-color: var(--brand-strong); }

        .msg-toast {
            display: none; position: fixed; top: 16px; left: 50%; transform: translateX(-50%);
            padding: 12px 20px; border-radius: 10px; font-size: 0.85rem; font-weight: 600;
            z-index: 9999; max-width: 90%; text-align: center; animation: slideDown 0.3s ease-out;
        }
        .msg-toast.success { background: var(--success-bg); border: 1px solid var(--success-border); color: #166534; }
        .msg-toast.error { background: var(--danger-bg); border: 1px solid var(--danger); color: var(--danger); }
        @keyframes slideDown {
            from { opacity: 0; transform: translate(-50%, -20px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }

        .btn-voltar {
            display: inline-flex; align-items: center; gap: 5px;
            color: rgba(255,255,255,0.7); font-size: 0.78rem; text-decoration: none; margin-bottom: 12px;
        }
        .btn-voltar:hover { color: #fff; text-decoration: none; }
        .version-footer { text-align: center; margin-top: 16px; font-size: 9px; color: rgba(255,255,255,0.35); }
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

    <!-- Portaria (persistida) -->
    <div class="mobile-card">
        <div class="portaria-section">
            <label for="portaria_mobile"><i class="fas fa-door-open mr-1"></i> Portaria:</label>
            <input type="text" id="portaria_mobile" maxlength="1" autocomplete="off" placeholder="A">
        </div>

        <!-- Scanner -->
        <h2><i class="fas fa-camera"></i> Escanear QR Code</h2>
        <div id="qr-reader"></div>
        <div id="scanStatus" class="scan-status">Toque em Scan para abrir a câmera</div>
        <button type="button" class="btn-scan" id="btnScan" onclick="toggleScanner()">
            <i class="fas fa-camera mr-2"></i> Scan
        </button>

        <!-- Resultado lido -->
        <div id="resultBox" class="result-box">
            <div id="resultItems"></div>
        </div>

        <!-- Botão LIDO (aparece após leitura, submete o form) -->
        <button type="button" class="btn-lido" id="btnLido" onclick="submitCadastro()">
            <i class="fas fa-check-circle mr-2"></i> LIDO — Cadastrar
        </button>
    </div>

    <!-- Form oculto para submit -->
    <form method="post" action="<?php echo sanitize_for_html($_SERVER['PHP_SELF']); ?>" id="formMobile" style="display:none;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="cadastrar" value="1">
        <input type="hidden" name="mobile" value="1">
        <input type="hidden" name="portaria_cadastro" id="form_portaria" value="">
        <div id="hiddenFields"></div>
    </form>

    <div class="version-footer">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function() {
    let scanner = null;
    let isScanning = false;
    const qrReader = document.getElementById('qr-reader');
    const scanStatus = document.getElementById('scanStatus');
    const btnScan = document.getElementById('btnScan');
    const btnLido = document.getElementById('btnLido');
    const resultBox = document.getElementById('resultBox');
    const resultItems = document.getElementById('resultItems');
    const hiddenFields = document.getElementById('hiddenFields');
    const formPortaria = document.getElementById('form_portaria');
    const portariaInput = document.getElementById('portaria_mobile');

    // Persistir portaria
    const saved = localStorage.getItem('ebi_mobile_portaria');
    if (saved) portariaInput.value = saved;
    portariaInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
        if (this.value) localStorage.setItem('ebi_mobile_portaria', this.value);
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
        if (!portariaInput.value.trim()) {
            portariaInput.focus();
            showToast('Defina a Portaria antes de escanear', 'error');
            return;
        }
        if (isScanning) {
            stopScanner();
        } else {
            startScanner();
        }
    };

    function startScanner() {
        // Reset estado anterior
        btnLido.classList.remove('show');
        resultBox.classList.remove('show');
        hiddenFields.innerHTML = '';

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
            scanStatus.textContent = 'Aponte a câmera para o QR Code...';
            scanStatus.className = 'scan-status';
        }).catch(function(err) {
            scanStatus.textContent = 'Erro ao acessar câmera: ' + err;
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
            });
        }
    }

    function parseQRData(text) {
        // Formato v1 (5 colunas): NomeCriança \t Responsável \t Idade \t Telefone \t Comum
        // Formato v2 (6 colunas): NomeCriança \t Responsável \t Idade \t Telefone \t Comum \t DataNascimento
        // Múltiplas crianças separadas por \r
        const lines = text.split(/\r|\n/).filter(function(l) { return l.trim() !== ''; });
        const results = [];

        for (let i = 0; i < lines.length; i++) {
            const parts = lines[i].split('\t');
            if (parts.length < 5) continue;

            const entry = {
                nome_crianca: parts[0].trim(),
                nome_responsavel: parts[1].trim(),
                idade: parts[2].trim(),
                telefone: parts[3].trim(),
                comum: parts[4].trim(),
                data_nascimento: parts.length >= 6 ? parts[5].trim() : ''
            };

            if (entry.nome_crianca && entry.nome_responsavel) {
                results.push(entry);
            }
        }
        return results;
    }

    function onScanSuccess(decodedText) {
        const parsed = parseQRData(decodedText);
        if (parsed.length === 0) {
            scanStatus.textContent = 'QR inválido — formato não reconhecido';
            scanStatus.className = 'scan-status error';
            return;
        }

        // Parar scanner
        stopScanner();

        // Vibrar feedback
        if (navigator.vibrate) navigator.vibrate(200);

        // Mostrar dados lidos
        scanStatus.textContent = parsed.length + ' criança(s) lida(s)!';
        scanStatus.className = 'scan-status success';

        let html = '';
        let fields = '';
        for (let i = 0; i < parsed.length; i++) {
            const d = parsed[i];
            html += '<div class="item"><span class="label">Criança</span><span class="value">' + esc(d.nome_crianca) + '</span></div>';
            html += '<div class="item"><span class="label">Responsável</span><span class="value">' + esc(d.nome_responsavel) + '</span></div>';
            html += '<div class="item"><span class="label">Idade</span><span class="value">' + esc(d.idade) + ' anos</span></div>';
            html += '<div class="item"><span class="label">Comum</span><span class="value">' + esc(d.comum) + '</span></div>';
            if (d.data_nascimento) {
                html += '<div class="item"><span class="label">Nasc.</span><span class="value">' + esc(d.data_nascimento) + '</span></div>';
            }
            if (i < parsed.length - 1) html += '<hr style="margin:4px 0;border-color:rgba(31,157,97,0.2)">';

            fields += '<input type="hidden" name="nome_crianca[]" value="' + attr(d.nome_crianca) + '">';
            fields += '<input type="hidden" name="nome_responsavel[]" value="' + attr(d.nome_responsavel) + '">';
            fields += '<input type="hidden" name="idade[]" value="' + attr(d.idade) + '">';
            fields += '<input type="hidden" name="telefone[]" value="' + attr(d.telefone) + '">';
            fields += '<input type="hidden" name="comum[]" value="' + attr(d.comum) + '">';
        }

        resultItems.innerHTML = html;
        resultBox.classList.add('show');
        hiddenFields.innerHTML = fields;

        // Mostrar botão LIDO
        btnLido.classList.add('show');
    }

    window.submitCadastro = function() {
        formPortaria.value = portariaInput.value.toUpperCase();
        if (!formPortaria.value) {
            showToast('Defina a Portaria!', 'error');
            return;
        }
        document.getElementById('formMobile').submit();
    };

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function attr(s) {
        return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
})();
</script>
</body>
</html>
