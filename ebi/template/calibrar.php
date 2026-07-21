<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elgin L42 DT — Configuração & Calibração</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0c0d12;
            --surface: #14161e;
            --surface-2: #1c1f2b;
            --surface-3: #252838;
            --border: #2a2d40;
            --border-light: #363a52;
            --accent: #7c6cf0;
            --accent-glow: rgba(124,108,240,0.25);
            --accent-light: #b4a8ff;
            --success: #22c997;
            --success-glow: rgba(34,201,151,0.2);
            --warning: #f5b942;
            --warning-glow: rgba(245,185,66,0.15);
            --danger: #ef6461;
            --danger-glow: rgba(239,100,97,0.15);
            --info: #5ba4f5;
            --text: #e4e2f0;
            --text-dim: #8b89a0;
            --text-faint: #5e5c72;
            --mono: 'JetBrains Mono', monospace;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ===== HEADER ===== */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(12px);
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .logo {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--accent), #5849d6);
            border-radius: 10px;
            display: grid; place-items: center;
            font-size: 20px;
            box-shadow: 0 4px 20px var(--accent-glow);
        }
        .header h1 {
            font-size: 20px; font-weight: 600; letter-spacing: -0.3px;
        }
        .header h1 span { color: var(--text-dim); font-weight: 300; }

        .conn-badge {
            display: flex; align-items: center; gap: 8px;
            padding: 7px 14px;
            border-radius: 50px;
            font-size: 13px; font-weight: 500;
            border: 1px solid var(--border);
            background: var(--surface-2);
            cursor: pointer;
            transition: all .25s;
        }
        .conn-badge:hover { border-color: var(--accent); }
        .conn-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--danger);
            transition: all .3s;
        }
        .conn-dot.on {
            background: var(--success);
            box-shadow: 0 0 10px rgba(34,201,151,.7);
        }

        /* ===== LAYOUT ===== */
        .page { max-width: 1100px; margin: 0 auto; padding: 32px 24px 64px; }

        /* Section cards */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            margin-bottom: 24px;
            overflow: hidden;
        }
        .card-head {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-head h2 {
            font-size: 16px; font-weight: 600;
            display: flex; align-items: center; gap: 10px;
        }
        .card-head .badge {
            font-size: 11px; font-weight: 600;
            padding: 3px 10px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .badge-step { background: var(--accent-glow); color: var(--accent-light); }
        .badge-ok   { background: var(--success-glow); color: var(--success); }
        .badge-warn { background: var(--warning-glow); color: var(--warning); }
        .card-body { padding: 24px; }

        /* ===== FORMS ===== */
        .fg { display: flex; flex-direction: column; gap: 5px; }
        .fg label {
            font-size: 12px; font-weight: 500;
            color: var(--text-dim);
            letter-spacing: .3px;
        }
        .fg input, .fg select, .fg textarea {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 9px 12px;
            color: var(--text);
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            outline: none;
            transition: all .2s;
        }
        .fg input:focus, .fg select:focus, .fg textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        .fg textarea {
            font-family: var(--mono);
            font-size: 12px;
            resize: vertical;
            min-height: 80px;
        }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .row3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
        .row4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 14px; }
        .gap14 { display: flex; flex-direction: column; gap: 14px; }

        /* ===== PARAM TABLE ===== */
        .param-grid {
            display: grid;
            grid-template-columns: 200px 1fr 130px;
            gap: 0;
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            font-size: 13px;
        }
        .param-grid .ph {
            background: var(--surface-3);
            padding: 10px 14px;
            font-weight: 600;
            color: var(--text-dim);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid var(--border);
        }
        .param-grid .pc {
            padding: 8px 14px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
        }
        .param-grid .pc:nth-child(6n+4),
        .param-grid .pc:nth-child(6n+5),
        .param-grid .pc:nth-child(6n+6) {
            background: rgba(255,255,255,.015);
        }
        .param-grid .pc input {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 5px 8px;
            color: var(--text);
            font-family: var(--mono);
            font-size: 12px;
            width: 100%;
            outline: none;
            transition: all .2s;
        }
        .param-grid .pc input:focus {
            border-color: var(--accent);
        }
        .param-grid .pc select {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 5px 8px;
            color: var(--text);
            font-size: 12px;
            width: 100%;
            outline: none;
        }
        .cmd-tag {
            font-family: var(--mono);
            font-size: 11px;
            color: var(--warning);
            background: var(--warning-glow);
            padding: 2px 7px;
            border-radius: 4px;
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 7px;
            padding: 10px 18px;
            border-radius: 9px;
            font-family: 'Outfit', sans-serif;
            font-size: 13px; font-weight: 600;
            cursor: pointer; border: none;
            transition: all .2s;
        }
        .btn-accent {
            background: linear-gradient(135deg, var(--accent), #5e4fd4);
            color: #fff;
            box-shadow: 0 4px 18px var(--accent-glow);
        }
        .btn-accent:hover { transform: translateY(-1px); box-shadow: 0 6px 24px var(--accent-glow); }
        .btn-success {
            background: linear-gradient(135deg, var(--success), #1aad82);
            color: #fff;
            box-shadow: 0 4px 18px var(--success-glow);
        }
        .btn-success:hover { transform: translateY(-1px); }
        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #d14543);
            color: #fff;
        }
        .btn-danger:hover { transform: translateY(-1px); }
        .btn-outline {
            background: var(--surface-2);
            color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-outline:hover { border-color: var(--accent); }
        .btn-warn {
            background: linear-gradient(135deg, var(--warning), #e0a52e);
            color: var(--bg);
        }
        .btn-sm { padding: 7px 12px; font-size: 12px; }
        .btn-block { width: 100%; }
        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; }

        /* ===== WORKFLOW STEPS ===== */
        .wf-steps {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .wf-step {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
            transition: all .3s;
        }
        .wf-step:last-child { border-bottom: none; }
        .wf-num {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: var(--surface-3);
            border: 1px solid var(--border);
            display: grid; place-items: center;
            font-family: var(--mono);
            font-size: 13px; font-weight: 700;
            flex-shrink: 0;
            transition: all .3s;
        }
        .wf-step.running .wf-num {
            background: var(--warning);
            border-color: var(--warning);
            color: var(--bg);
            animation: stepPulse 1.5s infinite;
        }
        .wf-step.done .wf-num {
            background: var(--success);
            border-color: var(--success);
            color: #fff;
        }
        .wf-step.error .wf-num {
            background: var(--danger);
            border-color: var(--danger);
            color: #fff;
        }
        .wf-step.skipped { opacity: .4; }
        @keyframes stepPulse {
            0%,100% { box-shadow: 0 0 0 0 var(--warning-glow); }
            50% { box-shadow: 0 0 0 6px var(--warning-glow); }
        }
        .wf-info { flex: 1; }
        .wf-info h4 { font-size: 14px; font-weight: 600; margin-bottom: 3px; }
        .wf-info p { font-size: 12px; color: var(--text-dim); line-height: 1.5; }
        .wf-info .wf-cmd {
            font-family: var(--mono);
            font-size: 11px;
            color: var(--accent-light);
            margin-top: 4px;
        }
        .wf-action { flex-shrink: 0; }

        /* ===== LOG ===== */
        .log-box {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
            font-family: var(--mono);
            font-size: 11px;
            line-height: 1.7;
            max-height: 260px;
            overflow-y: auto;
            color: var(--text-dim);
        }
        .log-box .log-ok   { color: var(--success); }
        .log-box .log-err  { color: var(--danger); }
        .log-box .log-warn { color: var(--warning); }
        .log-box .log-cmd  { color: var(--accent-light); }
        .log-box .log-ts   { color: var(--text-faint); }

        /* ===== TOAST ===== */
        .toasts {
            position: fixed; top: 16px; right: 16px;
            z-index: 9999;
            display: flex; flex-direction: column; gap: 8px;
        }
        .toast {
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13px; font-weight: 500;
            display: flex; align-items: center; gap: 8px;
            box-shadow: 0 8px 30px rgba(0,0,0,.4);
            animation: toastIn .35s ease;
            min-width: 280px;
        }
        .toast.s { background: linear-gradient(135deg, var(--success), #1aad82); color: #fff; }
        .toast.e { background: linear-gradient(135deg, var(--danger), #d14543); color: #fff; }
        .toast.i { background: linear-gradient(135deg, var(--accent), #5e4fd4); color: #fff; }
        .toast.w { background: linear-gradient(135deg, var(--warning), #e0a52e); color: var(--bg); }
        @keyframes toastIn { from { transform: translateX(80px); opacity:0; } to { transform: none; opacity:1; } }

        /* Spinner */
        .spin { width:16px; height:16px; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation: sp .5s linear infinite; display:inline-block; }
        @keyframes sp { to { transform:rotate(360deg); } }

        /* File hidden input */
        .file-hidden { display: none; }

        /* Printer select */
        .printer-row {
            display: flex; gap: 10px; align-items: flex-end;
        }
        .printer-row .fg { flex: 1; }
        .printer-row .btn { height: 38px; }

        /* Divider */
        .sep { height: 1px; background: var(--border); margin: 8px 0; }

        /* Note box */
        .note {
            font-size: 12px; line-height: 1.6;
            color: var(--text-dim);
            padding: 14px;
            border-radius: 10px;
            border: 1px solid;
        }
        .note-info  { border-color: rgba(91,164,245,.2); background: rgba(91,164,245,.05); }
        .note-warn  { border-color: rgba(245,185,66,.2);  background: rgba(245,185,66,.05); }
        .note strong { color: var(--text); }

        /* Backup file card */
        .backup-card {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .backup-card .bc-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            background: var(--accent-glow);
            display: grid; place-items: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .backup-card .bc-info { flex: 1; }
        .backup-card .bc-info h4 { font-size: 14px; }
        .backup-card .bc-info p { font-size: 12px; color: var(--text-dim); }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="header-left">
        <div class="logo">⚙️</div>
        <h1>Elgin L42 DT <span>/ Configuração & Calibração</span></h1>
    </div>
    <div class="conn-badge" onclick="toggleConn()" title="Conectar / Desconectar QZ Tray">
        <div class="conn-dot" id="cDot"></div>
        <span id="cText">Desconectado</span>
    </div>
</header>

<div class="page">

    <!-- ==================== CARD 1: CONEXÃO + IMPRESSORA ==================== -->
    <div class="card">
        <div class="card-head">
            <h2>🔌 Conexão & Impressora</h2>
        </div>
        <div class="card-body gap14">
            <div class="printer-row">
                <div class="fg">
                    <label>Impressora</label>
                    <select id="selPrinter"><option value="">— Conecte ao QZ Tray —</option></select>
                </div>
                <button class="btn btn-outline btn-sm" onclick="refreshPrinters()">🔄</button>
                <button class="btn btn-outline btn-sm" onclick="sendCmd('~HI','Identificação')">🏷️ Identificar</button>
            </div>
            <div class="note note-info">
                <strong>Pré-requisito:</strong> O <a href="https://qz.io/download/" target="_blank" style="color:var(--info)">QZ Tray</a> deve estar instalado e rodando no computador Windows com a impressora USB.
                A leitura de parâmetros é feita via etiqueta impressa (<code style="color:var(--accent-light)">~WC</code>). Como o QZ Tray é one-way via spooler, os parâmetros devem ser preenchidos manualmente ou carregados de um backup JSON salvo anteriormente.
            </div>
        </div>
    </div>

    <!-- ==================== CARD 2: LEITURA / BACKUP DE PARÂMETROS ==================== -->
    <div class="card">
        <div class="card-head">
            <h2>📋 Parâmetros da Impressora</h2>
            <span class="badge badge-step">PASSO 1 — BACKUP</span>
        </div>
        <div class="card-body gap14">
            <div class="note note-warn">
                <strong>Como funciona:</strong> Clique em <em>"Imprimir Configuração"</em> para a impressora gerar uma etiqueta com todos os parâmetros atuais (<code>~WC</code>).
                Depois, preencha os campos abaixo com os valores da etiqueta. Em seguida, salve como arquivo JSON (backup). Esse backup será usado para restaurar após o reset de fábrica.
            </div>

            <div class="btn-group">
                <button class="btn btn-accent" onclick="sendCmd('~WC','Imprimindo etiqueta de configuração')">📄 Imprimir Configuração (~WC)</button>
                <button class="btn btn-outline" onclick="sendCmd('^XA^HH^XZ','Solicitando config via ^HH (verifique terminal serial)')">💻 Enviar ^HH (serial/terminal)</button>
            </div>

            <div class="sep"></div>

            <!-- Param grid -->
            <div class="param-grid">
                <div class="ph">Parâmetro</div>
                <div class="ph">Valor</div>
                <div class="ph">Comando</div>

                <!-- Print Width -->
                <div class="pc">Largura de Impressão (mm)</div>
                <div class="pc"><input type="number" id="p_width" value="24" min="10" max="120" step="0.5"></div>
                <div class="pc"><span class="cmd-tag">SIZE w</span></div>

                <!-- Label Length -->
                <div class="pc">Comprimento da Etiqueta (mm)</div>
                <div class="pc"><input type="number" id="p_length" value="269" min="20" max="400" step="1"></div>
                <div class="pc"><span class="cmd-tag">SIZE h</span></div>

                <!-- Media Type -->
                <div class="pc">Tipo de Mídia</div>
                <div class="pc">
                    <select id="p_media">
                        <option value="D">Térmica Direta (Direct Thermal)</option>
                        <option value="T">Transferência Térmica (Ribbon)</option>
                    </select>
                </div>
                <div class="pc"><span class="cmd-tag">—</span></div>

                <!-- Media Tracking -->
                <div class="pc">Sensor de Mídia</div>
                <div class="pc">
                    <select id="p_tracking">
                        <option value="M" selected>Black Mark (tarja preta)</option>
                        <option value="N">Continuous (contínuo)</option>
                        <option value="Y">Web Sensing (gap/espaço)</option>
                    </select>
                </div>
                <div class="pc"><span class="cmd-tag">SET BLINE</span></div>

                <!-- Black Mark Offset -->
                <div class="pc">Offset Black Mark (dots)</div>
                <div class="pc"><input type="number" id="p_bmoffset" value="0" min="-80" max="283"></div>
                <div class="pc"><span class="cmd-tag">BLINE m,n</span></div>

                <!-- Darkness -->
                <div class="pc">Intensidade (Darkness)</div>
                <div class="pc"><input type="number" id="p_dark" value="16" min="0" max="30" step="1"></div>
                <div class="pc"><span class="cmd-tag">DENSITY</span></div>

                <!-- Speed -->
                <div class="pc">Velocidade (pol/s)</div>
                <div class="pc">
                    <select id="p_speed">
                        <option value="2">2 pol/s</option>
                        <option value="3">3 pol/s</option>
                        <option value="4">4 pol/s</option>
                        <option value="5" selected>5 pol/s</option>
                    </select>
                </div>
                <div class="pc"><span class="cmd-tag">SPEED</span></div>

                <!-- Tear-off offset -->
                <div class="pc">Offset Tear-off (dots)</div>
                <div class="pc"><input type="number" id="p_tearoff" value="0" min="-120" max="120"></div>
                <div class="pc"><span class="cmd-tag">OFFSET</span></div>

                <!-- Label Home -->
                <div class="pc">Home Position X,Y (dots)</div>
                <div class="pc"><input type="text" id="p_home" value="0,0" placeholder="0,0"></div>
                <div class="pc"><span class="cmd-tag">REFERENCE</span></div>

                <!-- DPI -->
                <div class="pc">Resolução (DPI)</div>
                <div class="pc">
                    <select id="p_dpi">
                        <option value="203" selected>203 dpi (8 dots/mm)</option>
                        <option value="300">300 dpi (12 dots/mm)</option>
                    </select>
                </div>
                <div class="pc"><span class="cmd-tag">—</span></div>

                <!-- Encoding -->
                <div class="pc">Codificação</div>
                <div class="pc">
                    <select id="p_encoding">
                        <option value="28" selected>UTF-8</option>
                        <option value="0">CP437 (USA)</option>
                        <option value="13">CP850 (Latin 1)</option>
                    </select>
                </div>
                <div class="pc"><span class="cmd-tag">CODEPAGE</span></div>
            </div>

            <div class="sep"></div>

            <!-- Backup/Restore buttons -->
            <div class="btn-group">
                <button class="btn btn-success" onclick="salvarBackup()">💾 Salvar Backup (JSON)</button>
                <button class="btn btn-outline" onclick="document.getElementById('fileRestore').click()">📂 Carregar Backup</button>
                <input type="file" id="fileRestore" class="file-hidden" accept=".json" onchange="carregarBackup(event)">
                <button class="btn btn-outline" onclick="copiarParametros()">📋 Copiar como Texto</button>
            </div>

            <div id="backupInfo" style="display:none">
                <div class="backup-card">
                    <div class="bc-icon">📦</div>
                    <div class="bc-info">
                        <h4 id="backupName">backup.json</h4>
                        <p id="backupDate">—</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== CARD 3: PROCESSO DE CONFIGURAÇÃO ==================== -->
    <div class="card">
        <div class="card-head">
            <h2>🔧 Processo de Configuração</h2>
            <span class="badge badge-step">PASSO 2 — CALIBRAR</span>
        </div>
        <div class="card-body gap14">
            <div class="note note-warn">
                <strong>Fluxo completo:</strong> Reset fábrica → Aguardar reinício → Configurar Black Mark + Térmica Direta → Calibrar sensores → Testar detecção → Enviar parâmetros do backup → Salvar na memória permanente. Você pode executar tudo automaticamente ou passo a passo.
            </div>

            <div class="wf-steps" id="wfSteps">
                <!-- Step 1 -->
                <div class="wf-step" id="ws1">
                    <div class="wf-num">1</div>
                    <div class="wf-info">
                        <h4>Reset de Fábrica</h4>
                        <p>Restaura todos os parâmetros da impressora para o padrão original. A impressora vai reiniciar.</p>
                        <div class="wf-cmd">INITIALPRINTER (TSPL2)</div>
                    </div>
                    <div class="wf-action">
                        <button class="btn btn-danger btn-sm" onclick="execStep(1)">Executar</button>
                    </div>
                </div>
                <!-- Step 2 -->
                <div class="wf-step" id="ws2">
                    <div class="wf-num">2</div>
                    <div class="wf-info">
                        <h4>Aguardar Reinício</h4>
                        <p>Espera 10 segundos para a impressora completar o reinício após o reset.</p>
                        <div class="wf-cmd">sleep 4000ms</div>
                    </div>
                    <div class="wf-action">
                        <button class="btn btn-outline btn-sm" onclick="execStep(2)">Aguardar</button>
                    </div>
                </div>
                <!-- Step 3 -->
                <div class="wf-step" id="ws3">
                    <div class="wf-num">3</div>
                    <div class="wf-info">
                        <h4>Configurar Black Mark + Térmica Direta</h4>
                        <p>Define o sensor para detectar tarja preta e tipo de mídia térmica direta.</p>
                        <div class="wf-cmd">TSPL2: SET BLINE ON + BLINE</div>
                    </div>
                    <div class="wf-action">
                        <button class="btn btn-accent btn-sm" onclick="execStep(3)">Executar</button>
                    </div>
                </div>
                <!-- Step 4 -->
                <div class="wf-step" id="ws4">
                    <div class="wf-num">4</div>
                    <div class="wf-info">
                        <h4>Calibrar Sensores</h4>
                        <p>Executa a calibração automática dos sensores. A impressora avança 1-4 pulseiras para detectar o black mark.</p>
                        <div class="wf-cmd">TSPL2: BLINEDETECT</div>
                    </div>
                    <div class="wf-action">
                        <button class="btn btn-accent btn-sm" onclick="execStep(4)">Calibrar</button>
                    </div>
                </div>
                <!-- Step 5 -->
                <div class="wf-step" id="ws5">
                    <div class="wf-num">5</div>
                    <div class="wf-info">
                        <h4>Aguardar Calibração + Reaplicar Black Mark</h4>
                        <p>Espera 10 segundos para calibração concluir, depois reenvia a configuração de Black Mark (a calibração pode reverter o sensor para contínuo).</p>
                        <div class="wf-cmd">sleep 5000ms → ^MNM,{offset} ^MTD</div>
                    </div>
                    <div class="wf-action">
                        <button class="btn btn-accent btn-sm" onclick="execStep(5)">Executar</button>
                    </div>
                </div>
                <!-- Step 6 -->
                <div class="wf-step" id="ws6">
                    <div class="wf-num">6</div>
                    <div class="wf-info">
                        <h4>Testar Detecção do Black Mark</h4>
                        <p>Imprime uma pequena etiqueta de teste para verificar se a impressora para corretamente no black mark.</p>
                        <div class="wf-cmd">TSPL2: CLS + TEXT + PRINT 1,1</div>
                    </div>
                    <div class="wf-action">
                        <button class="btn btn-warn btn-sm" onclick="execStep(6)">Testar</button>
                    </div>
                </div>
                <!-- Step 7 -->
                <div class="wf-step" id="ws7">
                    <div class="wf-num">7</div>
                    <div class="wf-info">
                        <h4>Enviar Parâmetros (do Backup)</h4>
                        <p>Restaura largura, comprimento, intensidade, velocidade, encoding e demais configurações do backup.</p>
                        <div class="wf-cmd">TSPL2: SIZE, SPEED, DENSITY, CODEPAGE, SET HEAD OFF, SET REPRINT OFF</div>
                    </div>
                    <div class="wf-action">
                        <button class="btn btn-accent btn-sm" onclick="execStep(7)">Enviar</button>
                    </div>
                </div>
                <!-- Step 8 -->
                <div class="wf-step" id="ws8">
                    <div class="wf-num">8</div>
                    <div class="wf-info">
                        <h4>Salvar na Memória Permanente</h4>
                        <p>Grava todas as configurações na flash da impressora. Sem este passo, tudo será perdido ao desligar.</p>
                        <div class="wf-cmd">BPLB: &lt;STX&gt;KPre_EEP</div>
                    </div>
                    <div class="wf-action">
                        <button class="btn btn-success btn-sm" onclick="execStep(8)">Salvar</button>
                    </div>
                </div>
            </div>

            <div class="sep"></div>

            <div class="btn-group">
                <button class="btn btn-accent btn-block" onclick="runFullWorkflow()" id="btnFull">
                    🚀 Executar Tudo Automaticamente
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== CARD 4: LOG ==================== -->
    <div class="card">
        <div class="card-head">
            <h2>📜 Log de Comandos</h2>
            <button class="btn btn-outline btn-sm" onclick="clearLog()">Limpar</button>
        </div>
        <div class="card-body">
            <div class="log-box" id="logBox">
                <span class="log-ts">[aguardando]</span> Conecte ao QZ Tray e selecione a impressora para começar.
            </div>
        </div>
    </div>

</div>

<!-- Toasts -->
<div class="toasts" id="toasts"></div>

<!-- QZ Tray -->
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-sha256@0/build/sha256.min.js"></script>

<script>
// ================================================================
// STATE
// ================================================================
let connected = false;

// ================================================================
// QZ TRAY
// ================================================================
  //qz.security.setCertificatePromise(function(resolve) { resolve(); });
  //qz.security.setSignatureAlgorithm("SHA512");
  //qz.security.setSignaturePromise(function() {
  //    return function(resolve) { resolve(); };
  //});
// COLOCAR ISSO:
qz.security.setCertificatePromise(function(resolve, reject) {
    fetch("assets/signing/digital-certificate.txt", {
        cache: 'no-store',
        headers: {'Content-Type': 'text/plain'}
    }).then(function(data) {
        data.ok ? resolve(data.text()) : reject(data.text());
    });
});

qz.security.setSignatureAlgorithm("SHA512");

qz.security.setSignaturePromise(function(toSign) {
    return function(resolve, reject) {
        fetch("assets/signing/sign-message.php?request=" + encodeURIComponent(toSign), {
            cache: 'no-store',
            headers: {'Content-Type': 'text/plain'}
        }).then(function(data) {
            data.ok ? resolve(data.text()) : reject(data.text());
        });
    };
});

async function toggleConn() {
    connected ? await doDisconnect() : await doConnect();
}

async function doConnect() {
    try {
        setConn('connecting');
        await qz.websocket.connect({
            host: 'localhost',
            port: { secure: [8181], insecure: [8182] },
            usingSecure: false,
            retries: 3,
            delay: 1
        });
        connected = true;
        setConn('on');
        toast('Conectado ao QZ Tray!', 's');
        log('Conectado ao QZ Tray via WebSocket', 'ok');
        await refreshPrinters();
    } catch(e) {
        connected = false;
        setConn('off');
        toast('Erro ao conectar — QZ Tray rodando?', 'e');
        log('Falha na conexão: ' + e.message, 'err');
    }
}

async function doDisconnect() {
    try {
        await qz.websocket.disconnect();
        connected = false;
        setConn('off');
        toast('Desconectado', 'i');
        log('Desconectado do QZ Tray', 'warn');
    } catch(e) { console.error(e); }
}

function setConn(s) {
    var d = document.getElementById('cDot');
    var t = document.getElementById('cText');
    if (s === 'on') { d.className = 'conn-dot on'; t.textContent = 'Conectado'; }
    else if (s === 'connecting') { d.className = 'conn-dot'; d.style.background = 'var(--warning)'; t.textContent = 'Conectando…'; }
    else { d.className = 'conn-dot'; d.style.background = ''; t.textContent = 'Desconectado'; }
}

async function refreshPrinters() {
    if (!connected) { toast('Conecte primeiro', 'e'); return; }
    try {
        var list = await qz.printers.find();
        var sel = document.getElementById('selPrinter');
        sel.innerHTML = '<option value="">— Selecione —</option>';
        list.forEach(function(p) {
            var o = document.createElement('option');
            o.value = p; o.textContent = p;
            if (p.toLowerCase().indexOf('elgin') > -1 || p.toLowerCase().indexOf('l42') > -1) o.selected = true;
            sel.appendChild(o);
        });
        log('Encontrada(s) ' + list.length + ' impressora(s)', 'ok');
    } catch(e) {
        toast('Erro ao listar impressoras', 'e');
        log('Erro listando impressoras: ' + e.message, 'err');
    }
}

function getPrinter() {
    return document.getElementById('selPrinter').value;
}

// ================================================================
// SEND RAW COMMAND
// ================================================================

// Send raw text/TSPL2 string to printer
async function sendCmd(zpl, desc) {
    if (!connected) { toast('Conecte ao QZ Tray', 'e'); return false; }
    var p = getPrinter();
    if (!p) { toast('Selecione uma impressora', 'e'); return false; }
    try {
        var cfg = qz.configs.create(p);
        await qz.print(cfg, [zpl]);
        log('ENVIADO → ' + desc, 'ok');
        log(zpl, 'cmd');
        return true;
    } catch(e) {
        log('ERRO → ' + desc + ': ' + e.message, 'err');
        toast('Erro: ' + e.message, 'e');
        return false;
    }
}

// Send BPLB/EPL command with <STX> (0x02) prefix
// The Elgin L42 DT uses BPLB language where special commands
// are prefixed with <STX> (ASCII 0x02), e.g.: <STX>KrEEP
async function sendRawBytes(prefixBytes, textAfter, desc) {
    if (!connected) { toast('Conecte ao QZ Tray', 'e'); return false; }
    var p = getPrinter();
    if (!p) { toast('Selecione uma impressora', 'e'); return false; }
    try {
        var cfg = qz.configs.create(p);
        // Build hex string: prefix bytes + text as hex + LF (0x0A)
        var hex = '';
        for (var i = 0; i < prefixBytes.length; i++) {
            hex += ('0' + prefixBytes[i].toString(16)).slice(-2);
        }
        for (var j = 0; j < textAfter.length; j++) {
            hex += ('0' + textAfter.charCodeAt(j).toString(16)).slice(-2);
        }
        hex += '0a'; // LF line terminator

        var data = [{
            type: 'raw',
            format: 'hex',
            data: hex
        }];
        await qz.print(cfg, data);
        var displayCmd = '<STX>' + textAfter;
        log('ENVIADO → ' + desc, 'ok');
        log('HEX: ' + hex + '  →  ' + displayCmd, 'cmd');
        return true;
    } catch(e) {
        log('ERRO → ' + desc + ': ' + e.message, 'err');
        toast('Erro: ' + e.message, 'e');
        return false;
    }
}

// ================================================================
// READ PARAMETERS → build from form
// ================================================================
function readParams() {
    var dpi = parseInt(document.getElementById('p_dpi').value);
    var dpmm = dpi / 25.4;
    return {
        width_mm:    parseFloat(document.getElementById('p_width').value) || 24,
        length_mm:   parseFloat(document.getElementById('p_length').value) || 269,
        width_dots:  Math.round((parseFloat(document.getElementById('p_width').value) || 24) * dpmm),
        length_dots: Math.round((parseFloat(document.getElementById('p_length').value) || 269) * dpmm),
        media:       document.getElementById('p_media').value,
        tracking:    document.getElementById('p_tracking').value,
        bm_offset:   parseInt(document.getElementById('p_bmoffset').value) || 0,
        darkness:    parseInt(document.getElementById('p_dark').value) || 16,
        speed:       document.getElementById('p_speed').value,
        tearoff:     parseInt(document.getElementById('p_tearoff').value) || 0,
        home:        document.getElementById('p_home').value || '0,0',
        dpi:         dpi,
        encoding:    document.getElementById('p_encoding').value,
        timestamp:   new Date().toISOString()
    };
}

function fillParams(obj) {
    if (obj.width_mm)   document.getElementById('p_width').value   = obj.width_mm;
    if (obj.length_mm)  document.getElementById('p_length').value  = obj.length_mm;
    if (obj.media)      document.getElementById('p_media').value   = obj.media;
    if (obj.tracking)   document.getElementById('p_tracking').value= obj.tracking;
    if (obj.bm_offset !== undefined) document.getElementById('p_bmoffset').value = obj.bm_offset;
    if (obj.darkness)   document.getElementById('p_dark').value    = obj.darkness;
    if (obj.speed)      document.getElementById('p_speed').value   = obj.speed;
    if (obj.tearoff !== undefined) document.getElementById('p_tearoff').value = obj.tearoff;
    if (obj.home)       document.getElementById('p_home').value    = obj.home;
    if (obj.dpi)        document.getElementById('p_dpi').value     = obj.dpi;
    if (obj.encoding)   document.getElementById('p_encoding').value= obj.encoding;
}

// ================================================================
// BACKUP / RESTORE
// ================================================================
function salvarBackup() {
    var params = readParams();
    params._type = 'elgin_l42dt_backup';
    params._version = '1.0';
    var json = JSON.stringify(params, null, 2);
    var blob = new Blob([json], { type: 'application/json' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    var ts = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
    a.href = url;
    a.download = 'elgin_l42dt_backup_' + ts + '.json';
    a.click();
    URL.revokeObjectURL(url);
    toast('Backup salvo!', 's');
    log('Backup salvo: ' + a.download, 'ok');
    showBackupInfo(a.download, params.timestamp);
}

function carregarBackup(ev) {
    var file = ev.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        try {
            var obj = JSON.parse(e.target.result);
            if (obj._type !== 'elgin_l42dt_backup') {
                toast('Arquivo não é um backup válido', 'e');
                return;
            }
            fillParams(obj);
            toast('Backup carregado!', 's');
            log('Backup carregado: ' + file.name + ' (de ' + (obj.timestamp || '?') + ')', 'ok');
            showBackupInfo(file.name, obj.timestamp);
        } catch(err) {
            toast('Erro ao ler arquivo: ' + err.message, 'e');
            log('Erro lendo backup: ' + err.message, 'err');
        }
    };
    reader.readAsText(file);
    ev.target.value = '';
}

function showBackupInfo(name, ts) {
    document.getElementById('backupInfo').style.display = '';
    document.getElementById('backupName').textContent = name;
    document.getElementById('backupDate').textContent = ts ? 'Criado em: ' + new Date(ts).toLocaleString('pt-BR') : '';
}

function copiarParametros() {
    var p = readParams();
    var txt = 'ELGIN L42 DT — Parâmetros\n';
    txt += '=========================\n';
    txt += 'Largura:      ' + p.width_mm + ' mm (' + p.width_dots + ' dots)\n';
    txt += 'Comprimento:  ' + p.length_mm + ' mm (' + p.length_dots + ' dots)\n';
    txt += 'Mídia:        ' + (p.media === 'D' ? 'Térmica Direta' : 'Transf. Térmica') + '\n';
    txt += 'Sensor:       ' + ({M:'Black Mark',N:'Contínuo',Y:'Gap/Web'}[p.tracking] || p.tracking) + '\n';
    txt += 'BM Offset:    ' + p.bm_offset + ' dots\n';
    txt += 'Darkness:     ' + p.darkness + '\n';
    txt += 'Velocidade:   ' + p.speed + ' pol/s\n';
    txt += 'Tearoff Ofs:  ' + p.tearoff + ' dots\n';
    txt += 'Home:         ' + p.home + '\n';
    txt += 'DPI:          ' + p.dpi + '\n';
    txt += 'Encoding:     ^CI' + p.encoding + '\n';
    txt += 'Timestamp:    ' + p.timestamp + '\n';
    navigator.clipboard.writeText(txt).then(function() {
        toast('Copiado para área de transferência!', 's');
    });
}

// ================================================================
// WORKFLOW STEPS
// ================================================================
function sleep(ms) { return new Promise(function(r) { setTimeout(r, ms); }); }

function setStepState(n, state) {
    var el = document.getElementById('ws' + n);
    if (!el) return;
    el.className = 'wf-step' + (state ? ' ' + state : '');
}

function resetAllSteps() {
    for (var i = 1; i <= 8; i++) setStepState(i, '');
    setStepState(31, '');
}

// ================================================================
// TSPL2 COMMAND BUILDERS (TSPL2 only)
// ================================================================

// TSPL2 Step 3: Black Mark config
function buildStep3TSPL() {
    var cmds = '';
    cmds += 'SET BLINE ON\r\n';
    cmds += 'BLINE 30 mm,0 mm\r\n';
    return cmds;
}

// TSPL2 Step 6: Test label
function buildTestTSPL() {
    var p = readParams();
    var cmds = '';
    cmds += 'SIZE ' + p.width_mm + ' mm,' + p.length_mm + ' mm\r\n';
    cmds += 'CLS\r\n';
    cmds += 'TEXT 20,8,"3",0,1,1,"TESTE BLACK MARK"\r\n';
    cmds += 'TEXT 20,34,"2",0,1,1,"Se parou no mark = OK"\r\n';
    cmds += 'TEXT 20,54,"1",0,1,1,"' + new Date().toLocaleString('pt-BR') + '"\r\n';
    cmds += 'PRINT 1,1\r\n';
    return cmds;
}

// TSPL2 Step 7: Send all parameters
function buildStep7TSPL() {
    var p = readParams();
    var tsplDensity = Math.round(p.darkness / 2);
    if (tsplDensity > 15) tsplDensity = 15;

    var cmds = '';
    cmds += 'SIZE ' + p.width_mm + ' mm,' + p.length_mm + ' mm\r\n';
    cmds += 'SPEED ' + p.speed + '\r\n';
    cmds += 'DENSITY ' + tsplDensity + '\r\n';
    cmds += 'DIRECTION 0\r\n';
    cmds += 'SET TEAR ON\r\n';
    cmds += 'SET HEAD OFF\r\n';
    cmds += 'SET REPRINT OFF\r\n';

    // CODEPAGE mapping
    var cp = '850';
    if (p.encoding === '28') cp = 'UTF-8';
    else if (p.encoding === '0') cp = '437';
    else if (p.encoding === '13') cp = '850';
    cmds += 'CODEPAGE ' + cp + '\r\n';

    // Offset (tear-off adjustment)
    if (p.tearoff !== 0) {
        var tearMM = (p.tearoff / (p.dpi / 25.4)).toFixed(1);
        cmds += 'OFFSET ' + tearMM + ' mm\r\n';
    }

    return cmds;
}

// ================================================================
// EXEC STEP — TSPL2 only
// ================================================================
async function execStep(n) {
    if (!connected) { toast('Conecte ao QZ Tray', 'e'); return false; }
    if (!getPrinter()) { toast('Selecione impressora', 'e'); return false; }

    setStepState(n, 'running');
    var ok = false;

    switch(n) {
        case 1: // Factory Reset — TSPL2
            if (!confirm('⚠️ Reset de Fábrica (INITIALPRINTER)\n\nTodas as configurações serão restauradas para o padrão.\nA impressora vai reiniciar.\n\nContinuar?')) {
                setStepState(n, ''); return false;
            }
            ok = await sendCmd('INITIALPRINTER\r\n', 'STEP 1 — Reset de Fábrica (INITIALPRINTER)');
            break;
        case 2: // Wait
            log('STEP 2 — Aguardando 10s para reinício…', 'warn');
            await sleep(10000);
            ok = true;
            log('STEP 2 — Reinício concluído', 'ok');
            break;
        case 3: // Black Mark config — TSPL2
            var t3 = buildStep3TSPL();
            ok = await sendCmd(t3, 'STEP 3 — Black Mark + Térmica Direta (SET BLINE ON + BLINE)');
            break;
        case 4: // Calibrate — TSPL2
            ok = await sendCmd('BLINEDETECT\r\n', 'STEP 4 — Calibrar Sensores (BLINEDETECT)');
            if (ok) toast('Impressora avançando mídia para calibrar…', 'w');
            break;
        case 5: // Wait calibration + re-apply Black Mark
            log('STEP 5 — Aguardando 10s para calibração…', 'warn');
            await sleep(10000);
            log('STEP 5 — Calibração concluída, reaplicando Black Mark…', 'ok');
            var t5 = buildStep3TSPL();
            ok = await sendCmd(t5, 'STEP 5 — Reaplicar Black Mark após calibração');
            break;
        case 6: // Test — TSPL2
            var t6 = buildTestTSPL();
            ok = await sendCmd(t6, 'STEP 6 — Imprimir Teste Black Mark');
            if (ok) toast('Verifique: a pulseira parou certinho no black mark?', 'w');
            break;
        case 7: // Send params — TSPL2
            var t7 = buildStep7TSPL();
            ok = await sendCmd(t7, 'STEP 7 — Enviar Parâmetros (SIZE, SPEED, DENSITY, CODEPAGE)');
            break;
        case 8: // Save — BPLB native command
            ok = await sendRawBytes([0x02], 'KPre_EEP', 'STEP 8 — Salvar na Memória Permanente (<STX>KPre_EEP)');
            break;
    }

    setStepState(n, ok ? 'done' : 'error');
    return ok;
}

async function runFullWorkflow() {
    if (!connected) { toast('Conecte ao QZ Tray', 'e'); return; }
    if (!getPrinter()) { toast('Selecione impressora', 'e'); return; }

    if (!confirm('🚀 Configuração Completa\n\nEste processo vai:\n1. Reset de fábrica\n2. Configurar Black Mark + Térmica Direta\n3. Calibrar sensores\n4. Testar detecção\n5. Enviar parâmetros do backup\n6. Salvar na memória\n\nA impressora vai avançar algumas pulseiras.\nContinuar?')) return;

    var btn = document.getElementById('btnFull');
    btn.disabled = true;
    btn.innerHTML = '<span class="spin"></span> Executando…';
    resetAllSteps();
    log('═══════ INÍCIO — Configuração Completa ═══════', 'ok');

    var ok = true;
    var steps = [1, 2, 3, 4, 5, 6, 7, 8];
    for (var si = 0; si < steps.length; si++) {
        var i = steps[si];
        if (!ok) { setStepState(i, 'skipped'); continue; }
        ok = await execStep(i);
        if (ok && si < steps.length - 1) await sleep(300);
    }

    if (ok) {
        toast('✅ Configuração completa com sucesso!', 's');
        log('═══════ FIM — Tudo OK! ═══════', 'ok');
    } else {
        toast('Houve um erro. Verifique o log.', 'e');
        log('═══════ FIM — Houve erros ═══════', 'err');
    }

    btn.disabled = false;
    btn.innerHTML = '🚀 Executar Tudo Automaticamente';
}

// ================================================================
// LOG
// ================================================================
function log(msg, type) {
    var box = document.getElementById('logBox');
    var ts = new Date().toLocaleTimeString('pt-BR');
    var cls = { ok:'log-ok', err:'log-err', warn:'log-warn', cmd:'log-cmd' }[type] || '';
    box.innerHTML += '<div><span class="log-ts">[' + ts + ']</span> <span class="' + cls + '">' + escHTML(msg) + '</span></div>';
    box.scrollTop = box.scrollHeight;
}
function clearLog() {
    document.getElementById('logBox').innerHTML = '<span class="log-ts">[limpo]</span>';
}
function escHTML(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ================================================================
// TOAST
// ================================================================
function toast(msg, type) {
    var c = document.getElementById('toasts');
    var icons = { s:'✅', e:'❌', i:'ℹ️', w:'⚠️' };
    var d = document.createElement('div');
    d.className = 'toast ' + type;
    d.innerHTML = '<span>' + (icons[type]||'') + '</span><span>' + msg + '</span>';
    c.appendChild(d);
    setTimeout(function() {
        d.style.transition = 'opacity .3s, transform .3s';
        d.style.opacity = '0';
        d.style.transform = 'translateX(40px)';
        setTimeout(function() { d.remove(); }, 300);
    }, 4500);
}

// ================================================================
// INIT
// ================================================================
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        if (typeof qz !== 'undefined') doConnect();
    }, 800);
});

window.addEventListener('beforeunload', function() {
    if (connected) qz.websocket.disconnect();
});
</script>

<div class="text-center mt-4 mb-2" style="font-size:9px;color:#b0b0b0;opacity:0.6">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</body>
</html>
