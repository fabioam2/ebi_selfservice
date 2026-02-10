<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de QRCode para Espaço Infantil</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-masker/1.1.1/vanilla-masker.min.js"></script>
    
    <style>
        :root {
            --cor-azul-claro: #ADD8E6;
            --cor-texto-principal: #333;
            --cor-verde-claro: #98FB98;
            --cor-texto-botao-principal: #fff;
            --cor-verde-hover: #8FBC8F;
            --cor-vermelho-claro: #FFE0E0;
            --cor-vermelho-hover: #FFD1D1;
            --cor-laranja-palido: #FFDAB9;
            --cor-laranja-hover: #E9967A;
            --cor-azul-claro-botao: #87CEEB;
            --cor-azul-claro-hover: #6495ED;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            font-family: Arial, sans-serif;
            flex-direction: column;
            margin-top: 20px;
        }
        
        .container {
            text-align: left;
            width: 80%;
            margin-bottom: 20px;
        }
        
        .instructions {
            text-align: justify;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .importante {
            display: block;
            width: 100%;
            margin: none;
            background-color: var(--cor-vermelho-claro);
            color: var(--cor-texto-principal);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: left;
            text-decoration: none;
            font-size: 16px;
        }
        
        .child-info {
            border: 1px solid #ccc;
            padding: 20px;
            margin-bottom: 10px;
            border-radius: 5px;
            position: relative;
        }

        .child-info label {
            display: block;
            margin-bottom: 5px;
        }
        
        /* Estilo para ajustar o campo de data e a exibição da idade */
        .child-data-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .child-data-group input[type="text"] {
            width: 100px; /* Ajusta o tamanho da caixa de texto da data */
        }
        .idade-display {
            font-weight: bold;
            color: var(--cor-texto-principal);
            padding: 5px 0;
        }

        .remove-child-btn {
            position: absolute;
            bottom: 11px;
            left: 15px;
            background-color: var(--cor-vermelho-claro);
            color: var(--cor-texto-principal);
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
        }

        .remove-child-btn:hover {
            background-color: var(--cor-vermelho-hover);
        }

        .button {
            display: block;
            width: 80%;
            margin: 10px auto;
            padding: 10px 15px;
            background-color: var(--cor-verde-claro);
            color: var(--cor-texto-botao-principal);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
        }

        .button:hover {
            background-color: var(--cor-verde-hover);
        }

        .msgBtn, .msgBtniphone {
            display: flex;
            justify-content: center; 
            align-items: center;
            width: 80%;
            margin: 10px auto;
            padding: 10px 15px;
            background-color: var(--cor-azul-claro);
            color: var(--cor-texto-principal);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
        }

          
        .privacidade {
            display: block;
            width: 80%;
            margin: 10px auto;
            padding: 10px 15px;
            background-color: var(--cor-vermelho-claro);
            color: var(--cor-texto-principal);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
        }

        .privacidade:hover {
            background-color: var(--cor-vermelho-hover);
        }

        .error {
            color: red;
        }

        #qrcode-container {
            position: relative;
            text-align: center;
            margin-bottom: 20px;
        }

        #button-container {
            display: flex;
            width: 80%;
            justify-content: space-around;
            margin-bottom: 20px;
        }

        #downloadBtn, #copyBtn {
            padding: 10px 15px;
            background-color: var(--cor-laranja-palido);
            color: var(--cor-texto-principal);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
        
        #downloadBtn:hover, #copyBtn:hover {
            background-color: var(--cor-laranja-hover);
        }

        .add-child-btn {
            background-color: var(--cor-azul-claro-botao);
            color: var(--cor-texto-botao-principal);
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            width: 80%;
            margin: 10px auto;
            text-align: center;
            text-decoration: none;
        }

        .add-child-btn:hover {
            background-color: var(--cor-azul-claro-hover);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerador de QR Code – Espaço Bíblico Infantil</h1>
        <div class="instructions">
            <p>Com o objetivo de otimizar a entrada das crianças no EBI - Espaço Bíblico Infantil, passaremos a utilizar um QR Code de identificação, contendo os dados do responsável e das crianças.<br>
            
            
            
            <div class="importante">
                <b>O QR Code NÃO garante vaga no EBI, sendo necessário retirar a senha ao chegar na igreja.</b><br>
            </div><br>
            <b>Orientações:</b><br>
                O QR Code gerado poderá ser utilizado em todas as visitas ao Espaço Bíblico Infantil.<br>
            </div>
        <div class="instructions">    
            Cada família deverá gerar apenas um QR Code por responsável.<br>
            O responsável deve guardar o QR Code gerado. Caso o perca, poderá gerar um novo facilmente.<br>
            O responsável precisa ser maior de idade.<br>
             </p>
        </div>
        <form id="qrForm">
            <label for="nomePai">Nome do Responsável:</label>
            <input type="text" id="nomePai" name="nomePai" placeholder="Nome e Sobrenome"><br>
            <span id="errorNomePai" class="error"></span><br>
            
            <label for="telefone">Telefone do Responsável:</label>
            <input type="text" inputmode="tel" id="telefone" name="telefone"><br>
            <span id="errorTelefone" class="error"></span><br>
            
            <label for="comum">Comum Congregação:</label>
            <input type="text" id="comum" name="comum"><br>
            <span id="errorComum" class="error"></span><br>

            <div id="children-container">
                <div class="child-info" id="child-1">
                    <label for="nomeFilho1">Nome da Criança 1:</label>
                    <input type="text" id="nomeFilho1" name="nomeFilho1" placeholder="Nome e Sobrenome"><br>
                    <span id="errorNomeFilho1" class="error"></span><br>

                    <label for="dataNascimentoFilho1">Data de Nascimento da Criança 1 (dd/mm/aaaa):</label>
                    <div class="child-data-group">
                        <input type="text" id="dataNascimentoMaskFilho1" name="dataNascimentoMaskFilho1" placeholder="dd/mm/aaaa" maxlength="10">
                        <span id="idadeDisplay1" class="idade-display"></span>
                    </div>
                    <span id="errorDataNascimentoFilho1" class="error"></span><br>
                    <span id="errorIdade1" class="error"></span><br>
                    
                </div>
            </div>

            <button type="button" class="add-child-btn" onclick="addChildField()">Adicionar Criança</button>
            
            <button type="button"  class="button"  onclick="generateQRCode()">Gerar QR</button>
            
            <div class="msgBtniphone" style="display:none;">
                iPhone: Para salvar a imagem do QR Code, toque e segure a imagem e selecione "Salvar no AppFotos" ou "Compartilhar".
            </div>

            <div id="msgBtn" class="msgBtn" style="display:none;">
                Tire 'print' da tela e guarde o QR Code para apresentar na recepção do Espaço bíblico infantil.
            </div>
        </form>

        <br>

        <div id="qrcode-container">
            <div id="qrcode"></div>
        </div>

        <br>

        <span class="privacidade">Privacidade: Os dados inseridos para gerar o QR Code não são armazenados!</span>
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
                newChildDiv.classList.add('child-info');
                newChildDiv.id = `child-${childCount}`;
                newChildDiv.innerHTML = `
                    <label for="nomeFilho${childCount}">Nome da Criança ${childCount}:</label>
                    <input type="text" id="nomeFilho${childCount}" name="nomeFilho${childCount}" placeholder="Nome e Sobrenome"><br>
                    <span id="errorNomeFilho${childCount}" class="error"></span><br>

                    <label for="dataNascimentoFilho${childCount}">Data de Nascimento da Criança ${childCount} (dd/mm/aaaa):</label>
                    <div class="child-data-group">
                        <input type="text" id="dataNascimentoMaskFilho${childCount}" name="dataNascimentoMaskFilho${childCount}" placeholder="dd/mm/aaaa" maxlength="10">
                        <span id="idadeDisplay${childCount}" class="idade-display"></span>
                    </div>
                    <span id="errorDataNascimentoFilho${childCount}" class="error"></span><br>
                    
                    <span id="errorIdade${childCount}" class="error"></span><br>

                    <button type="button" class="remove-child-btn" onclick="removeChildField(${childCount})">Remover</button>
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
                const children = document.getElementsByClassName('child-info');
                for (let i = 0; i < children.length; i++) {
                    const newId = i + 1;
                    children[i].id = `child-${newId}`;
                    
                    // NOME
                    children[i].querySelector('label[for^="nomeFilho"]').innerText = `Nome da Criança ${newId}:`;
                    children[i].querySelector('input[id^="nomeFilho"]').id = `nomeFilho${newId}`;
                    children[i].querySelector('input[name^="nomeFilho"]').name = `nomeFilho${newId}`;
                    children[i].querySelector('span[id^="errorNomeFilho"]').id = `errorNomeFilho${newId}`;
                    
                    // DATA DE NASCIMENTO (mask input)
                    children[i].querySelector('label[for^="dataNascimentoMaskFilho"]').innerText = `Data de Nascimento da Criança ${newId} (dd/mm/aaaa):`;
                    const maskInput = children[i].querySelector('input[id^="dataNascimentoMaskFilho"]');
                    maskInput.id = `dataNascimentoMaskFilho${newId}`;
                    maskInput.name = `dataNascimentoMaskFilho${newId}`;
                    children[i].querySelector('span[id^="errorDataNascimentoFilho"]').id = `errorDataNascimentoFilho${newId}`;
                    
                    // IDADE (display e erro)
                    children[i].querySelector('span[id^="idadeDisplay"]').id = `idadeDisplay${newId}`;
                    children[i].querySelector('span[id^="errorIdade"]').id = `errorIdade${newId}`;
                    
                    // REMOVER BOTÃO
                    const removeBtn = children[i].querySelector('button.remove-child-btn');
                    if(removeBtn) {
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
                    // Formata a data de nascimento para o QR Code (DDMMMAAAA sem máscara)
                    const dataNascimentoFilho = removeAccents(dataNascimentoMaskValue.replace(/[^0-9]/g, ''));

                    // Estrutura de dados por linha (uma criança):
                    
                    //SEM DATA nascimento
                    // NomeFilho \t NomePai \t DataNascFilho \t IdadeFilho \t TelefonePai \t ComumPai
                    qrData += `${nomeFilho}\t${nomePai}\t${idade}\t${telefone}\t${comum}`;
                    
                    // NomeFilho \t NomePai \t DataNascFilho \t IdadeFilho \t TelefonePai \t ComumPai
                  //  qrData += `${nomeFilho}\t${nomePai}\t${dataNascimentoFilho}\t${idade}\t${telefone}\t${comum}`;
                    if (i < childCount) {
                        qrData += '\r'; // Separador de registros (crianças)
                    }
                }
            }
            
            applyPhoneMask();

            if (isValid && qrData) {
                document.getElementById('qrcode').innerHTML = '';
                var qrcode = new QRCode(document.getElementById("qrcode"), {
                    text: qrData,
                    width: 256,
                    height: 256,
                    correctLevel: QRCode.CorrectLevel.H
                });

                qrCodeCanvas = document.querySelector('#qrcode canvas');
                if (qrCodeCanvas) {
                    document.getElementById('msgBtn').style.display = 'flex';
                    displayiPhoneMessage();
                } else {
                    document.getElementById('msgBtn').style.display = 'none';
                    document.querySelector('.msgBtniphone').style.display = 'none';
                }
            } else {
                document.getElementById('msgBtn').style.display = 'none';
                document.querySelector('.msgBtniphone').style.display = 'none';
                document.getElementById('qrcode').innerHTML = '';
            }
        }

        // [Função copyQRCode omitida por brevidade, mas intacta]
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
                     alert('Não foi possível copiar a imagem.');
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
                messageContainer.style.display = 'flex';
                document.getElementById('msgBtn').style.display = 'none'; 
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            applyPhoneMask();
            applyInitialDateMasks(); // Aplica a máscara e o cálculo para a primeira criança
        });
    </script>
</body> 
</html>