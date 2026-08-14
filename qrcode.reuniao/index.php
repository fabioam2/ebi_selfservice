<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <title>QR Code para Reunião – Espaço Bíblico Infantil</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-masker/1.1.1/vanilla-masker.min.js"></script>
    <script src="../qr-crypto.js"></script>

    <style>
        :root {
            --bg-1: #0f766e;
            --bg-2: #0b4f8a;
            --bg-3: #f59e0b;
            --surface-border: rgba(15, 23, 42, 0.08);
            --text-main: #10273b;
            --text-soft: #4b647c;
            --brand: #0e7490;
            --brand-strong: #0b5f76;
            --brand-soft: rgba(14, 116, 144, 0.14);
            --success-bg: #dff8ea;
            --success-border: #1f9d61;
            --warning-bg: #fff4dc;
            --warning-border: #e8a100;
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
            flex-direction: column;
            align-items: center;
            font-family: 'Manrope', sans-serif;
            padding: 20px 14px 40px;
            position: relative;
            overflow-x: hidden;
        }

        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 999px;
            z-index: 0;
            filter: blur(0.5px);
            animation: floatGlow 11s ease-in-out infinite alternate;
            pointer-events: none;
        }

        body::before {
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.42), rgba(245, 158, 11, 0));
            left: -40px;
            top: 20%;
        }

        body::after {
            width: 270px;
            height: 270px;
            background: radial-gradient(circle, rgba(45, 212, 191, 0.35), rgba(45, 212, 191, 0));
            right: -70px;
            bottom: 6%;
            animation-delay: 1.4s;
        }

        @keyframes floatGlow {
            from { transform: translateY(0px); }
            to { transform: translateY(-14px); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-18px) scale(0.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .qr-page {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 560px;
        }

        .qr-container {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.97));
            padding: 32px 28px;
            border-radius: 22px;
            border: 1px solid var(--surface-border);
            box-shadow: 0 18px 48px rgba(1, 27, 49, 0.33);
            animation: slideIn 0.55s ease-out;
        }

        .qr-header { text-align: center; margin-bottom: 22px; }
        .qr-header .icon-header { font-size: 2.9rem; color: var(--brand); margin-bottom: 10px; }
        .qr-header h1 { color: var(--text-main); font-weight: 800; letter-spacing: -0.02em; font-size: 1.5rem; margin-bottom: 4px; }
        .qr-header p { color: var(--text-soft); font-size: 0.95rem; margin: 0; }

        .info-box {
            background: #eff9ff;
            border-left: 4px solid var(--brand);
            padding: 13px 15px;
            margin-bottom: 16px;
            border-radius: 9px;
            color: #20455f;
            font-size: 0.88rem;
            line-height: 1.5;
        }

        .info-box i { color: var(--brand); margin-right: 6px; }

        .important-box {
            background: var(--warning-bg);
            border-left: 4px solid var(--warning-border);
            padding: 13px 15px;
            margin-bottom: 16px;
            border-radius: 9px;
            color: #6b4600;
            font-size: 0.88rem;
            line-height: 1.5;
        }

        .important-box i { color: var(--warning-border); margin-right: 6px; }

        details.instructions-collapse { margin-bottom: 20px; }
        details.instructions-collapse summary {
            cursor: pointer;
            font-weight: 700;
            color: var(--brand-strong);
            font-size: 0.9rem;
            list-style: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        details.instructions-collapse summary::-webkit-details-marker { display: none; }
        details.instructions-collapse summary .chev { margin-left: auto; transition: transform 0.2s ease; }
        details.instructions-collapse[open] summary .chev { transform: rotate(180deg); }
        details.instructions-collapse ul {
            margin: 10px 0 0;
            padding-left: 18px;
            font-size: 0.85rem;
            color: var(--text-soft);
            line-height: 1.6;
        }

        .section-title {
            font-weight: 800;
            color: var(--text-main);
            font-size: 0.95rem;
            margin: 18px 0 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-title i { color: var(--brand); }
        .section-title:first-of-type { margin-top: 4px; }

        .form-group label { font-weight: 600; color: #173146; margin-bottom: 6px; font-size: 0.88rem; }

        .form-control {
            border-radius: 11px;
            border: 1px solid #ced9e4;
            padding: 11px 13px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
            background: #fbfdff;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 0.19rem var(--brand-soft);
            transform: translateY(-1px);
            background: #fff;
            outline: none;
        }

        select.form-control {
            height: 42px;
            font-size: 0.82rem;
            padding: 7px 24px 7px 10px;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
            background-position: right 10px center;
        }

        .error {
            color: var(--danger);
            font-size: 0.78rem;
            display: block;
            min-height: 1.1em;
            margin-top: 2px;
        }

        .child-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid var(--brand);
            border-radius: 12px;
            padding: 14px 15px 6px;
            margin-bottom: 14px;
            position: relative;
        }

        .child-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .child-badge {
            background: var(--brand);
            color: #fff;
            font-weight: 700;
            font-size: 0.78rem;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .child-label { font-weight: 700; color: var(--text-main); font-size: 0.88rem; }

        .remove-child-btn {
            margin-left: auto;
            background: none;
            border: none;
            color: var(--danger);
            font-size: 0.95rem;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 6px;
            transition: background-color 0.2s ease;
        }
        .remove-child-btn:hover { background: rgba(185, 28, 28, 0.1); }

        .idade-display {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--brand-soft);
            color: var(--brand-strong);
            font-weight: 700;
            font-size: 0.8rem;
            border-radius: 999px;
            padding: 8px 12px;
            width: 100%;
            min-height: 42px;
            text-align: center;
        }

        .btn-add-child {
            background: #fff;
            color: var(--brand-strong);
            border: 1px solid rgba(14, 116, 144, 0.35);
            border-radius: 11px;
            padding: 11px 22px;
            font-weight: 700;
            width: 100%;
            margin-top: 4px;
            margin-bottom: 18px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .btn-add-child:hover {
            background: #f2fbff;
            transform: translateY(-1px);
            box-shadow: 0 7px 14px rgba(14, 116, 144, 0.15);
        }

        .btn-generate {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-strong) 100%);
            border: none;
            border-radius: 11px;
            padding: 14px 30px;
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            width: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }
        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(14, 116, 144, 0.4);
            filter: brightness(1.03);
            color: #fff;
        }

        .alert-msg {
            border-radius: 10px;
            font-size: 0.85rem;
            padding: 12px 14px;
            margin-top: 14px;
        }

        #qrcode-container {
            text-align: center;
            margin: 22px 0 6px;
            animation: slideIn 0.4s ease-out;
        }

        #qrcode {
            display: inline-block;
            padding: 14px;
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--surface-border);
            box-shadow: 0 10px 24px rgba(1, 27, 49, 0.15);
        }

        #qrcode canvas, #qrcode img { display: block; max-width: 100%; height: auto; }

        #button-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 14px;
            flex-wrap: wrap;
        }

        #downloadBtn, #copyBtn {
            border: none;
            border-radius: 10px;
            font-weight: 700;
            padding: 10px 18px;
            font-size: 0.85rem;
            color: #fff;
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }
        #downloadBtn { background: linear-gradient(135deg, #1f9d61 0%, #17784a 100%); }
        #copyBtn { background: linear-gradient(135deg, #64748b 0%, #475569 100%); }
        #downloadBtn:hover, #copyBtn:hover { transform: translateY(-1px); filter: brightness(1.05); color: #fff; }

        .privacy-note {
            text-align: center;
            color: var(--text-soft);
            font-size: 0.78rem;
            margin-top: 20px;
            opacity: 0.85;
        }
        .privacy-note i { color: var(--brand); }

        @media (max-width: 480px) {
            .qr-container { padding: 24px 18px; border-radius: 18px; }
            .qr-header h1 { font-size: 1.3rem; }
            .qr-header .icon-header { font-size: 2.4rem; }
            .child-card .form-row .form-group:nth-child(1) { flex: 0 0 42%; max-width: 42%; }
            .child-card .form-row .form-group:nth-child(2) { flex: 0 0 58%; max-width: 58%; }
            .child-card .form-row .form-group:nth-child(3) { flex: 0 0 100%; max-width: 100%; padding-top: 2px; }
        }
    </style>
</head>
<body>
    <div class="qr-page">
        <div class="qr-container">
            <div class="qr-header">
                <i class="fas fa-qrcode icon-header"></i>
                <h1>QR Code para Reunião</h1>
                <p>Espaço Bíblico Infantil</p>
            </div>

            <div class="important-box">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>QR Code para Reunião do Espaço Bíblico Infantil</strong>
            </div>

            <details class="instructions-collapse">
                <summary><i class="fas fa-info-circle"></i> Como funciona <i class="fas fa-chevron-down chev"></i></summary>
                <ul>
                    <li>Selecione a função e informe os dados para gerar o QR Code da reunião.</li>
                    <li>Guarde o QR Code gerado para apresentá-lo na reunião.</li>
                </ul>
            </details>

            <form id="qrForm">
                <div class="form-group">
                    <label for="nomePai">Nome:</label>
                    <input type="text" class="form-control" id="nomePai" name="nomePai" placeholder="Nome e sobrenome" autocomplete="name">
                    <span id="errorNomePai" class="error"></span>
                </div>

                <div class="form-row">
                    <div class="form-group col-12">
                        <label for="telefone">Telefone</label>
                        <input type="text" inputmode="tel" class="form-control" id="telefone" name="telefone" placeholder="(00) 00000-0000" autocomplete="tel">
                        <span id="errorTelefone" class="error"></span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="comum">Comum</label>
                        <input type="text" class="form-control" id="comum" name="comum" placeholder="Ex: Central">
                        <span id="errorComum" class="error"></span>
                    </div>
                    <div class="form-group col-6">
                        <label for="cidade">Cidade <span class="text-muted" style="font-weight:400;">(opcional)</span></label>
                        <input type="text" class="form-control" id="cidade" name="cidade" placeholder="Ex: São Paulo">
                        <span id="errorCidade" class="error"></span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-12">
                        <label for="estado">Estado <span class="text-muted" style="font-weight:400;">(opcional)</span></label>
                        <select class="form-control" id="estado" name="estado">
                            <option value="">—</option>
                            <option value="AC">AC - Acre</option>
                            <option value="AL">AL - Alagoas</option>
                            <option value="AP">AP - Amapá</option>
                            <option value="AM">AM - Amazonas</option>
                            <option value="BA">BA - Bahia</option>
                            <option value="CE">CE - Ceará</option>
                            <option value="DF">DF - Distrito Federal</option>
                            <option value="ES">ES - Espírito Santo</option>
                            <option value="GO">GO - Goiás</option>
                            <option value="MA">MA - Maranhão</option>
                            <option value="MT">MT - Mato Grosso</option>
                            <option value="MS">MS - Mato Grosso do Sul</option>
                            <option value="MG">MG - Minas Gerais</option>
                            <option value="PA">PA - Pará</option>
                            <option value="PB">PB - Paraíba</option>
                            <option value="PR">PR - Paraná</option>
                            <option value="PE">PE - Pernambuco</option>
                            <option value="PI">PI - Piauí</option>
                            <option value="RJ">RJ - Rio de Janeiro</option>
                            <option value="RN">RN - Rio Grande do Norte</option>
                            <option value="RS">RS - Rio Grande do Sul</option>
                            <option value="RO">RO - Rondônia</option>
                            <option value="RR">RR - Roraima</option>
                            <option value="SC">SC - Santa Catarina</option>
                            <option value="SP">SP - São Paulo</option>
                            <option value="SE">SE - Sergipe</option>
                            <option value="TO">TO - Tocantins</option>
                        </select>
                        <span id="errorEstado" class="error"></span>
                    </div>
                </div>

                <div class="section-title"><i class="fas fa-clipboard-list"></i>Dados da Reunião</div>

                <div class="form-group">
                    <label for="funcao">Função:</label>
                    <select class="form-control" id="funcao" name="funcao">
                        <option value="">Selecione a função</option>
                        <option value="Coordenadora">Coordenadora</option>
                        <option value="Colaboradora">Colaboradora</option>
                        <option value="Ancião">Ancião</option>
                        <option value="Cooperador do Ofício">Cooperador do Ofício</option>
                        <option value="Cooperador de Jovens">Cooperador de Jovens</option>
                        <option value="Diácono">Diácono</option>
                        <option value="Adm">Adm</option>
                        <option value="Outros">Outros</option>
                    </select>
                    <span id="errorFuncao" class="error"></span>
                </div>

                <button type="button" class="btn btn-generate" onclick="generateQRCode()">
                    <i class="fas fa-qrcode mr-1"></i>Gerar QR Code
                </button>

                <div id="msgBtn" class="alert alert-info alert-msg" style="display:none;">
                    <i class="fas fa-camera mr-1"></i>Tire um "print" da tela e guarde o QR Code para apresentar na reunião do Espaço Bíblico Infantil.
                </div>

                <div class="msgBtniphone alert alert-info alert-msg" style="display:none;">
                    <i class="fab fa-apple mr-1"></i>iPhone: toque e segure a imagem do QR Code e selecione "Salvar no Fotos" ou "Compartilhar".
                </div>
            </form>

            <div id="qrcode-container" style="display:none;">
                <div id="qrcode"></div>
                <div id="button-container">
                    <button type="button" id="downloadBtn"><i class="fas fa-download mr-1"></i>Baixar</button>
                    <button type="button" id="copyBtn"><i class="fas fa-copy mr-1"></i>Copiar</button>
                </div>
            </div>

            <div class="privacy-note">
                <i class="fas fa-lock"></i> Privacidade: os dados inseridos para gerar o QR Code não são armazenados.
            </div>

            <div class="text-center mt-4 mb-2" style="font-size:9px;color:#b0b0b0;opacity:0.6">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
        </div>
    </div>

    <script>
        let qrCodeCanvas;

        function removeAccents(text) {
            return text.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        }

        async function generateQRCode() {
            var nomePai = removeAccents(document.getElementById('nomePai').value);
            var telefone = removeAccents(document.getElementById('telefone').value);
            var cidade = removeAccents(document.getElementById('cidade').value);
            var estado = document.getElementById('estado').value;
            var comum = removeAccents(document.getElementById('comum').value);
            var funcao = removeAccents(document.getElementById('funcao').value);
            var idadeFixa = 3;
            var sexoFixo = 'X';
            var dataNascimentoFixa = '01/01/2023';

            var qrData = "";
            var isValid = true;

            // Limpa mensagens de erro do Responsável
            document.getElementById('errorNomePai').innerText = '';
            document.getElementById('errorTelefone').innerText = '';
            document.getElementById('errorCidade').innerText = '';
            document.getElementById('errorEstado').innerText = '';
            document.getElementById('errorComum').innerText = '';
            document.getElementById('errorFuncao').innerText = '';

            // Validação dos dados do Responsável
            if (!nomePai) {
                document.getElementById('errorNomePai').innerText = 'Este campo é obrigatório';
                isValid = false;
            }
            if (!funcao) {
                document.getElementById('errorFuncao').innerText = 'Selecione uma função';
                isValid = false;
            }

            if (isValid) {
                // Mantém os nove campos lidos pela portaria: função, nome, idade,
                // telefone, comum, cidade, UF, sexo e nascimento.
                qrData = `${funcao}\t${nomePai}\t${idadeFixa}\t${telefone}\t${comum}\t${cidade}\t${estado}\t${sexoFixo}\t${dataNascimentoFixa}`;
                try {
                    qrData = await EbiQrCrypto.encrypt(qrData);
                } catch (error) {
                    alert('Não foi possível criptografar o QR Code.');
                    return;
                }
            }

            applyPhoneMask();

            const qrcodeContainer = document.getElementById('qrcode-container');

            if (isValid && qrData) {
                document.getElementById('qrcode').innerHTML = '';
                var qrcode = new QRCode(document.getElementById("qrcode"), {
                    text: qrData,
                    width: 220,
                    height: 220,
                    correctLevel: QRCode.CorrectLevel.H
                });

                qrCodeCanvas = document.querySelector('#qrcode canvas');
                qrcodeContainer.style.display = 'block';
                if (qrCodeCanvas) {
                    document.getElementById('msgBtn').style.display = 'block';
                    displayiPhoneMessage();
                } else {
                    document.getElementById('msgBtn').style.display = 'none';
                    document.querySelector('.msgBtniphone').style.display = 'none';
                }
                qrcodeContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                document.getElementById('msgBtn').style.display = 'none';
                document.querySelector('.msgBtniphone').style.display = 'none';
                document.getElementById('qrcode').innerHTML = '';
                qrcodeContainer.style.display = 'none';
            }
        }

        function downloadQRCode() {
            if (!qrCodeCanvas) {
                alert('Por favor, gere o QR Code primeiro.');
                return;
            }
            const link = document.createElement('a');
            link.download = 'qrcode-reuniao-ebi.png';
            link.href = qrCodeCanvas.toDataURL('image/png');
            link.click();
        }

        function copyQRCode() {
            if (!qrCodeCanvas) {
                alert('Por favor, gere o QR Code primeiro.');
                return;
            }
            qrCodeCanvas.toBlob(function(blob) {
                navigator.clipboard.write([
                    new ClipboardItem({ 'image/png': blob })
                ]).then(function() {
                    alert('QR Code copiado para a área de transferência!');
                }).catch(function(err) {
                    console.error('Erro ao copiar imagem: ', err);
                    alert('Não foi possível copiar a imagem. Utilize o botão Baixar.');
                });
            });
        }

        function applyPhoneMask() {
            VMasker(document.querySelector("#telefone")).maskPattern("(99) 99999-9999");
        }

        function displayiPhoneMessage() {
            const isiPhone = /iPhone/i.test(navigator.userAgent);
            const messageContainer = document.querySelector('.msgBtniphone');

            if (isiPhone && messageContainer) {
                messageContainer.style.display = 'block';
                document.getElementById('msgBtn').style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            applyPhoneMask();

            document.getElementById('downloadBtn').addEventListener('click', downloadQRCode);

            // Oculta o botão Copiar em navegadores sem suporte à Clipboard API de imagens (ex.: Safari/iOS antigos)
            if (navigator.clipboard && window.ClipboardItem) {
                document.getElementById('copyBtn').addEventListener('click', copyQRCode);
            } else {
                document.getElementById('copyBtn').style.display = 'none';
            }
        });
    </script>
</body>
</html>
