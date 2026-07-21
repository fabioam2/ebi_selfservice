<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <title>Gerador de QR Code v2 – Espaço Bíblico Infantil</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-masker/1.1.1/vanilla-masker.min.js"></script>

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
        }
    </style>
</head>
<body>
    <div class="qr-page">
        <div class="qr-container">
            <div class="qr-header">
                <i class="fas fa-qrcode icon-header"></i>
                <h1>Gerador de QR Code <span class="badge badge-warning" style="font-size:.55em;vertical-align:middle">v2</span></h1>
                <p>Espaço Bíblico Infantil — inclui data de nascimento</p>
            </div>

            <div class="important-box">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>O QR Code NÃO garante vaga no EBI</strong>, sendo necessário retirar a senha ao chegar na igreja.
            </div>

            <details class="instructions-collapse">
                <summary><i class="fas fa-info-circle"></i> Como funciona <i class="fas fa-chevron-down chev"></i></summary>
                <ul>
                    <li>O QR Code pode ser usado em todas as visitas ao Espaço Bíblico Infantil.</li>
                    <li>Cada família deve gerar apenas um QR Code por responsável.</li>
                    <li>Guarde o QR Code gerado. Caso o perca, poderá gerar um novo facilmente.</li>
                    <li>O responsável precisa ser maior de idade.</li>
                    <li><strong>Novidade (v2):</strong> este QR Code inclui a data de nascimento de cada criança, permitindo identificar aniversários e calcular a idade exata.</li>
                </ul>
            </details>

            <form id="qrForm">
                <div class="section-title"><i class="fas fa-user"></i>Dados do Responsável</div>

                <div class="form-group">
                    <label for="nomePai">Nome do Responsável</label>
                    <input type="text" class="form-control" id="nomePai" name="nomePai" placeholder="Nome e sobrenome" autocomplete="name">
                    <span id="errorNomePai" class="error"></span>
                </div>

                <div class="form-row">
                    <div class="form-group col-7">
                        <label for="telefone">Telefone</label>
                        <input type="text" inputmode="tel" class="form-control" id="telefone" name="telefone" placeholder="(00) 00000-0000" autocomplete="tel">
                        <span id="errorTelefone" class="error"></span>
                    </div>
                    <div class="form-group col-5">
                        <label for="comum">Comum</label>
                        <input type="text" class="form-control" id="comum" name="comum" placeholder="Ex: Central">
                        <span id="errorComum" class="error"></span>
                    </div>
                </div>

                <div class="section-title"><i class="fas fa-child"></i>Crianças</div>

                <div id="children-container">
                    <div class="child-card" id="child-1">
                        <div class="child-card-header">
                            <span class="child-badge">1</span>
                            <span class="child-label">Criança 1</span>
                        </div>
                        <div class="form-group">
                            <label for="nomeFilho1">Nome da Criança</label>
                            <input type="text" class="form-control" id="nomeFilho1" name="nomeFilho1" placeholder="Nome e sobrenome">
                            <span id="errorNomeFilho1" class="error"></span>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-7">
                                <label for="dataNascimentoMaskFilho1">Data de Nascimento</label>
                                <input type="text" inputmode="numeric" class="form-control" id="dataNascimentoMaskFilho1" name="dataNascimentoMaskFilho1" placeholder="dd/mm/aaaa" maxlength="10">
                            </div>
                            <div class="form-group col-5">
                                <label>&nbsp;</label>
                                <span id="idadeDisplay1" class="idade-display"></span>
                            </div>
                        </div>
                        <span id="errorDataNascimentoFilho1" class="error"></span>
                        <span id="errorIdade1" class="error"></span>
                    </div>
                </div>

                <div class="form-group" style="margin-top:16px;">
                    <label for="portariaQR" style="font-weight:600;font-size:0.85rem;">Portaria (opcional — auto-cadastra ao ler):</label>
                    <input type="text" id="portariaQR" maxlength="1" style="width:50px;text-align:center;text-transform:uppercase;font-weight:700;font-size:1.1rem;border:2px solid var(--brand, #0e7490);border-radius:8px;padding:6px;" placeholder="A">
                    <small style="color:#666;display:block;margin-top:4px;">Se preenchido, o QR Code incluirá a portaria e o cadastro será automático ao ler com a pistola.</small>
                </div>

                <button type="button" class="btn btn-add-child" onclick="addChildField()">
                    <i class="fas fa-plus mr-1"></i>Adicionar Criança
                </button>

                <button type="button" class="btn btn-generate" onclick="generateQRCode()">
                    <i class="fas fa-qrcode mr-1"></i>Gerar QR Code
                </button>

                <div id="msgBtn" class="alert alert-info alert-msg" style="display:none;">
                    <i class="fas fa-camera mr-1"></i>Tire um "print" da tela e guarde o QR Code para apresentar na recepção do Espaço Bíblico Infantil.
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
        let childCount = 1;
        const maxChildren = 5;

        function removeAccents(text) {
            return text.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        }

        // --- Funções de Data e Idade ---

        /**
         * Calcula a idade em anos a partir de uma data de nascimento no formato DD/MM/AAAA.
         * @param {string} dateString Data de nascimento no formato DD/MM/AAAA.
         * @returns {number|null} A idade em anos ou null se o formato for inválido.
         */
        function calculateAge(dateString) {
            const parts = dateString.split('/');
            if (parts.length !== 3) return null;

            const day = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10);
            const year = parseInt(parts[2], 10);

            if (isNaN(day) || isNaN(month) || isNaN(year) || year < 1900) return null;

            const today = new Date();
            const birthDate = new Date(year, month - 1, day); // month - 1 pois JS é 0-indexed

            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();

            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            return age >= 0 ? age : null;
        }

        /**
         * Atualiza a exibição da idade e retorna a idade calculada.
         */
        function updateChildAge(childId) {
            const maskedInput = document.getElementById(`dataNascimentoMaskFilho${childId}`);
            const ageDisplay = document.getElementById(`idadeDisplay${childId}`);
            const errorIdade = document.getElementById(`errorIdade${childId}`);

            const maskedValue = maskedInput.value;
            ageDisplay.innerText = '';
            errorIdade.innerText = '';

            if (maskedValue.length === 10) {
                const age = calculateAge(maskedValue);
                if (age !== null && age >= 3 && age <= 11) {
                    ageDisplay.innerText = `Idade: ${age} anos`;
                    return age;
                } else if (age !== null) {
                    ageDisplay.innerText = `Idade: ${age} anos`;
                    errorIdade.innerText = `Idade fora da faixa permitida (3-11 anos).`;
                }
            }
            return null;
        }

        // Aplica a máscara de data a um campo específico e adiciona listener para o cálculo da idade
        function applyDateMaskToChild(childId) {
            const maskedInput = document.getElementById(`dataNascimentoMaskFilho${childId}`);
            if (maskedInput) {
                 VMasker(maskedInput).maskPattern("99/99/9999");
                 // Adiciona listener para recalcular a idade a cada mudança
                 maskedInput.addEventListener('input', () => updateChildAge(childId));
            }
        }
        // -----------------------------------------------------------------

        function addChildField() {
            if (childCount < maxChildren) {
                childCount++;
                const newChildDiv = document.createElement('div');
                newChildDiv.classList.add('child-card');
                newChildDiv.id = `child-${childCount}`;
                newChildDiv.innerHTML = `
                    <div class="child-card-header">
                        <span class="child-badge">${childCount}</span>
                        <span class="child-label">Criança ${childCount}</span>
                        <button type="button" class="remove-child-btn" onclick="removeChildField(${childCount})" aria-label="Remover criança">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <div class="form-group">
                        <label for="nomeFilho${childCount}">Nome da Criança</label>
                        <input type="text" class="form-control" id="nomeFilho${childCount}" name="nomeFilho${childCount}" placeholder="Nome e sobrenome">
                        <span id="errorNomeFilho${childCount}" class="error"></span>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-7">
                            <label for="dataNascimentoMaskFilho${childCount}">Data de Nascimento</label>
                            <input type="text" inputmode="numeric" class="form-control" id="dataNascimentoMaskFilho${childCount}" name="dataNascimentoMaskFilho${childCount}" placeholder="dd/mm/aaaa" maxlength="10">
                        </div>
                        <div class="form-group col-5">
                            <label>&nbsp;</label>
                            <span id="idadeDisplay${childCount}" class="idade-display"></span>
                        </div>
                    </div>
                    <span id="errorDataNascimentoFilho${childCount}" class="error"></span>
                    <span id="errorIdade${childCount}" class="error"></span>
                `;
                document.getElementById('children-container').appendChild(newChildDiv);

                // Aplica a máscara e listeners para o novo campo de data
                applyDateMaskToChild(childCount);

            } else {
                alert('Você pode cadastrar no máximo 5 crianças.');
            }
        }

        function removeChildField(childId) {
            const childDiv = document.getElementById(`child-${childId}`);
            if (childDiv) {
                childDiv.remove();
                childCount--;
                // Reorganizar IDs e rótulos das crianças restantes
                const children = document.getElementsByClassName('child-card');
                for (let i = 0; i < children.length; i++) {
                    const newId = i + 1;
                    children[i].id = `child-${newId}`;

                    // BADGE / LABEL
                    children[i].querySelector('.child-badge').innerText = newId;
                    children[i].querySelector('.child-label').innerText = `Criança ${newId}`;

                    // NOME
                    children[i].querySelector('label[for^="nomeFilho"]').setAttribute('for', `nomeFilho${newId}`);
                    children[i].querySelector('input[id^="nomeFilho"]').id = `nomeFilho${newId}`;
                    children[i].querySelector('input[name^="nomeFilho"]').name = `nomeFilho${newId}`;
                    children[i].querySelector('span[id^="errorNomeFilho"]').id = `errorNomeFilho${newId}`;

                    // DATA DE NASCIMENTO (mask input)
                    children[i].querySelector('label[for^="dataNascimentoMaskFilho"]').setAttribute('for', `dataNascimentoMaskFilho${newId}`);
                    const maskInput = children[i].querySelector('input[id^="dataNascimentoMaskFilho"]');
                    maskInput.id = `dataNascimentoMaskFilho${newId}`;
                    maskInput.name = `dataNascimentoMaskFilho${newId}`;
                    children[i].querySelector('span[id^="errorDataNascimentoFilho"]').id = `errorDataNascimentoFilho${newId}`;

                    // IDADE (display e erro)
                    children[i].querySelector('span[id^="idadeDisplay"]').id = `idadeDisplay${newId}`;
                    children[i].querySelector('span[id^="errorIdade"]').id = `errorIdade${newId}`;

                    // REMOVER BOTÃO
                    const removeBtn = children[i].querySelector('button.remove-child-btn');
                    if (removeBtn) {
                        removeBtn.onclick = function() {
                            removeChildField(newId);
                        };
                    }

                    // Reaplica máscara e listeners
                    applyDateMaskToChild(newId);
                    updateChildAge(newId); // Recalcula idade ao reorganizar
                }
            }
        }

        function generateQRCode() {
            var nomePai = removeAccents(document.getElementById('nomePai').value);
            var telefone = removeAccents(document.getElementById('telefone').value);
            var comum = removeAccents(document.getElementById('comum').value);

            var qrData = "";
            var isValid = true;

            // Limpa mensagens de erro do Responsável
            document.getElementById('errorNomePai').innerText = '';
            document.getElementById('errorTelefone').innerText = '';
            document.getElementById('errorComum').innerText = '';

            // Validação dos dados do Responsável
            if (!nomePai) {
                document.getElementById('errorNomePai').innerText = 'Este campo é obrigatório';
                isValid = false;
            }
            if (!telefone) {
                document.getElementById('errorTelefone').innerText = 'Este campo é obrigatório';
                isValid = false;
            }
            if (!comum) {
                document.getElementById('errorComum').innerText = 'Este campo é obrigatório';
                isValid = false;
            }

            // Validação e Montagem dos dados das Crianças
            for (let i = 1; i <= childCount; i++) {
                const nomeFilho = removeAccents(document.getElementById(`nomeFilho${i}`).value);
                const dataNascimentoMaskValue = document.getElementById(`dataNascimentoMaskFilho${i}`).value;

                // Recalcula e obtém a idade/valida novamente
                const idade = updateChildAge(i);

                // Limpa o erro de nome e data que pode ter ficado do updateChildAge
                const errorNomeFilho = document.getElementById(`errorNomeFilho${i}`);
                const errorDataNascimentoFilho = document.getElementById(`errorDataNascimentoFilho${i}`);
                errorNomeFilho.innerText = '';
                errorDataNascimentoFilho.innerText = '';

                if (!nomeFilho) {
                    errorNomeFilho.innerText = 'Este campo é obrigatório';
                    isValid = false;
                }

                if (!dataNascimentoMaskValue || dataNascimentoMaskValue.length < 10) {
                    errorDataNascimentoFilho.innerText = 'Data de nascimento é obrigatória (dd/mm/aaaa)';
                    isValid = false;
                }

                if (idade === null || idade < 3 || idade > 11) {
                    document.getElementById(`errorIdade${i}`).innerText = 'Idade inválida ou fora da faixa (3-11 anos).';
                    isValid = false;
                }

                if (isValid && nomeFilho && idade !== null) {
                    // Estrutura de dados por linha (uma criança) — formato v2, com data de nascimento:
                    // NomeFilho \t NomePai \t IdadeFilho \t TelefonePai \t ComumPai \t DataNascimentoFilho
                    // A data de nascimento (dd/mm/aaaa) permite identificar aniversários e
                    // recalcular a idade exata na leitura do QR Code.
                    qrData += `${nomeFilho}\t${nomePai}\t${idade}\t${telefone}\t${comum}\t${dataNascimentoMaskValue}`;

                    if (i < childCount) {
                        qrData += '\r'; // Separador de registros (crianças)
                    }
                }
            }

            // Incluir portaria no final do QR (permite cadastro automático ao ler)
            var portariaQR = document.getElementById('portariaQR').value.toUpperCase().trim();
            if (portariaQR && qrData) {
                qrData += '\r' + portariaQR; // Enter + Portaria após última criança
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
            link.download = 'qrcode-ebi.png';
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

        function applyInitialDateMasks() {
            // Aplica a máscara e o cálculo de idade para a primeira criança
            applyDateMaskToChild(1);
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
            applyInitialDateMasks(); // Aplica a máscara e o cálculo para a primeira criança

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
