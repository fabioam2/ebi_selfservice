<?php
/**
 * View Mobile — Cadastro rápido por QR Code.
 * Acesso via ?acao=mobile no ebi.test.php ou index.php.
 * Variáveis disponíveis: $mensagemSucesso, $mensagemErro, $todosOsCadastros
 */

// Filtrar cadastros feitos hoje pela portaria salva (para a lista)
$portariaFiltro = strtoupper(trim($_GET['p'] ?? ''));
$hoje = date('Y-m-d');
$cadastrosHojeMobile = [];
foreach ($todosOsCadastros as $c) {
    $criadoEm = $c['created_at'] ?? '';
    if (strpos($criadoEm, $hoje) === 0) {
        $cadastrosHojeMobile[] = $c;
    }
}
// Ordenar por mais recente primeiro
$cadastrosHojeMobile = array_reverse($cadastrosHojeMobile);
$totalHoje = count($cadastrosHojeMobile);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <title>EBI Mobile – Cadastro por QR Code</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #0f766e; --bg-2: #0b4f8a;
            --text-main: #10273b; --text-soft: #4b647c;
            --brand: #0e7490; --brand-strong: #0b5f76;
            --brand-soft: rgba(14,116,144,0.14);
            --success-bg: #dff8ea; --success-border: #1f9d61;
            --danger: #b91c1c; --danger-bg: #fee2e2;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: linear-gradient(130deg, var(--bg-1) 0%, var(--bg-2) 58%, #083358 100%);
            min-height: 100vh;
            font-family: 'Manrope', sans-serif;
            padding: 14px 10px 30px;
            display: flex; flex-direction: column; align-items: center;
        }
        .page { width: 100%; max-width: 420px; }
        .header { text-align: center; color: #fff; margin-bottom: 14px; }
        .header h1 { font-size: 1.3rem; font-weight: 800; }
        .header p { font-size: 0.75rem; opacity: 0.7; margin-top: 2px; }
        .card {
            background: rgba(255,255,255,0.98); padding: 18px 16px;
            border-radius: 16px; box-shadow: 0 10px 30px rgba(1,27,49,0.3);
            margin-bottom: 12px;
        }
        .card h2 { font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 10px; display: flex; align-items: center; gap: 7px; }
        .card h2 i { color: var(--brand); }

        .portaria-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding: 10px 12px; background: var(--brand-soft); border-radius: 10px; }
        .portaria-row label { font-size: 0.8rem; font-weight: 600; color: var(--text-main); }
        .portaria-row input { width: 44px; text-align: center; text-transform: uppercase; font-weight: 700; font-size: 1.1rem; border: 2px solid var(--brand); border-radius: 8px; padding: 5px; }
        .portaria-row input:focus { outline: none; border-color: var(--brand-strong); }
        .portaria-row .badge-total { font-size: 0.72rem; font-weight: 700; background: var(--brand); color: #fff; padding: 3px 8px; border-radius: 10px; margin-left: auto; }

        #qr-reader { width: 100%; border-radius: 10px; overflow: hidden; display: none; margin-bottom: 10px; }
        .scan-status { text-align: center; font-size: 0.78rem; color: var(--text-soft); padding: 6px; }
        .scan-status.ok { color: var(--success-border); font-weight: 700; }
        .scan-status.err { color: var(--danger); font-weight: 600; }

        .btn-scan {
            width: 100%; padding: 14px; border: none; border-radius: 12px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff; font-weight: 700; font-size: 1rem; cursor: pointer;
        }
        .btn-scan.active { background: linear-gradient(135deg, #dc2626, #b91c1c); }

        .result-box { display: none; background: var(--success-bg); border: 1px solid var(--success-border); border-radius: 10px; padding: 12px; margin: 10px 0; }
        .result-box.show { display: block; }
        .result-box .line { font-size: 0.78rem; padding: 3px 0; color: var(--text-main); display: flex; justify-content: space-between; }
        .result-box .line .lbl { color: var(--text-soft); }

        .btn-cadastrar {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: #fff; font-weight: 800; font-size: 1.1rem; cursor: pointer;
            display: none; margin-top: 10px; animation: pulse 1.5s infinite;
        }
        .btn-cadastrar.show { display: block; }
        @keyframes pulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(22,163,74,0.4); }
            50% { box-shadow: 0 0 0 10px rgba(22,163,74,0); }
        }

        .manual-toggle {
            width: 100%; border: 0; background: transparent; color: var(--brand-strong);
            padding: 2px 0; display: flex; align-items: center; justify-content: space-between;
            font: inherit; font-size: 0.85rem; font-weight: 800; cursor: pointer;
        }
        .manual-toggle i:last-child { transition: transform 0.2s ease; }
        .manual-toggle[aria-expanded="true"] i:last-child { transform: rotate(180deg); }
        .manual-form { display: none; margin-top: 14px; padding-top: 14px; border-top: 1px solid #dce6ed; }
        .manual-form.show { display: block; }
        .manual-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .manual-field { min-width: 0; }
        .manual-field.full { grid-column: 1 / -1; }
        .manual-field label { display: block; margin-bottom: 4px; color: var(--text-main); font-size: 0.72rem; font-weight: 700; }
        .manual-field input, .manual-field select {
            width: 100%; min-height: 39px; border: 1px solid #b7c8d6; border-radius: 8px;
            padding: 8px; background: #fff; color: var(--text-main); font: inherit; font-size: 0.8rem;
        }
        .manual-field input:focus, .manual-field select:focus { outline: 2px solid rgba(14,116,144,0.28); border-color: var(--brand); }
        .manual-submit {
            width: 100%; margin-top: 14px; min-height: 45px; border: 0; border-radius: 10px;
            background: var(--brand); color: #fff; font: inherit; font-size: 0.9rem; font-weight: 800; cursor: pointer;
        }

        .lista-card { max-height: 260px; overflow-y: auto; }
        .lista-item { display: flex; align-items: center; padding: 7px 0; border-bottom: 1px solid #eee; font-size: 0.78rem; }
        .lista-item:last-child { border-bottom: none; }
        .lista-item .nome { font-weight: 600; color: var(--text-main); flex: 1; }
        .lista-item .info { color: var(--text-soft); font-size: 0.7rem; }
        .lista-empty { text-align: center; color: var(--text-soft); font-size: 0.78rem; padding: 14px 0; }

        .toast {
            display: none; position: fixed; top: 14px; left: 50%; transform: translateX(-50%);
            padding: 10px 18px; border-radius: 10px; font-size: 0.82rem; font-weight: 600;
            z-index: 9999; max-width: 88%; text-align: center; animation: slideD 0.3s ease-out;
        }
        .toast.ok { background: var(--success-bg); border: 1px solid var(--success-border); color: #166534; }
        .toast.err { background: var(--danger-bg); border: 1px solid var(--danger); color: var(--danger); }
        @keyframes slideD { from { opacity:0; transform:translate(-50%,-16px); } to { opacity:1; transform:translate(-50%,0); } }

        .back-link { display: inline-flex; align-items: center; gap: 4px; color: rgba(255,255,255,0.65); font-size: 0.75rem; text-decoration: none; margin-bottom: 10px; }
        .back-link:hover { color: #fff; text-decoration: none; }
        .footer { text-align: center; margin-top: 12px; font-size: 9px; color: rgba(255,255,255,0.3); }

        .btn-tour {
            margin-top: 8px;
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.5);
            background: transparent;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-tour:active { background: rgba(255,255,255,0.15); }

        /* ===== Tour Guiado ===== */
        #tourOverlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 2000;
            display: none;
        }
        .tour-alvo-destacado {
            position: relative;
            z-index: 2001;
            box-shadow: 0 0 0 4px #fff, 0 0 0 8px #f59e0b, 0 0 25px rgba(0,0,0,0.5);
            border-radius: 10px;
        }
        #tourTooltip {
            position: fixed;
            z-index: 2002;
            max-width: calc(100vw - 24px);
            width: 300px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 6px 24px rgba(0,0,0,0.35);
            padding: 14px 16px;
            display: none;
        }
        #tourTooltip .tour-titulo {
            font-weight: 800;
            font-size: 0.9rem;
            color: var(--brand);
            margin-bottom: 6px;
        }
        #tourTooltip .tour-texto {
            font-size: 0.8rem;
            color: var(--text-main);
            margin-bottom: 10px;
        }
        #tourTooltip .tour-rodape {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 6px;
        }
        #tourTooltip .tour-passo-contador {
            font-size: 0.68rem;
            color: var(--text-soft);
        }
        #tourTooltip button {
            border: none;
            border-radius: 8px;
            padding: 5px 10px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
        }
        #tourBtnPular { background: transparent; color: var(--text-soft); }
        #tourBtnAnterior { background: #e5e7eb; color: var(--text-main); }
        #tourBtnAnterior:disabled { opacity: 0.4; cursor: default; }
        #tourBtnProximo { background: var(--brand); color: #fff; }
    </style>
</head>
<body>
<div class="page">
    <a href="<?php echo sanitize_for_html($_SERVER['PHP_SELF']); ?>" class="back-link"><i class="fas fa-arrow-left"></i> Voltar</a>

    <div class="header">
        <h1><i class="fas fa-mobile-alt"></i> EBI Mobile</h1>
        <p>Cadastro rápido por QR Code</p>
        <button type="button" class="btn-tour" id="btnTourGuiado" onclick="iniciarTourGuiado()"><i class="fas fa-question-circle mr-1"></i> Tour Guiado</button>
    </div>

    <div id="toast" class="toast"></div>
    <?php if ($mensagemSucesso): ?>
        <script>document.addEventListener('DOMContentLoaded',function(){toast('<?php echo addslashes($mensagemSucesso); ?>','ok')});</script>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <script>document.addEventListener('DOMContentLoaded',function(){toast('<?php echo addslashes($mensagemErro); ?>','err')});</script>
    <?php endif; ?>

    <!-- Scanner + Cadastro -->
    <div class="card">
        <div class="portaria-row">
            <label><i class="fas fa-door-open"></i> Portaria:</label>
            <input type="text" id="portaria" maxlength="1" autocomplete="off" placeholder="A">
            <span class="badge-total"><?php echo $totalHoje; ?> hoje</span>
        </div>

        <h2><i class="fas fa-qrcode"></i> Scanner</h2>
        <div id="qr-reader"></div>
        <div id="status" class="scan-status">Toque em Scan para abrir a câmera</div>
        <button type="button" class="btn-scan" id="btnScan" onclick="scan()"><i class="fas fa-camera mr-1"></i> Scan</button>

        <div id="result" class="result-box"><div id="resultData"></div></div>

        <button type="button" class="btn-cadastrar" id="btnCad" onclick="cadastrar()">
            <i class="fas fa-check-circle mr-1"></i> Cadastrar
        </button>
    </div>

    <div class="card">
        <button type="button" class="manual-toggle" id="btnManual" aria-expanded="false" aria-controls="cadastroManual">
            <span><i class="fas fa-keyboard"></i> Cadastro manual</span>
            <i class="fas fa-chevron-down" aria-hidden="true"></i>
        </button>
        <form method="post" action="<?php echo sanitize_for_html($_SERVER['PHP_SELF']); ?>?acao=mobile" id="cadastroManual" class="manual-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="cadastrar" value="1">
            <input type="hidden" name="mobile" value="1">
            <input type="hidden" name="portaria_cadastro" id="manualPortaria" value="">
            <div class="manual-grid">
                <div class="manual-field full">
                    <label for="manualNomeCrianca">Nome da criança</label>
                    <input type="text" id="manualNomeCrianca" name="nome_crianca[]" autocomplete="name" required>
                </div>
                <div class="manual-field full">
                    <label for="manualNomeResponsavel">Nome do responsável</label>
                    <input type="text" id="manualNomeResponsavel" name="nome_responsavel[]" autocomplete="name" required>
                </div>
                <div class="manual-field">
                    <label for="manualIdade">Idade</label>
                    <input type="number" id="manualIdade" name="idade[]" min="0" max="17" inputmode="numeric" required>
                </div>
                <div class="manual-field">
                    <label for="manualTelefone">Telefone</label>
                    <input type="tel" id="manualTelefone" name="telefone[]" autocomplete="tel" required>
                </div>
                <div class="manual-field full">
                    <label for="manualComum">Comum</label>
                    <input type="text" id="manualComum" name="comum[]" required>
                </div>
                <div class="manual-field">
                    <label for="manualCidade">Cidade</label>
                    <input type="text" id="manualCidade" name="cidade[]" autocomplete="address-level2">
                </div>
                <div class="manual-field">
                    <label for="manualEstado">UF</label>
                    <select id="manualEstado" name="estado[]" autocomplete="address-level1">
                        <option value="">UF</option>
                        <?php foreach (['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'] as $uf): ?>
                            <option value="<?php echo $uf; ?>"><?php echo $uf; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="manual-field">
                    <label for="manualSexo">Sexo</label>
                    <select id="manualSexo" name="sexo[]">
                        <option value="">Não informado</option>
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                        <option value="X">Outro</option>
                    </select>
                </div>
                <div class="manual-field">
                    <label for="manualNascimento">Nascimento</label>
                    <input type="text" id="manualNascimento" name="data_nascimento[]" inputmode="numeric" placeholder="dd/mm/aaaa" maxlength="10">
                </div>
            </div>
            <button type="submit" class="manual-submit"><i class="fas fa-check-circle"></i> Cadastrar criança</button>
        </form>
    </div>

    <!-- Lista dos cadastros de hoje -->
    <div class="card" id="listaHojeCard">
        <h2><i class="fas fa-list"></i> Cadastros de Hoje (<?php echo $totalHoje; ?>)</h2>
        <div class="lista-card">
            <?php if ($totalHoje > 0): ?>
                <?php foreach ($cadastrosHojeMobile as $c): ?>
                    <div class="lista-item">
                        <span class="nome"><?php
                            echo sanitize_for_html($c['nomeCrianca']);
                            if (function_exists('verificarAniversario')) {
                                $tag = verificarAniversario($c['dataNascimento'] ?? '');
                                if ($tag === 'hoje') echo ' 🎂';
                                elseif ($tag === 'semana') echo ' 🎈';
                            }
                        ?></span>
                        <span class="info"><?php
                            $dn = $c['dataNascimento'] ?? '';
                            $dnShort = $dn ? substr($dn, 0, 5) : '';
                            echo sanitize_for_html($c['portaria']) . ' · ' . sanitize_for_html($c['idade']) . 'a';
                            if ($dnShort) echo ' · ' . sanitize_for_html($dnShort);
                        ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="lista-empty"><i class="fas fa-inbox" style="font-size:1.5rem;opacity:0.3;display:block;margin-bottom:6px;"></i>Nenhum cadastro hoje</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Form oculto -->
    <form method="post" action="<?php echo sanitize_for_html($_SERVER['PHP_SELF']); ?>?acao=mobile" id="frm" style="display:none;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="cadastrar" value="1">
        <input type="hidden" name="mobile" value="1">
        <input type="hidden" name="portaria_cadastro" id="frmPort" value="">
        <div id="frmFields"></div>
    </form>

    <div class="footer">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<?php if (QR_CODE_CRYPTO_ENABLED): ?>
<script src="<?php echo sanitize_for_html(ebi_qr_crypto_script_url()); ?>"></script>
<script>EbiQrCrypto.configure(<?php echo json_encode(QR_CODE_CRYPTO_KEY); ?>);</script>
<?php endif; ?>
<script><?php readfile(__DIR__ . '/../assets/compact-qr-reader.js'); ?></script>
<script>
(function(){
    let sc = null, scanning = false, reading = false, data = [];
    const el = id => document.getElementById(id);
    const port = el('portaria'), btn = el('btnScan'), status = el('status');
    const result = el('result'), resultData = el('resultData');
    const btnCad = el('btnCad'), frmPort = el('frmPort'), frmFields = el('frmFields');
    const btnManual = el('btnManual'), cadastroManual = el('cadastroManual'), manualPortaria = el('manualPortaria');

    // Persistir portaria
    const sv = localStorage.getItem('ebi_mobile_portaria');
    if (sv) port.value = sv;
    port.addEventListener('input', function(){ this.value=this.value.toUpperCase(); if(this.value) localStorage.setItem('ebi_mobile_portaria',this.value); });

    btnManual.addEventListener('click', function() {
        const aberto = cadastroManual.classList.toggle('show');
        btnManual.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        if (aberto) el('manualNomeCrianca').focus();
    });

    cadastroManual.addEventListener('submit', function(event) {
        const portaria = port.value.trim().toUpperCase();
        if (!/^[A-Z]$/.test(portaria)) {
            event.preventDefault();
            port.focus();
            toast('Defina a Portaria','err');
            return;
        }
        manualPortaria.value = portaria;
    });

    window.toast = function(msg, type) {
        const t = el('toast'); t.textContent=msg; t.className='toast '+type; t.style.display='block';
        setTimeout(()=>{ t.style.display='none'; }, 3500);
    };

    window.scan = function() {
        if (!port.value.trim()) { port.focus(); toast('Defina a Portaria','err'); return; }
        if (scanning) { stop(); return; }
        // Reset
        btnCad.classList.remove('show'); result.classList.remove('show'); reading=false; data=[]; frmFields.innerHTML='';
        el('qr-reader').style.display='block';
        sc = new Html5Qrcode('qr-reader');
        sc.start({facingMode:'environment'},{fps:10,qrbox:{width:200,height:200}}, onRead)
          .then(()=>{ scanning=true; btn.innerHTML='<i class="fas fa-stop mr-1"></i> Parar'; btn.classList.add('active'); status.textContent='Aponte para o QR Code...'; status.className='scan-status'; })
          .catch(e=>{ status.textContent='Erro câmera'; status.className='scan-status err'; el('qr-reader').style.display='none'; });
    };

    function stop() {
        if(sc&&scanning) sc.stop().then(()=>{ scanning=false; btn.innerHTML='<i class="fas fa-camera mr-1"></i> Scan'; btn.classList.remove('active'); el('qr-reader').style.display='none'; });
    }

    async function onRead(text) {
        if (reading) return;
        reading = true;
        if (text.indexOf('EBIQR1.') === 0) {
            if (!window.EbiQrCrypto) {
                reading=false; status.textContent='Leitor criptografado indisponível'; status.className='scan-status err'; return;
            }
            try {
                text = await EbiQrCrypto.decrypt(text);
                if (window.EbiQrCompact) {
                    text = window.EbiQrCompact.expandPayload(text);
                }
            } catch (error) {
                reading=false; status.textContent='QR criptografado inválido'; status.className='scan-status err'; return;
            }
        }
        const parsed = parseQrPayload(text);
        if(!parsed.length){ reading=false; status.textContent='QR inválido'; status.className='scan-status err'; return; }
        data = parsed;
        stop();
        if(navigator.vibrate) navigator.vibrate(200);
        status.textContent = parsed.length+' criança(s) lida(s)!';
        status.className = 'scan-status ok';
        // Render
        let h='';
        for(const d of data){
            h+='<div class="line"><span class="lbl">Criança</span><span>'+esc(d.nome)+'</span></div>';
            h+='<div class="line"><span class="lbl">Responsável</span><span>'+esc(d.resp)+'</span></div>';
            h+='<div class="line"><span class="lbl">Idade</span><span>'+esc(d.idade)+' anos</span></div>';
            h+='<div class="line"><span class="lbl">Comum</span><span>'+esc(d.comum)+'</span></div>';
            if(d.cidade || d.estado) h+='<div class="line"><span class="lbl">Cidade/UF</span><span>'+esc([d.cidade,d.estado].filter(Boolean).join(' - '))+'</span></div>';
        }
        resultData.innerHTML=h; result.classList.add('show');
        btnCad.classList.add('show');
    }

    window.cadastrar = function() {
        frmPort.value = port.value.toUpperCase();
        if(!frmPort.value){ toast('Defina a Portaria!','err'); return; }
        if(!data.length){ toast('Escaneie um QR primeiro','err'); return; }
        let f='';
        for(const d of data){
            f+='<input type="hidden" name="nome_crianca[]" value="'+attr(d.nome)+'">';
            f+='<input type="hidden" name="nome_responsavel[]" value="'+attr(d.resp)+'">';
            f+='<input type="hidden" name="idade[]" value="'+attr(d.idade)+'">';
            f+='<input type="hidden" name="telefone[]" value="'+attr(d.tel)+'">';
            f+='<input type="hidden" name="comum[]" value="'+attr(d.comum)+'">';
            f+='<input type="hidden" name="cidade[]" value="'+attr(d.cidade)+'">';
            f+='<input type="hidden" name="estado[]" value="'+attr(d.estado)+'">';
            f+='<input type="hidden" name="sexo[]" value="'+attr(d.sexo)+'">';
            f+='<input type="hidden" name="data_nascimento[]" value="'+attr(d.nasc)+'">';
        }
        frmFields.innerHTML=f;
        el('frm').submit();
    };

    function parseQrPayload(text) {
        const records = text.split(/\r\n|\r|\n/).map(record => record.trim()).filter(Boolean);

        // QR antigo: cada criança fica em uma linha com cinco campos separados por Tab.
        if (records.length > 1) {
            return records.map(parseQrRecord).filter(Boolean);
        }

        const fields = text.split('\t').map(field => field.trim());

        // QR v2 concatena crianças em uma única linha. O formato atual inclui
        // cidade e estado (nove campos); o formato anterior tinha sete campos.
        for (const fieldsPerChild of [9, 7]) {
            if (fields.length >= fieldsPerChild && fields.length % fieldsPerChild === 0 && isQrV2(fields, fieldsPerChild)) {
                const children = [];
                const sexoIndex = fieldsPerChild - 2;
                const nascimentoIndex = fieldsPerChild - 1;
                for (let index = 0; index < fields.length; index += fieldsPerChild) {
                    children.push({
                        nome: fields[index],
                        resp: fields[index + 1],
                        idade: fields[index + 2],
                        tel: fields[index + 3],
                        comum: fields[index + 4],
                        cidade: fieldsPerChild === 9 ? fields[index + 5] : '',
                        estado: fieldsPerChild === 9 ? fields[index + 6].toUpperCase() : '',
                        sexo: fields[index + sexoIndex].toUpperCase(),
                        nasc: fields[index + nascimentoIndex],
                    });
                }
                return children;
            }
        }

        const record = parseQrRecord(text);
        return record ? [record] : [];
    }

    function isQrV2(fields, fieldsPerChild) {
        const sexoIndex = fieldsPerChild - 2;
        const nascimentoIndex = fieldsPerChild - 1;
        for (let index = 0; index < fields.length; index += fieldsPerChild) {
            if (!/^[MFX]$/i.test(fields[index + sexoIndex]) || !/^\d{2}\/\d{2}\/\d{4}$/.test(fields[index + nascimentoIndex])) {
                return false;
            }
        }
        return true;
    }

    function parseQrRecord(record) {
        const fields = record.split('\t').map(field => field.trim());
        if (fields.length < 5) return null;

        const fieldsPerChild = fields.length === 9 && isQrV2(fields, 9)
            ? 9
            : fields.length === 7 && isQrV2(fields, 7)
                ? 7
                : 0;
        const sexo = fieldsPerChild > 0 ? fields[fieldsPerChild - 2].toUpperCase() : '';
        const nascimento = fieldsPerChild > 0 ? fields[fieldsPerChild - 1] : '';
        return {
            nome: fields[0],
            resp: fields[1],
            idade: fields[2],
            tel: fields[3],
            comum: fields[4],
            cidade: fieldsPerChild === 9 ? fields[5] : '',
            estado: fieldsPerChild === 9 ? fields[6].toUpperCase() : '',
            sexo: sexo,
            nasc: nascimento,
        };
    }

    function esc(s){ const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
    function attr(s){ return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
})();
</script>

<!-- Tour Guiado -->
<div id="tourOverlay"></div>
<div id="tourTooltip">
    <div class="tour-titulo"></div>
    <div class="tour-texto"></div>
    <div class="tour-rodape">
        <span class="tour-passo-contador"></span>
        <div>
            <button type="button" id="tourBtnPular">Pular</button>
            <button type="button" id="tourBtnAnterior">Anterior</button>
            <button type="button" id="tourBtnProximo">Próximo</button>
        </div>
    </div>
</div>
<script>
(function(){
    var tourPassos = [
        {
            seletor: '#portaria',
            titulo: 'Portaria de Entrada',
            texto: 'Define a portaria (padrão "A"), identifica por onde a criança entrou.',
            posicao: 'bottom'
        },
        {
            seletor: '#btnScan',
            titulo: 'Escanear QR Code',
            texto: 'Toque aqui para abrir a câmera e ler o QR Code do responsável — os dados da criança são preenchidos automaticamente.',
            posicao: 'top'
        },
        {
            seletor: '#btnCad',
            titulo: 'Confirmar Cadastro',
            texto: 'Depois de escanear, confira os dados e toque aqui para confirmar o cadastro e liberar a pulseira para impressão.',
            posicao: 'top'
        },
        {
            seletor: '#listaHojeCard',
            titulo: 'Cadastros de Hoje',
            texto: 'Acompanhe aqui a lista de crianças já cadastradas hoje, com portaria, idade e data de nascimento.',
            posicao: 'top'
        }
    ];

    var tourIndiceAtual = -1;

    function tourElVisivel(elAlvo) {
        if (!elAlvo) return false;
        var rect = elAlvo.getBoundingClientRect();
        var estilo = window.getComputedStyle(elAlvo);
        return estilo.display !== 'none' && estilo.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
    }

    function tourPosicionarTooltip(elAlvo, passo) {
        var rect = elAlvo.getBoundingClientRect();
        var tip = document.getElementById('tourTooltip');
        tip.querySelector('.tour-titulo').textContent = passo.titulo;
        tip.querySelector('.tour-texto').textContent = passo.texto;
        tip.querySelector('.tour-passo-contador').textContent = (tourIndiceAtual + 1) + ' de ' + tourPassos.length;
        tip.style.display = 'block';

        var margem = 12;
        var tipWidth = tip.offsetWidth;
        var tipHeight = tip.offsetHeight;
        var posicao = passo.posicao || 'bottom';
        var top, left;

        if (posicao === 'top') {
            top = rect.top - tipHeight - margem;
            left = rect.left;
        } else if (posicao === 'left') {
            top = rect.top;
            left = rect.left - tipWidth - margem;
        } else if (posicao === 'right') {
            top = rect.top;
            left = rect.right + margem;
        } else {
            top = rect.bottom + margem;
            left = rect.left;
        }

        if (left + tipWidth > window.innerWidth - margem) left = window.innerWidth - tipWidth - margem;
        if (left < margem) left = margem;
        if (top + tipHeight > window.innerHeight - margem) top = window.innerHeight - tipHeight - margem;
        if (top < margem) top = margem;

        tip.style.top = top + 'px';
        tip.style.left = left + 'px';

        document.getElementById('tourBtnAnterior').disabled = (tourIndiceAtual === 0);
        document.getElementById('tourBtnProximo').textContent = (tourIndiceAtual === tourPassos.length - 1) ? 'Concluir' : 'Próximo';
    }

    function tourEsconderPassoAtual() {
        var passo = tourPassos[tourIndiceAtual];
        if (!passo) return;
        var elAlvo = document.querySelector(passo.seletor);
        if (elAlvo) elAlvo.classList.remove('tour-alvo-destacado');
        if (typeof passo.depois === 'function') passo.depois();
    }

    function tourMostrarPasso(indice) {
        var passo = tourPassos[indice];
        if (!passo) return;

        setTimeout(function() {
            if (typeof passo.antes === 'function') passo.antes();

            setTimeout(function() {
                var elAlvo = document.querySelector(passo.seletor);
                if (!tourElVisivel(elAlvo)) {
                    tourProximoPasso();
                    return;
                }
                elAlvo.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(function() {
                    elAlvo.classList.add('tour-alvo-destacado');
                    tourPosicionarTooltip(elAlvo, passo);
                }, 250);
            }, 120);
        }, 0);
    }

    function tourProximoPasso() {
        tourEsconderPassoAtual();
        tourIndiceAtual++;
        if (tourIndiceAtual >= tourPassos.length) {
            tourEncerrar();
            return;
        }
        tourMostrarPasso(tourIndiceAtual);
    }

    function tourPassoAnterior() {
        if (tourIndiceAtual <= 0) return;
        tourEsconderPassoAtual();
        tourIndiceAtual--;
        tourMostrarPasso(tourIndiceAtual);
    }

    function tourEncerrar() {
        tourEsconderPassoAtual();
        document.getElementById('tourOverlay').style.display = 'none';
        document.getElementById('tourTooltip').style.display = 'none';
        tourIndiceAtual = -1;
        localStorage.setItem('ebi_tour_guiado_mobile_visto', '1');
    }

    window.iniciarTourGuiado = function() {
        tourIndiceAtual = -1;
        document.getElementById('tourOverlay').style.display = 'block';
        tourProximoPasso();
    };

    document.getElementById('tourBtnProximo').addEventListener('click', tourProximoPasso);
    document.getElementById('tourBtnAnterior').addEventListener('click', tourPassoAnterior);
    document.getElementById('tourBtnPular').addEventListener('click', tourEncerrar);
    window.addEventListener('resize', function() {
        var passo = tourPassos[tourIndiceAtual];
        if (!passo) return;
        var elAlvo = document.querySelector(passo.seletor);
        if (elAlvo && document.getElementById('tourTooltip').style.display !== 'none') {
            tourPosicionarTooltip(elAlvo, passo);
        }
    });

    // Mostrar automaticamente no primeiro acesso
    if (!localStorage.getItem('ebi_tour_guiado_mobile_visto')) {
        setTimeout(window.iniciarTourGuiado, 700);
    }
})();
</script>
</body>
</html>
