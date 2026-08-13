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
    <title>Reunião EBI Mobile – Credenciamento por QR Code</title>
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
    </style>
</head>
<body>
<div class="page">
    <a href="<?php echo sanitize_for_html($_SERVER['PHP_SELF']); ?>" class="back-link"><i class="fas fa-arrow-left"></i> Voltar</a>

    <div class="header">
        <h1><i class="fas fa-mobile-alt"></i> Reunião EBI</h1>
        <p>Credenciamento por QR Code</p>
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

    <!-- Lista dos cadastros de hoje -->
    <div class="card" id="listaHojeCard">
        <h2><i class="fas fa-list"></i> Registros de Hoje (<?php echo $totalHoje; ?>)</h2>
        <div class="lista-card">
            <?php if ($totalHoje > 0): ?>
                <?php foreach ($cadastrosHojeMobile as $c): ?>
                    <div class="lista-item">
                        <span class="nome"><?php echo sanitize_for_html($c['nomeCrianca']); ?></span>
                        <span class="info"><?php
                            echo sanitize_for_html($c['nomeResponsavel']) . ' · ' . sanitize_for_html($c['portaria']);
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
<script>
(function(){
    let sc = null, scanning = false, data = [];
    const el = id => document.getElementById(id);
    const port = el('portaria'), btn = el('btnScan'), status = el('status');
    const result = el('result'), resultData = el('resultData');
    const btnCad = el('btnCad'), frmPort = el('frmPort'), frmFields = el('frmFields');

    // Persistir portaria
    const sv = localStorage.getItem('ebi_mobile_portaria');
    if (sv) port.value = sv;
    port.addEventListener('input', function(){ this.value=this.value.toUpperCase(); if(this.value) localStorage.setItem('ebi_mobile_portaria',this.value); });

    window.toast = function(msg, type) {
        const t = el('toast'); t.textContent=msg; t.className='toast '+type; t.style.display='block';
        setTimeout(()=>{ t.style.display='none'; }, 3500);
    };

    window.scan = function() {
        if (!port.value.trim()) { port.focus(); toast('Defina a Portaria','err'); return; }
        if (scanning) { stop(); return; }
        // Reset
        btnCad.classList.remove('show'); result.classList.remove('show'); data=[]; frmFields.innerHTML='';
        el('qr-reader').style.display='block';
        sc = new Html5Qrcode('qr-reader');
        sc.start({facingMode:'environment'},{fps:10,qrbox:{width:200,height:200}}, onRead)
          .then(()=>{ scanning=true; btn.innerHTML='<i class="fas fa-stop mr-1"></i> Parar'; btn.classList.add('active'); status.textContent='Aponte para o QR Code...'; status.className='scan-status'; })
          .catch(e=>{ status.textContent='Erro câmera'; status.className='scan-status err'; el('qr-reader').style.display='none'; });
    };

    function stop() {
        if(sc&&scanning) sc.stop().then(()=>{ scanning=false; btn.innerHTML='<i class="fas fa-camera mr-1"></i> Scan'; btn.classList.remove('active'); el('qr-reader').style.display='none'; });
    }

    function onRead(text) {
        const parsed = parseQrPayload(text);
        if(!parsed.length){ status.textContent='QR inválido'; status.className='scan-status err'; return; }
        data = parsed;
        stop();
        if(navigator.vibrate) navigator.vibrate(200);
        status.textContent = parsed.length+' registro(s) lido(s)!';
        status.className = 'scan-status ok';
        // Render
        let h='';
        for(const d of data){
            h+='<div class="line"><span class="lbl">Função</span><span>'+esc(d.nome)+'</span></div>';
            h+='<div class="line"><span class="lbl">Nome</span><span>'+esc(d.resp)+'</span></div>';
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

        // QR antigo: cada registro fica em uma linha com cinco campos separados por Tab.
        if (records.length > 1) {
            return records.map(parseQrRecord).filter(Boolean);
        }

        const fields = text.split('\t').map(field => field.trim());

        // O QR atual usa nove campos com cidade e estado; o formato anterior tinha sete campos.
        // cidade e estado (nove campos); o formato anterior tinha sete campos.
        for (const fieldsPerChild of [9, 7]) {
            if (fields.length >= fieldsPerChild && fields.length % fieldsPerChild === 0 && isQrV2(fields, fieldsPerChild)) {
                const registros = [];
                const sexoIndex = fieldsPerChild - 2;
                const nascimentoIndex = fieldsPerChild - 1;
                for (let index = 0; index < fields.length; index += fieldsPerChild) {
                    registros.push({
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
                return registros;
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
</body>
</html>
