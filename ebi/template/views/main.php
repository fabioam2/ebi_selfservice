<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Crianças</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #eef2f7; }
        .container { margin-top: 20px; padding: 20px; background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-width: 1300px; }
        .tabela-scrollable { max-height: 400px; overflow-y: auto; margin-bottom: 20px; border: 1px solid #dee2e6; border-radius: 8px; }
        .tabela-scrollable table { width: 100%; margin-bottom: 0; }
        .tabela-scrollable th { background-color: #007bff; color: white; position: sticky; top: 0; z-index: 10; font-size: 0.9rem; padding: 0.5rem; text-align: center; }
        .tabela-scrollable td { font-size: 0.85rem; padding: 0.4rem; vertical-align: middle; }
        .tabela-scrollable th:nth-child(6), .tabela-scrollable th:nth-child(7), .tabela-scrollable th:nth-child(10) { text-align: left;}
        .tabela-scrollable td:nth-child(6), .tabela-scrollable td:nth-child(7), .tabela-scrollable td:nth-child(10) { text-align: left;}

        .form-control-sm { height: calc(1.5em + .5rem + 2px); padding: .25rem .5rem; font-size: .875rem; line-height: 1.5; border-radius: .2rem; }
        .btn { margin-right: 8px; border-radius: 5px; padding: 8px 15px; transition: all 0.2s ease-in-out; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .alert { border-radius: 8px; margin-bottom: 15px; }
        header h1 img { border: 2px solid #007bff; }
        .form-control { border-radius: 5px; border-color: #ced4da; }
        .form-control:focus { border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }

        #formNovoCadastro .form-labels .col { font-weight: bold; color: #495057; padding-bottom: 0.2rem; font-size: 0.85rem; white-space: nowrap; }
        #formNovoCadastro .form-registro-linha { margin-bottom: 0.25rem; padding: 0.15rem 0; }
        #formNovoCadastro .form-registro-linha .form-group { margin-bottom: 0.1rem; padding-left: 5px; padding-right: 5px; }
        #formNovoCadastro .form-control-sm { font-size: 0.85rem; }

        .col-nome-crianca { flex: 0 0 23%; max-width: 23%; }
        .col-responsavel { flex: 0 0 23%; max-width: 23%; }
        .col-idade { flex: 0 0 9%; max-width: 9%; }
        .col-telefone { flex: 0 0 18%; max-width: 18%; }
        .col-comum { flex: 0 0 18%; max-width: 18%; }
        .col-acao { flex: 0 0 9%; max-width: 9%; }


        .dropdown-menu button.dropdown-item, .dropdown-menu a.dropdown-item { cursor: pointer; }
        .modal-backdrop.show { opacity: .5; }
        .modal.show { display: block; }
        #backupPreviewContent { font-size: 0.8em; white-space: pre-wrap; max-height: 100px; overflow-y: auto; background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 5px; margin-top: 10px; border-radius: .2rem;}
        .status-icon svg { vertical-align: middle; }

        .filtro-portaria-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .filtro-portaria-container .form-label { margin-right: 0.5rem; margin-bottom: 0; white-space: nowrap;}
        #filtroPortaria {
            height: calc(1.5em + .5rem + 2px); 
            padding-left: 0.3rem !important; 
            padding-right: 0.3rem !important;
            margin-right: 0.5rem;
            width: auto; 
            min-width: 100px; 
            display: inline-block; 
        }
        .filtro-portaria-group { display: flex; align-items: center; }

        .btn-copiar-quadrado {
            width: 31px;  
            height: 31px; 
            padding: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .btn-copiar-quadrado svg { 
            width: 16px;
            height: 16px;
        }

        .total-cadastros-info {
            font-size: 0.9rem;
            color: #fff;
            background-color: #007bff; 
            border: 1px solid #007bff;
            padding: 0.375rem 0.75rem;
            border-radius: .2rem;
            margin-left: 10px;
            margin-right: 10px; 
            align-self: center; 
            display: inline-flex; 
            align-items: center; 
        }
        .total-cadastros-info svg {
            margin-right: 0.35rem;
            vertical-align: -0.1em; 
        }
        
        .total-cadastros-alerta {
             background-color: #dc3545 !important; /* Vermelho de perigo do Bootstrap */
              border-color: #b22222 !important;
        }
        .portaria-cadastro-group {
            display: flex;
            align-items: center;
            background-color: #17a2b8; 
            padding: 0.375rem 0.75rem;
            border-radius: .25rem;
            border: 1px solid #117a8b; 
        }
        .portaria-cadastro-group label {
            margin-bottom: 0;
            margin-right: 0.5rem;
            font-weight: normal;
            color: #fff; 
        }
        .portaria-cadastro-group input {
            border: none !important;
            background-color: transparent !important;
            box-shadow: none !important;
            padding-left: 0.2rem !important;
            width: 40px !important; 
            color: #fff !important; 
            text-transform: uppercase; 
        }
        .portaria-cadastro-group input::placeholder {
            color: rgba(255, 255, 255, 0.7);
            opacity: 1; 
        }
        .portaria-cadastro-group input:-ms-input-placeholder { 
            color: rgba(255, 255, 255, 0.7);
        }
        .portaria-cadastro-group input::-ms-input-placeholder { 
            color: rgba(255, 255, 255, 0.7);
        }

        @media print {
            body { font-size: 10pt; }
            .container { box-shadow: none; margin-top: 0; padding: 0; max-width: 100%; }
            header, .alert, #formNovoCadastro, .filtro-portaria-container, #formListaCriancas .d-flex.justify-content-between, .modal, .btn, form[action*="logout"], .dropdown, .no-print {
                display: none !important;
            }
            .tabela-scrollable { max-height: none; overflow-y: visible; border: none; }
            .tabela-scrollable th { background-color: #f0f0f0 !important; color: #000 !important; font-size: 9pt; }
            .tabela-scrollable td { font-size: 9pt; }
            .tabela-scrollable th, .tabela-scrollable td { padding: 3px; border: 1px solid #ccc; }
            #lista-criancas tr td:first-child, #lista-criancas tr th:first-child { display: none; }
            #lista-criancas tr td:last-child, #lista-criancas tr th:last-child { display: none; }
            .status-icon svg { display: none; } 
            .status-icon .print-status { display: inline !important; } 
        }
        .status-icon .print-status { display: none; } 

    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="d-flex align-items-center justify-content-between mb-3">
            <div style="min-width: 220px;"></div>
            <h1 class="text-center mb-0">
                <img src="https://placehold.co/60x60/007bff/white?text=Kids" alt="Ícone de Criança" style="vertical-align: middle; border-radius: 50%; margin-right: 10px;">
                Cadastro de Crianças
            </h1>
            <div class="d-flex align-items-center" style="min-width: 220px; justify-content: flex-end;">
                <a href="./saida/index.php" class="btn btn-outline-secondary btn-sm mr-1" target="_blank">Saída</a>
                <a href="./saida/painel.php" class="btn btn-outline-secondary btn-sm mr-1" target="_blank">Painel Saída</a>
                <a href="https://qrcode.ccbcampinas.org.br/" class="btn btn-outline-secondary btn-sm" target="_blank">QrCode</a>
            </div>
        </header>

        <?php if ($mensagemSucesso): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $mensagemSucesso; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $mensagemErro; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>" class="mb-3 p-3 border rounded bg-light shadow-sm" id="formNovoCadastro">
            <?php echo csrf_field(); ?>
            <div class="form-row form-labels d-none d-md-flex">
                <div class="col col-nome-crianca">Nome Criança</div>
                <div class="col col-responsavel">Responsável</div>
                <div class="col col-idade text-center">Idade</div>
                <div class="col col-telefone">Telefone</div>
                <div class="col col-comum">Comum</div>
                <div class="col col-acao text-center">Ação</div>
            </div>

            <?php for ($linha = 0; $linha < NUM_LINHAS_FORMULARIO_CADASTRO; $linha++): ?>
            <div class="form-row align-items-center form-registro-linha">
                <div class="form-group col-md col-nome-crianca">
                    <label for="input_<?php echo $linha; ?>_0" class="d-md-none">Nome Criança <?php echo $linha + 1; ?>:</label>
                    <input type="text" class="form-control form-control-sm cadastro-input" id="input_<?php echo $linha; ?>_0" name="nome_crianca[]" data-linha="<?php echo $linha; ?>" data-col="0" placeholder="Nome da Criança">
                </div>
                <div class="form-group col-md col-responsavel">
                    <label for="input_<?php echo $linha; ?>_1" class="d-md-none">Responsável <?php echo $linha + 1; ?>:</label>
                    <input type="text" class="form-control form-control-sm cadastro-input" id="input_<?php echo $linha; ?>_1" name="nome_responsavel[]" data-linha="<?php echo $linha; ?>" data-col="1" placeholder="Nome do Responsável">
                </div>
                <div class="form-group col-md col-idade">
                    <label for="input_<?php echo $linha; ?>_2" class="d-md-none">Idade <?php echo $linha + 1; ?>:</label>
                    <input type="number" class="form-control form-control-sm cadastro-input text-center" id="input_<?php echo $linha; ?>_2" name="idade[]" min="0" data-linha="<?php echo $linha; ?>" data-col="2" placeholder="Idade">
                </div>
                <div class="form-group col-md col-telefone">
                    <label for="input_<?php echo $linha; ?>_3" class="d-md-none">Telefone <?php echo $linha + 1; ?>:</label>
                    <input type="text" class="form-control form-control-sm telefone-mask cadastro-input" id="input_<?php echo $linha; ?>_3" name="telefone[]" data-linha="<?php echo $linha; ?>" data-col="3" placeholder="(00) 00000-0000">
                </div>
                <div class="form-group col-md col-comum">
                    <label for="input_<?php echo $linha; ?>_4" class="d-md-none">Comum <?php echo $linha + 1; ?>:</label>
                    <input type="text" class="form-control form-control-sm cadastro-input" id="input_<?php echo $linha; ?>_4" name="comum[]" data-linha="<?php echo $linha; ?>" data-col="4" placeholder="Comum">
                </div>
                <div class="form-group col-md col-acao px-1 d-flex align-items-center justify-content-center">
                    <?php if ($linha > 0): ?>
                    <button type="button" class="btn btn-primary btn-sm btn-copiar-quadrado btn-copiar-dados" data-target-linha="<?php echo $linha; ?>" title="Copiar Responsável, Telefone e Comum da Linha 1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-files" viewBox="0 0 16 16">
                            <path d="M13 0H6a2 2 0 0 0-2 2 2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2 2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm0 13V4a2 2 0 0 0-2-2H5a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1zM3 4a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4z"/>
                        </svg>
                    </button>
                    <button type="button" class="btn btn-warning btn-sm btn-copiar-quadrado ml-1" title="Limpar esta linha" onclick="limparLinhaCadastro(<?php echo $linha; ?>)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eraser" viewBox="0 0 16 16">
                            <path d="M8.086 2.207a2 2 0 0 1 2.828 0l3.879 3.879a2 2 0 0 1 0 2.828l-5.5 5.5A2 2 0 0 1 7.879 15H5.12a2 2 0 0 1-1.414-.586l-2.5-2.5a2 2 0 0 1 0-2.828zm2.121.707a1 1 0 0 0-1.414 0L4.16 7.547l5.293 5.293 4.633-4.633a1 1 0 0 0 0-1.414zM8.708 13.293l.019-.019-3.44-3.441.013.012a2.5 2.5 0 0 1 3.408 3.416z"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endfor; ?>

            <div class="form-row mt-3 align-items-end">
                <div class="col-auto mr-auto">
                    <div class="portaria-cadastro-group">
                        <label for="portaria_cadastro" class="form-label">Portaria:</label>
                        <input type="text" class="form-control form-control-sm" id="portaria_cadastro" name="portaria_cadastro" placeholder="" maxlength="1">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-warning btn-sm mr-2" id="btnLimparCadastro" title="Limpar campos do formulário de cadastro">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eraser mr-1" viewBox="0 0 16 16"><path d="M8.086 2.207a2 2 0 0 1 2.828 0l3.879 3.879a2 2 0 0 1 0 2.828l-5.5 5.5A2 2 0 0 1 7.879 15H5.12a2 2 0 0 1-1.414-.586l-2.5-2.5a2 2 0 0 1 0-2.828zm2.121.707a1 1 0 0 0-1.414 0L4.16 7.547l5.293 5.293 4.633-4.633a1 1 0 0 0 0-1.414zM8.708 13.293l.019-.019-3.44-3.441.013.012a2.5 2.5 0 0 1 3.408 3.416z"/></svg>
                        Limpar
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm" name="cadastrar" id="btnCadastrar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus-fill mr-1" viewBox="0 0 16 16"><path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/><path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5"/></svg>
                        Cadastrar
                    </button>
                </div>
            </div>
        </form>
        <hr class="my-3">

        <div class="filtro-portaria-container">
            <div class="filtro-portaria-group">
                <label for="filtroPortaria" class="form-label">Filtrar Portaria:</label>
                <select multiple class="form-control form-control-sm" id="filtroPortaria" name="filtro_portaria_selecionadas[]"></select>
                <button type="button" class="btn btn-outline-secondary btn-sm ml-2" id="limparFiltroPortaria">Limpar Filtro</button>
            </div>
        </div>

        <!-- Painel Testar Impressão -->
        <div id="painelTesteImpressao" class="d-none p-3 mb-3 rounded border" style="background:#fff8e1; border-color:#ffe082 !important;">
            <div class="d-flex align-items-center mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#e65100" class="bi bi-tag-fill mr-2" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/></svg>
                <strong style="color:#e65100;">Modo Testar Impressão ATIVO</strong>
                <small class="ml-2 text-muted">— imprime apenas o nome da criança (sem etiqueta do responsável)</small>
            </div>
            <div class="form-row align-items-end">
                <div class="form-group col-auto mb-0">
                    <label for="testeX" class="col-form-label col-form-label-sm">Posição X</label>
                    <input type="number" class="form-control form-control-sm" id="testeX" value="140" min="0" max="500" style="width:90px;">
                </div>
                <div class="form-group col-auto mb-0">
                    <label for="testeY" class="col-form-label col-form-label-sm">Posição Y</label>
                    <input type="number" class="form-control form-control-sm" id="testeY" value="30" min="0" max="3000" style="width:90px;">
                </div>
                <div class="form-group col-auto mb-0">
                    <label for="testeFontSize" class="col-form-label col-form-label-sm">Tamanho da Fonte</label>
                    <input type="number" class="form-control form-control-sm" id="testeFontSize" value="20" min="5" max="200" style="width:90px;">
                </div>
                <div class="col-auto mb-0">
                    <button type="button" class="btn btn-sm btn-warning" onclick="salvarConfigTeste()">Aplicar</button>
                </div>
                <div class="col-auto mb-0">
                    <small id="testeConfigStatus" class="text-success d-none">✓ Configurações salvas</small>
                </div>
            </div>
        </div>


        <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>" id="formListaCriancas">
            <?php echo csrf_field(); ?>
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <button type="submit" class="btn btn-success" name="imprimir"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer-fill mr-2" viewBox="0 0 16 16"><path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1"/><path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm3 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/></svg>Imprimir</button>
                    <div class="total-cadastros-info <?php if ($totalDeCadastrosGeral > 90) echo 'total-cadastros-alerta'; ?>" title="Total de crianças cadastradas"> 
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                        </svg>
                        Total: <?php echo $totalDeCadastrosGeral; ?>
                    </div>
                    <div class="total-cadastros-info" title="Total de crianças com 3 anos">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-cake2" viewBox="0 0 16 16"><path d="M11.05 4.05a2.5 2.5 0 1 0-4.999.058A2.5 2.5 0 0 0 11.05 4.05zm-4.01-.034a1.5 1.5 0 1 1 2.998-.033A1.5 1.5 0 0 1 7.04 4.016z"/><path d="M6.536 6.072L5.85 7.305A.5.5 0 0 0 6.29 8h3.42a.5.5 0 0 0 .44-.695l-.686-1.233L13.617 5.25a.5.5 0 0 0-.39-.867H2.773a.5.5 0 0 0-.39.867l4.153.822z"/><path d="M12.572 6.092L12 6.224v4.248c.782.396 1.595.24 2.222-.457.628-.698.782-1.61.396-2.393-.386-.783-1.2-.937-1.932-.783zm-1.03 4.355V6.35H4.458v4.097c-.782-.396-1.595-.24-2.222.457-.628-.698-.782-1.61-.396-2.393.386.783 1.2.937 1.932.783A2.91 2.91 0 0 0 4.3 12.57a2.91 2.91 0 0 0 3.572 1.818c.782.396 1.595.24 2.222-.457.628-.698.782-1.61.396-2.393-.386-.783-1.2-.937-1.932-.783A2.91 2.91 0 0 0 11.542 10.447zM4.907 11.32c-.185.059-.354.15-.495.271-.14.12-.242.265-.304.423l-.066.165c-.073.188-.098.388-.066.58.03.18.113.348.235.485.122.137.28.238.458.29.178.053.368.057.546.013l.126-.03.11-.042.108-.054.092-.06a1.08 1.08 0 0 1 .23-.167c.05-.04.094-.085.132-.133.09-.114.155-.245.19-.383.036-.137.043-.28.023-.416a.97.97 0 0 0-.133-.437c-.08-.14-.19-.26-.32-.35-.13-.09-.27-.14-.41-.16l-.112-.01z"/></svg>
                        3 Anos: <?php echo $totalCriancas3Anos; ?>
                    </div>
                    <?php if (!empty($palavrasChaveComumDestaque)): ?>
                    <div class="total-cadastros-info" title="Total de cadastros de <?php echo sanitize_for_html($nomeComumDestaque); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-house-heart-fill" viewBox="0 0 16 16">
                            <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L8 2.207l6.646 6.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                            <path d="m8 3.293 6 6V13.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5V9.293l6-6Zm0 5.189c1.664-1.673 5.825 1.254 0 5.018-5.825-3.764-1.664-6.691 0-5.018Z"/>
                        </svg>
                        <?php echo sanitize_for_html($nomeComumDestaque); ?>: <?php echo $totalComumDestaque; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Badge status QZ Tray -->
                <span id="qzStatusBadge" class="badge badge-secondary mr-2" style="font-size:0.75rem; cursor:pointer; padding: 6px 10px; vertical-align: middle;" onclick="abrirModalQZTray()" title="Status do QZ Tray — clique para configurar">
                    <span id="qzStatusDot" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#dc3545;margin-right:4px;vertical-align:middle;"></span>
                    <span id="qzStatusText">QZ: Desconectado</span>
                </span>

                <div class="dropdown">
                    <button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuAdmin" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-fill mr-2" viewBox="0 0 16 16"><path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311a1.464 1.464 0 0 1-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413-1.4-2.397 0-2.81l.34-.1a1.464 1.464 0 0 1 .872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.858 2.929 2.929 0 0 1 0 5.858z"/></svg>
                        Ações Admin
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuAdmin">
                        <h6 class="dropdown-header">Relatórios</h6>
                        <button class="dropdown-item" type="button" id="btnImprimirLista">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer mr-1" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                            Imprimir Lista
                        </button>
                        <button class="dropdown-item" type="button" id="btnBaixarCSV">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filetype-csv mr-1" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M14 4.5V14a2 2 0 0 1-2 2h-1v-1h1a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5zM3.517 14.841a1.13 1.13 0 0 0 .401.823c.13.108.289.192.478.252.19.061.411.091.665.091.338 0 .624-.053.859-.158.236-.105.416-.252.539-.44.125-.189.187-.408.187-.656 0-.224-.045-.41-.134-.56a1.001 1.001 0 0 0-.375-.357 2.027 2.027 0 0 0-.566-.21l-.621-.144a.97.97 0 0 1-.404-.176.37.37 0 0 1-.144-.299c0-.156.062-.284.185-.384.125-.101.296-.152.512-.152.143 0 .266.023.37.068a.624.624 0 0 1 .246.181.56.56 0 0 1 .12.258h.75a1.092 1.092 0 0 0-.2-.566 1.21 1.21 0 0 0-.5-.41 1.813 1.813 0 0 0-.78-.152c-.293 0-.551.05-.776.15-.225.099-.4.24-.527.421-.127.182-.19.395-.19.639 0 .201.04.376.122.524.082.149.2.27.352.367.152.095.332.167.539.213l.618.144c.207.049.361.113.463.193a.387.387 0 0 1 .152.326.505.505 0 0 1-.085.29.559.559 0 0 1-.255.193c-.111.047-.249.07-.413.07-.117 0-.223-.013-.32-.04a.838.838 0 0 1-.248-.115.578.578 0 0 1-.255-.384h-.765zM.806 13.693c0-.248.034-.46.102-.633a.868.868 0 0 1 .302-.399.814.814 0 0 1 .475-.137c.15 0 .283.032.398.097a.7.7 0 0 1 .272.26.85.85 0 0 1 .12.381h.765v-.072a1.33 1.33 0 0 0-.466-.964 1.441 1.441 0 0 0-.489-.272 1.838 1.838 0 0 0-.606-.097c-.356 0-.66.074-.911.223-.25.148-.44.359-.572.632-.13.274-.196.6-.196.979v.498c0 .379.064.704.193.976.131.271.322.48.572.626.25.145.554.217.914.217.293 0 .554-.055.785-.164.23-.11.414-.26.55-.454a1.27 1.27 0 0 0 .226-.674v-.076h-.764a.799.799 0 0 1-.118.363.7.7 0 0 1-.272.25.874.874 0 0 1-.401.087.845.845 0 0 1-.478-.132.833.833 0 0 1-.299-.392 1.699 1.699 0 0 1-.102-.627v-.495zM6.78 15.29a1.176 1.176 0 0 1-.111-.449h.764a.578.578 0 0 0 .255.384c.07.049.154.087.25.114.095.028.201.041.319.041.164 0 .301-.023.413-.07a.559.559 0 0 0 .255-.193.507.507 0 0 0 .085-.29.387.387 0 0 0-.153-.326c-.101-.08-.256-.144-.463-.193l-.618-.143a1.72 1.72 0 0 1-.539-.214 1.001 1.001 0 0 1-.351-.367 1.068 1.068 0 0 1-.123-.524c0-.244.063-.457.19-.639.127-.181.303-.322.527-.422.225-.1.484-.149.777-.149.304 0 .568.05.79.152a1.21 1.21 0 0 1 .5.41c.12.174.186.381.2.566h-.75a.56.56 0 0 0-.12-.258.624.624 0 0 0-.246-.181.824.824 0 0 0-.37-.068c-.216 0-.387.05-.512.152a.472.472 0 0 0-.184.384c0 .121.048.22.143.3a.97.97 0 0 0 .404.175l.621.143c.217.05.406.12.566.211a1 1 0 0 1 .375.358c.09.148.134.335.134.56 0 .247-.063.466-.187.656-.124.188-.304.335-.54.44-.235.105-.52.158-.858.158-.254 0-.476-.03-.665-.09a1.404 1.404 0 0 1-.478-.253 1.13 1.13 0 0 1-.29-.375z"/></svg>
                            Baixar CSV
                        </button>
                        <button class="dropdown-item" type="button" id="btnBaixarXLS">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-spreadsheet mr-1" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V9H3V2a1 1 0 0 1 1-1h5.5zM3 12v-2h2v2zm0 1h2v2H4a1 1 0 0 1-1-1zm3 2v-2h3v2zm4 0v-2h3v1a1 1 0 0 1-1 1zm3-3h-3v-2h3zm-7 0v-2h3v2z"/></svg>
                            Baixar XLS
                        </button>
                        <div class="dropdown-divider"></div>
                        <button class="dropdown-item" type="button" onclick="abrirModalConfigImpressora()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer mr-1" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                            Configurar Impressora
                        </button>
                        <button class="dropdown-item" type="button" onclick="toggleModoDebugImpressao()" id="btnToggleDebug">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bug mr-1" viewBox="0 0 16 16"><path d="M4.355.522a.5.5 0 0 1 .623.333l.291.956A4.979 4.979 0 0 1 8 1c1.007 0 1.946.298 2.731.811l.29-.956a.5.5 0 1 1 .957.29l-.41 1.352A4.985 4.985 0 0 1 13 6h.5a.5.5 0 0 0 0-1h-.538l-.853-2.56a.5.5 0 1 1 .957-.29l.956 2.87A2 2 0 0 1 15.5 7.5v1a2 2 0 0 1-2 2h-.5v.5a5 5 0 0 1-10 0V10h-.5a2 2 0 0 1-2-2v-1a2 2 0 0 1 1.478-1.93l.956-2.87a.5.5 0 1 1 .957.29L2.538 5H2a.5.5 0 0 0 0 1h.5a4.985 4.985 0 0 1 1.432-3.503l-.41-1.352a.5.5 0 0 1 .333-.623zM4 7v4a4 4 0 0 0 8 0V7a4 4 0 0 0-8 0z"/></svg>
                            <span id="labelDebugMode">Modo Debug: OFF</span>
                        </button>
                        <button class="dropdown-item" type="button" onclick="toggleModoTesteImpressao()" id="btnToggleTeste">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tag mr-1" viewBox="0 0 16 16"><path d="M6 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-1 0a.5.5 0 1 0-1 0 .5.5 0 0 0 1 0z"/><path d="M2 1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 1 6.586V2a1 1 0 0 1 1-1zm0 5.586 7 7L13.586 9l-7-7H2v4.586z"/></svg>
                            <span id="labelTesteMode">Testar Impressão: OFF</span>
                        </button>
                        <div class="dropdown-divider"></div>
                        <button class="dropdown-item" type="button" onclick="abrirModalQZTray()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-usb-plug-fill mr-1" viewBox="0 0 16 16"><path d="M6.5 6a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/><path d="M3 10.5a.5.5 0 0 1 .5-.5H4V9H2V7H1.5a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5H4V3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v1h2.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5H14v2h-1v1h.5a.5.5 0 0 1 0 1h-3l-1 1H7l-1-1H3.5a.5.5 0 0 1-.5-.5z"/></svg>
                            Impressora QZ Tray
                        </button>
                        <a class="dropdown-item" href="calibrar.php" target="_blank">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-sliders mr-1" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M11.5 2a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM9.05 3a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0V3h9.05zM4.5 7a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM2.05 8a2.5 2.5 0 0 1 4.9 0H16v1H6.95a2.5 2.5 0 0 1-4.9 0H0V8h2.05zm9.45 4a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm-2.45 1a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0v-1h9.05z"/></svg>
                            Calibrar
                        </a>
                        <div class="dropdown-divider"></div>
                        <button class="dropdown-item" type="button" onclick="abrirModalZerarArquivo()">Zerar Arquivo</button>
                        <button class="dropdown-item" type="submit" name="preparar_recuperacao" form="formListaCriancas">Recuperar Backup <small class="text-muted">(.bkp.1 é o mais recente)</small></button>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo sanitize_for_html($_SERVER['PHP_SELF']); ?>?acao=logout">Sair do Sistema</a>
                    </div>
                </div>
            </div>

            <div class="tabela-scrollable shadow-sm mt-3">
                <table class="table table-striped table-hover table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 4%;" class="no-print"><input type="checkbox" id="selecionarTodos" title="Selecionar todos" aria-label="Selecionar todos"></th>
                            <th style="width: 6%;">Impresso</th>
                            <th style="width: 6%;">Portaria</th>
                            <th style="width: 7%;">Código</th>
                            <th style="width: 7%;">Cod Resp</th>
                            <th style="width: auto;">Nome da Criança</th>
                            <th style="width: auto;">Nome do Responsável</th>
                            <th style="width: 12%;">Telefone</th>
                            <th style="width: 6%;">Idade</th>
                            <th style="width: 12%;">Comum</th>
                            <th style="width: 8%;" class="no-print">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="lista-criancas">
                        <?php if (!empty($todosOsCadastros)): ?>
                            <?php foreach (array_reverse($todosOsCadastros, true) as $id => $crianca): ?>
                                <tr data-id="<?php echo sanitize_for_html($crianca['id']); ?>" data-portaria="<?php echo sanitize_for_html($crianca['portaria'] ?? ''); ?>">
                                    <td style="text-align: center;" class="no-print"><input type="checkbox" name="selecionados[]" value="<?php echo sanitize_for_html($crianca['id']); ?>" class="checkbox-crianca"></td>
                                    <td class="status-cell text-center">
                                        <span class="status-icon">
                                            <?php if (isset($crianca['statusImpresso']) && $crianca['statusImpresso'] === 'S'): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="green" class="bi bi-check-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                                                <span class="print-status">Sim</span>
                                            <?php else: ?>
                                                <span class="print-status">Não</span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td data-campo="portaria" style="text-align: center;"><?php echo sanitize_for_html($crianca['portaria'] ?? ''); ?></td>
                                    <td style="text-align: center;"><?php echo sanitize_for_html($crianca['id']); ?></td>
                                    <td style="text-align: center;"><?php echo sanitize_for_html($crianca['cod_resp'] ?? ''); ?></td>
                                    <td><?php echo sanitize_for_html($crianca['nomeCrianca']); ?></td>
                                    <td><?php echo sanitize_for_html($crianca['nomeResponsavel']); ?></td>
                                    <td style="text-align: center;"><?php echo sanitize_for_html($crianca['telefone']); ?></td>
                                    <td style="text-align: center;"><?php echo sanitize_for_html($crianca['idade']); ?></td>
                                    <td><?php echo sanitize_for_html($crianca['comum']); ?></td>
                                    <td style="text-align: center;" class="no-print">
                                        <button type="button" class="btn btn-sm btn-danger-linha" onclick="confirmarApagarLinha(<?php echo sanitize_for_html($crianca['id']); ?>, '<?php echo addslashes(sanitize_for_html($crianca['nomeCrianca'])); ?>')"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16"><path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0"/></svg></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="11" class="text-center py-4">Nenhuma criança cadastrada ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>" id="formApagarLinha" style="display: none;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="acao" value="apagar_linha">
            <input type="hidden" name="id_para_apagar" id="id_para_apagar_input">
        </form>

        <div class="modal fade" id="modalZerarArquivo" tabindex="-1" role="dialog" aria-labelledby="modalZerarArquivoLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>" id="formZerarArquivoInterno">
                        <?php echo csrf_field(); ?>
                        <div class="modal-header"><h5 class="modal-title" id="modalZerarArquivoLabel">Confirmar Zerar Arquivo</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                        <div class="modal-body">
                            <p><strong>ATENÇÃO:</strong> Esta ação é destrutiva e apagará todos os cadastros. O arquivo atual será salvo como backup (.bkp.1). Backups mais antigos (.bkp.2+) serão removidos. Se .bkp.1 for o único backup após esta operação, ele também será removido.</p>
                            <div class="form-group"><label for="admin_senha_zerar_modal">Senha Administrativa:</label><input type="password" class="form-control" id="admin_senha_zerar_modal" name="admin_senha" required></div>
                        </div>
                        <div class="modal-footer"><input type="hidden" name="zerar_arquivo_confirmado" value="1"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger">Confirmar Zerar</button></div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($exibirModalRecuperacao): ?>
            <div class="modal-backdrop fade show" id="modalRecuperarBackdrop"></div>
            <div class="modal fade show" id="modalRecuperarBackup" tabindex="-1" role="dialog" style="display: block;" aria-labelledby="modalRecuperarBackupLabel" aria-modal="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>">
                            <?php echo csrf_field(); ?>
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalRecuperarBackupLabel">Recuperar Backup</h5>
                                <button type="button" class="close" onclick="fecharModalRecuperacao()" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            </div>
                            <div class="modal-body">
                                <p>Selecione o arquivo de backup para restaurar. O arquivo atual será salvo como um novo backup (.bkp.1) antes da restauração.</p>
                                <?php if (!empty($backupsDisponiveis)): ?>
                                    <div class="form-group">
                                        <label for="arquivo_backup_selecionado">Arquivo de Backup:</label>
                                        <select class="form-control" id="arquivo_backup_selecionado" name="arquivo_backup_selecionado" required>
                                            <?php foreach ($backupsDisponiveis as $bkpFile): ?>
                                                <option value="<?php echo sanitize_for_html($bkpFile); ?>"><?php echo sanitize_for_html($bkpFile); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div id="backupPreviewContent" style="display: none;"></div>
                                    <div class="form-group mt-3">
                                        <label for="admin_senha_recuperar">Senha Administrativa:</label>
                                        <input type="password" class="form-control" id="admin_senha_recuperar" name="admin_senha" required>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Nenhum arquivo de backup encontrado para restauração.</p>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="confirmar_recuperacao" value="1">
                                <button type="button" class="btn btn-secondary" onclick="fecharModalRecuperacao()">Cancelar</button>
                                <?php if (!empty($backupsDisponiveis)): ?>
                                <button type="submit" class="btn btn-primary">Restaurar Backup</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Modal QZ Tray - Seleção de Impressora -->
        <div class="modal fade" id="modalQZTray" tabindex="-1" role="dialog" aria-labelledby="modalQZTrayLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title" id="modalQZTrayLabel">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-usb-plug-fill mr-2" viewBox="0 0 16 16"><path d="M6.5 6a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/><path d="M3 10.5a.5.5 0 0 1 .5-.5H4V9H2V7H1.5a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5H4V3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v1h2.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5H14v2h-1v1h.5a.5.5 0 0 1 0 1h-3l-1 1H7l-1-1H3.5a.5.5 0 0 1-.5-.5z"/></svg>
                            Impressora QZ Tray
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2">
                            <small>O QZ Tray permite imprimir diretamente via USB sem necessidade de servidor HTTP local. Certifique-se de que o <strong>QZ Tray</strong> está instalado e em execução.</small>
                        </div>

                        <div id="qzModalStatus" class="alert alert-secondary py-2 mb-3">
                            <span id="qzModalStatusDot" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#dc3545;margin-right:6px;vertical-align:middle;"></span>
                            <span id="qzModalStatusText">Desconectado</span>
                        </div>

                        <div class="form-group">
                            <label for="qzPrinterSelect"><strong>Impressora:</strong></label>
                            <div class="input-group">
                                <select class="form-control" id="qzPrinterSelect" disabled>
                                    <option value="">— Conecte ao QZ Tray primeiro —</option>
                                </select>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" id="btnRefreshQZPrinters" onclick="qzRefreshPrinters()" disabled title="Atualizar lista de impressoras">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted" id="qzPrinterSavedInfo"></small>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-success btn-sm" id="btnQZConectar" onclick="qzConectar()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-plug-fill mr-1" viewBox="0 0 16 16"><path d="M6 0a.5.5 0 0 1 .5.5V3h3V.5a.5.5 0 0 1 1 0V3h1a.5.5 0 0 1 .5.5v3A3.5 3.5 0 0 1 8.5 10c-.002.434-.01.845-.04 1.22-.041.514-.126 1.003-.317 1.424a2.083 2.083 0 0 1-.97 1.028C6.725 13.9 6.169 14 5.5 14c-.998 0-1.61.33-1.974.718A1.922 1.922 0 0 0 3 16H2c0-.616.232-1.367.797-1.968C3.374 13.42 4.261 13 5.5 13c.494 0 .989-.27 1.223-.658.17-.28.277-.67.293-1.342H6a3.5 3.5 0 0 1-3.5-3.5v-3A.5.5 0 0 1 3 4h1V.5A.5.5 0 0 1 4.5 0h1A.5.5 0 0 1 6 .5V3h-.5V.5A.5.5 0 0 1 6 0z"/></svg>
                                Conectar
                            </button>
                            <button type="button" class="btn btn-danger btn-sm d-none" id="btnQZDesconectar" onclick="qzDesconectar()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-plug mr-1" viewBox="0 0 16 16"><path d="M6 0a.5.5 0 0 1 .5.5V3h3V.5a.5.5 0 0 1 1 0V3h1a.5.5 0 0 1 .5.5v3A3.5 3.5 0 0 1 8.5 10c-.002.434-.01.845-.04 1.22-.041.514-.126 1.003-.317 1.424a2.083 2.083 0 0 1-.97 1.028C6.725 13.9 6.169 14 5.5 14c-.998 0-1.61.33-1.974.718A1.922 1.922 0 0 0 3 16H2c0-.616.232-1.367.797-1.968C3.374 13.42 4.261 13 5.5 13c.494 0 .989-.27 1.223-.658.17-.28.277-.67.293-1.342H6a3.5 3.5 0 0 1-3.5-3.5v-3A.5.5 0 0 1 3 4h1V.5A.5.5 0 0 1 4.5 0h1A.5.5 0 0 1 6 .5V3h-.5V.5A.5.5 0 0 1 6 0z"/></svg>
                                Desconectar
                            </button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="btnQZEsquecer" onclick="qzEsquecerImpressora()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-trash mr-1" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                                Esquecer Impressora
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Fechar</button>
                            <button type="button" class="btn btn-primary btn-sm" id="btnQZSalvarImpressora" onclick="qzSalvarImpressora()" disabled>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-check2 mr-1" viewBox="0 0 16 16"><path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/></svg>
                                Usar esta Impressora
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Configuração da Impressora -->
        <div class="modal fade" id="modalConfigImpressora" tabindex="-1" role="dialog" aria-labelledby="modalConfigImpressoraLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>" id="formConfigImpressora">
                        <?php echo csrf_field(); ?>
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title" id="modalConfigImpressoraLabel">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-printer mr-2" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                                Configuração da Impressora
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <strong>Atenção:</strong> Essas configurações serão salvas no arquivo config.ini. Altere apenas se souber o que está fazendo.
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="config_tampulseira">Tamanho da Pulseira (mm)</label>
                                        <input type="number" class="form-control" id="config_tampulseira" name="config_tampulseira" value="<?php echo TAMPULSEIRA; ?>" required>
                                        <small class="form-text text-muted">Tamanho total da pulseira em milímetros</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="config_dots">Dots por Milímetro</label>
                                        <input type="number" class="form-control" id="config_dots" name="config_dots" value="<?php echo DOTS; ?>" required>
                                        <small class="form-text text-muted">Resolução da impressora (normalmente 8)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="config_fecho">Tamanho do Fecho (mm)</label>
                                        <input type="number" class="form-control" id="config_fecho" name="config_fecho" value="<?php echo FECHO; ?>" required>
                                        <small class="form-text text-muted">Tamanho do fecho da pulseira</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="config_fechoini">Posição Inicial do Fecho</label>
                                        <input type="number" class="form-control" id="config_fechoini" name="config_fechoini" value="<?php echo FECHOINI; ?>" required>
                                        <small class="form-text text-muted">Posição inicial (normalmente 1)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="config_printer_name">Nome da Impressora</label>
                                <input type="text" class="form-control" id="config_printer_name" name="config_printer_name" value="<?php echo PRINTER_NAME; ?>" required>
                                <small class="form-text text-muted">Nome do dispositivo de impressão (ex: ZDesigner 105SL, ZDesigner GK420d, etc.)</small>
                            </div>

                            <div class="form-group">
                                <label for="config_palavra_contador_comum">Palavra-chave para Contador "Comum"</label>
                                <input type="text" class="form-control" id="config_palavra_contador_comum" name="config_palavra_contador_comum" value="<?php echo PALAVRA_CONTADOR_COMUM; ?>" required>
                                <small class="form-text text-muted">Palavra base para contagem (ex: "bonfim", "jardim", etc.). O sistema gera automaticamente variações similares para tolerância a erros.</small>
                            </div>

                            <div class="form-group">
                                <label for="config_lista_palavras_contador_comum">Palavras Adicionais para Contador "Comum"</label>
                                <textarea class="form-control" id="config_lista_palavras_contador_comum" name="config_lista_palavras_contador_comum" rows="3"><?php echo LISTA_PALAVRAS_CONTADOR_COMUM; ?></textarea>
                                <small class="form-text text-muted">Lista de palavras adicionais separadas por vírgula (ex: "parque, parqui, par que, jardim, capela"). Estas palavras serão verificadas EXATAMENTE como digitadas, sem gerar variações automáticas.</small>
                            </div>

                            <div class="form-group">
                                <label for="config_url_impressora">URL da Impressora</label>
                                <input type="text" class="form-control" id="config_url_impressora" name="config_url_impressora" value="<?php echo URL_IMPRESSORA; ?>" required>
                                <small class="form-text text-muted">Ex: http://127.0.0.1:9100/write ou http://IP_DA_IMPRESSORA:9100/write</small>
                            </div>

                            <div class="form-group">
                                <label for="config_largura_pulseira">Largura da Pulseira (dots)</label>
                                <input type="number" class="form-control" id="config_largura_pulseira" name="config_largura_pulseira" value="<?php echo $config['IMPRESSORA_ZPL']['LARGURA_PULSEIRA'] ?? 192; ?>" required>
                                <small class="form-text text-muted">Largura em dots (24mm = 192 dots)</small>
                            </div>

                            <div class="form-group">
                                <label for="admin_senha_config">Senha Administrativa</label>
                                <input type="password" class="form-control" id="admin_senha_config" name="admin_senha" required>
                                <small class="form-text text-muted">Digite a senha administrativa para confirmar as alterações</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" name="salvar_config_impressora">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save mr-1" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v7.293l2.646-2.647a.5.5 0 0 1 .708.708l-3.5 3.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L7.5 9.293V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/></svg>
                                Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Debug ZPL -->
        <div class="modal fade" id="modalDebugZPL" tabindex="-1" role="dialog" aria-labelledby="modalDebugZPLLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title" id="modalDebugZPLLabel">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-bug-fill mr-2" viewBox="0 0 16 16"><path d="M4.978.855a.5.5 0 1 0-.956.29L4.3 2.003a4.98 4.98 0 0 0-2.106 1.098L.75 1.655a.5.5 0 0 0-.707.707l1.446 1.446a4.98 4.98 0 0 0-.967 2.197l-1.02.156a.5.5 0 0 0 .153.988l1.028-.157a5.01 5.01 0 0 0 .848 1.653L.085 9.992a.5.5 0 0 0 .707.707l1.446-1.446a4.98 4.98 0 0 0 2.106 1.098l-.278 1.858a.5.5 0 1 0 .956.29l.278-1.858a5.017 5.017 0 0 0 2.4 0l.278 1.858a.5.5 0 1 0 .956-.29l-.278-1.858a4.98 4.98 0 0 0 2.106-1.098l1.446 1.446a.5.5 0 0 0 .707-.707l-1.446-1.446a4.98 4.98 0 0 0 .848-1.653l1.028.157a.5.5 0 1 0 .153-.988l-1.02-.156a4.98 4.98 0 0 0-.967-2.197l1.446-1.446a.5.5 0 0 0-.707-.707l-1.446 1.446a4.98 4.98 0 0 0-2.106-1.098l.278-1.858a.5.5 0 1 0-.956-.29l-.278 1.858a5.017 5.017 0 0 0-2.4 0l-.278-1.858z"/><path d="M7.8 5.5a.8.8 0 1 1-1.6 0 .8.8 0 0 1 1.6 0z"/><path d="M9.8 5.5a.8.8 0 1 1-1.6 0 .8.8 0 0 1 1.6 0z"/></svg>
                            Modo Debug - Comando ZPL
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <strong>Modo Debug Ativo!</strong> Você pode editar o código ZPL antes de enviar para a impressora.
                        </div>

                        <div class="form-group">
                            <label for="debug_info">Informações:</label>
                            <div id="debug_info" class="p-2 bg-light border rounded">
                                <strong>Criança:</strong> <span id="debug_nome_crianca"></span><br>
                                <strong>Código:</strong> <span id="debug_codigo"></span><br>
                                <strong>Tipo:</strong> <span id="debug_tipo_pulseira"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="debug_zpl_code">Código ZPL:</label>
                            <textarea class="form-control" id="debug_zpl_code" rows="15" style="font-family: 'Courier New', monospace; font-size: 12px;"></textarea>
                            <small class="form-text text-muted">Você pode editar o código ZPL diretamente aqui antes de enviar</small>
                        </div>

                        <div class="form-group">
                            <label for="debug_url_impressora">URL da Impressora (fallback HTTP):</label>
                            <input type="text" class="form-control" id="debug_url_impressora" value="<?php echo URL_IMPRESSORA; ?>">
                        </div>

                        <div class="alert py-2 mb-2" id="debug_rota_alert" style="font-size:0.85rem;">
                            <strong>Rota de impressão:</strong> <span id="debug_rota_text">verificando...</span>
                        </div>

                        <div class="form-group">
                            <label>Comando equivalente:</label>
                            <textarea class="form-control" id="debug_curl_command" rows="5" readonly style="font-family: 'Courier New', monospace; font-size: 11px; background-color: #f8f9fa;"></textarea>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="copiarCurl()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard" viewBox="0 0 16 16"><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/></svg>
                                Copiar cURL
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" id="btnEnviarDebug" onclick="enviarZPLDebug()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-send-fill mr-1" viewBox="0 0 16 16"><path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083l6-15Zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471-.47 1.178Z"/></svg>
                            <span id="debug_btn_label">Enviar para Impressora</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rodapé com versão do sistema -->
        <div class="text-center mt-4 mb-2" style="font-size: 9px; color: #b0b0b0; opacity: 0.6;">
            v<?php echo VERSAO_SISTEMA; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qz-tray@2/qz-tray.js"></script>
    <script>
        var csrfToken = <?php echo json_encode(csrf_token()); ?>;
        const NUM_LINHAS_FORM_CADASTRO = <?php echo NUM_LINHAS_FORMULARIO_CADASTRO; ?>;
        const NUM_CAMPOS_POR_LINHA_CADASTRO = 5; 

        function focarPrimeiroCampoCadastro() {
            $('#input_0_0').focus();
        }

        function limparLinhaCadastro(linha) {
            $('#input_' + linha + '_0').val(''); 
            $('#input_' + linha + '_1').val(''); 
            $('#input_' + linha + '_2').val(''); 
            $('#input_' + linha + '_3').val('').trigger('input'); 
            $('#input_' + linha + '_4').val(''); 
        }

        $(document).ready(function(){
            $('.telefone-mask').mask('(00) 00000-0000');

            const portariaInputCadastro = $('#portaria_cadastro');
            const storedPortaria = localStorage.getItem('ultimaPortariaCadastro');
            if (storedPortaria) {
                portariaInputCadastro.val(storedPortaria);
            }

            portariaInputCadastro.on('input', function() {
                let value = $(this).val().toUpperCase();
                if (value.length > 1) {
                    value = value.substring(0, 1);
                }
                $(this).val(value);
                if (value.match(/^[A-Z]$/)) {
                    localStorage.setItem('ultimaPortariaCadastro', value);
                } else if (value === '') {
                    localStorage.removeItem('ultimaPortariaCadastro');
                }
            });

            <?php if ($focarPrimeiroCampoAposCadastro): ?>
                focarPrimeiroCampoCadastro();
                $('#formNovoCadastro .cadastro-input').val(''); 
            <?php elseif ($focarAposAcao): ?>
                focarPrimeiroCampoCadastro();
            <?php elseif (!empty($mensagemErro)): ?>
            <?php endif; ?>


            $('.cadastro-input').on('keydown', function(e) {
                const key = e.key;
                const target = e.target;
                if (key === 'Enter' || key === 'Tab') {
                    e.preventDefault();
                    const currentLinha = parseInt($(target).data('linha'));
                    const currentCol = parseInt($(target).data('col'));
                    let nextLinha = currentLinha;
                    let nextCol = currentCol;

                    if (currentCol < NUM_CAMPOS_POR_LINHA_CADASTRO - 1) {
                        nextCol++;
                    } else {
                        if (currentLinha < NUM_LINHAS_FORM_CADASTRO - 1) {
                            nextLinha++;
                            nextCol = 0;
                        } else {
                            $('#portaria_cadastro').focus();
                            return;
                        }
                    }
                    $('#input_' + nextLinha + '_' + nextCol).focus();
                }
            });
            
            $('#input_' + (NUM_LINHAS_FORM_CADASTRO - 1) + '_' + (NUM_CAMPOS_POR_LINHA_CADASTRO - 1)).on('keydown', function(e) {
                if (e.key === 'Tab' && !e.shiftKey) {
                    e.preventDefault();
                    $('#portaria_cadastro').focus();
                }
            });

            $('#portaria_cadastro').on('keydown', function(e) {
                if (e.key === 'Enter' || (e.key === 'Tab' && !e.shiftKey)) {
                    e.preventDefault();
                    if ($('#btnLimparCadastro').is(':visible') && !$('#btnLimparCadastro').is(':disabled')) { 
                        $('#btnLimparCadastro').focus();
                    } else {
                        $('#btnCadastrar').focus();
                    }
                }
            });


            $('#selecionarTodos').change(function() {
                $('.checkbox-crianca:visible').prop('checked', $(this).prop('checked'));
            });

            $(document).on('change', '.checkbox-crianca', function() {
                if (!$(this).prop('checked')) {
                    $('#selecionarTodos').prop('checked', false);
                } else {
                    var todosMarcadosVisiveis = true;
                    $('.checkbox-crianca:visible').each(function(){
                        if(!$(this).prop('checked')){
                            todosMarcadosVisiveis = false; return false; 
                        }
                    });
                    $('#selecionarTodos').prop('checked', todosMarcadosVisiveis);
                }
            });

            window.setTimeout(function() { $(".alert-success, .alert-danger").not('.alert-login').fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); }); }, 7000);

            $('#arquivo_backup_selecionado').change(function() {
                var selectedFile = $(this).val();
                var previewDiv = $('#backupPreviewContent');
                if (selectedFile) {
                    previewDiv.html('Carregando preview...').show();
                    var previewUrl = window.location.pathname + '?acao=preview_backup&arquivo=' + encodeURIComponent(selectedFile);
                    fetch(previewUrl)
                        .then(response => {
                            if (!response.ok) {
                                return response.text().then(text => { throw new Error('Erro: ' + response.status + ' ' + text); });
                            }
                            return response.text();
                        })
                        .then(data => {
                            previewDiv.text(data ? data : 'Preview não disponível ou arquivo vazio.');
                        })
                        .catch(error => {
                            console.error('Erro ao buscar preview:', error);
                            previewDiv.text('Erro ao carregar preview: ' + error.message).show();
                        });
                } else {
                    previewDiv.hide().empty();
                }
            });
            <?php if ($exibirModalRecuperacao && !empty($backupsDisponiveis)): ?>
                if ($('#arquivo_backup_selecionado').val()) { 
                    $('#arquivo_backup_selecionado').trigger('change'); 
                }
            <?php endif; ?>

            const filtroPortariaSelect = $('#filtroPortaria');
            const todasAsLinhasDaTabela = $('#lista-criancas tr');
            const localStorageKeyFiltroPortaria = 'filtroPortariaSelecionado';
            let portariasUnicas = new Set();

            todasAsLinhasDaTabela.each(function() {
                const portariaDaLinha = $(this).data('portaria')?.toString().trim().toUpperCase();
                if (portariaDaLinha) {
                    portariasUnicas.add(portariaDaLinha);
                }
            });

            Array.from(portariasUnicas).sort().forEach(p => {
                filtroPortariaSelect.append(new Option(p, p));
            });

            function aplicarFiltroPortaria() {
                const portariasSelecionadas = filtroPortariaSelect.val();
                localStorage.setItem(localStorageKeyFiltroPortaria, JSON.stringify(portariasSelecionadas));

                if (!portariasSelecionadas || portariasSelecionadas.length === 0) {
                    todasAsLinhasDaTabela.show();
                    $('#selecionarTodos').prop('disabled', todasAsLinhasDaTabela.length === 0);
                } else {
                    let algumaLinhaVisivel = false;
                    todasAsLinhasDaTabela.each(function() {
                        const portariaDaLinha = $(this).data('portaria')?.toString().trim().toUpperCase();
                        if (portariasSelecionadas.includes(portariaDaLinha)) {
                            $(this).show();
                            algumaLinhaVisivel = true;
                        } else {
                            $(this).hide();
                            $(this).find('.checkbox-crianca').prop('checked', false); 
                        }
                    });
                    $('#selecionarTodos').prop('disabled', !algumaLinhaVisivel);
                }
                var todosMarcadosVisiveis = true;
                var algumVisivel = false;
                $('.checkbox-crianca:visible').each(function(){
                    algumVisivel = true;
                    if(!$(this).prop('checked')){
                        todosMarcadosVisiveis = false; return false;
                    }
                });
                if (!algumVisivel) todosMarcadosVisiveis = false; 
                $('#selecionarTodos').prop('checked', todosMarcadosVisiveis);
            }

            const filtroSalvo = localStorage.getItem(localStorageKeyFiltroPortaria);
            if (filtroSalvo) {
                try {
                    const portariasSalvas = JSON.parse(filtroSalvo);
                    if (Array.isArray(portariasSalvas)) {
                        filtroPortariaSelect.val(portariasSalvas);
                    }
                } catch (e) {
                    console.error("Erro ao carregar filtro de portaria do localStorage:", e);
                    localStorage.removeItem(localStorageKeyFiltroPortaria); 
                }
            }
            aplicarFiltroPortaria(); 

            filtroPortariaSelect.on('change', aplicarFiltroPortaria);

            $('#limparFiltroPortaria').on('click', function() {
                filtroPortariaSelect.val(null).trigger('change'); 
            });

            $('.btn-copiar-dados').on('click', function() {
                const targetLinha = parseInt($(this).data('target-linha'));
                const responsavelLinha0 = $('#input_0_1').val();
                const telefoneLinha0 = $('#input_0_3').val();
                const comumLinha0 = $('#input_0_4').val();

                $('#input_' + targetLinha + '_1').val(responsavelLinha0);
                $('#input_' + targetLinha + '_3').val(telefoneLinha0).trigger('input'); 
                $('#input_' + targetLinha + '_4').val(comumLinha0);
            });

            $('#btnLimparCadastro').on('click', function() {
                $('#formNovoCadastro .cadastro-input').val('');
                focarPrimeiroCampoCadastro();
            });

            $('#btnImprimirLista').on('click', function() {
                var printWindow = window.open('', '_blank', 'height=600,width=800');
                printWindow.document.write('<html><head><title>Lista de Crianças</title>');
                printWindow.document.write('<style>');
                printWindow.document.write('body { font-family: Arial, sans-serif; font-size: 10pt; margin: 20px;}');
                printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }');
                printWindow.document.write('th, td { border: 1px solid #ccc; padding: 4px; text-align: left; vertical-align: top; }');
                printWindow.document.write('th { background-color: #f0f0f0; font-weight: bold; }');
                printWindow.document.write('h2 { text-align: center; margin-bottom: 15px; }');
                printWindow.document.write('</style></head><body>');
                printWindow.document.write('<h2>Lista de Crianças Cadastradas</h2>');
                printWindow.document.write('<table>');

                var $thead = $('.tabela-scrollable thead').clone();
                $thead.find('.no-print').remove();
                printWindow.document.write('<thead>' + $thead.html() + '</thead>');

                printWindow.document.write('<tbody>');
                $('#lista-criancas tr').each(function() {
                    if ($(this).is(':visible')) {
                        var $row = $(this).clone();
                        $row.find('.no-print').remove();

                        var $statusIcon = $row.find('.status-icon');
                        if ($statusIcon.find('svg').length > 0) {
                            $statusIcon.parent().html('Sim');
                        } else {
                            $statusIcon.parent().html('Não');
                        }
                        printWindow.document.write('<tr>' + $row.html() + '</tr>');
                    }
                });
                printWindow.document.write('</tbody></table></body></html>');
                printWindow.document.close();
                printWindow.focus();
                setTimeout(function(){ printWindow.print(); }, 500);
            });

            // Função auxiliar para extrair dados visíveis da tabela
            function extrairDadosTabelaVisivel() {
                var headers = ['Impresso', 'Portaria', 'Codigo', 'Cod Resp', 'Nome da Crianca', 'Nome do Responsavel', 'Telefone', 'Idade', 'Comum'];
                var rows = [];
                $('#lista-criancas tr').each(function() {
                    if ($(this).is(':visible')) {
                        var $cells = $(this).find('td');
                        if ($cells.length === 0) return;
                        var impresso = $cells.eq(1).find('svg').length > 0 ? 'Sim' : 'Nao';
                        var row = [
                            impresso,
                            $cells.eq(2).text().trim(),
                            $cells.eq(3).text().trim(),
                            $cells.eq(4).text().trim(),
                            $cells.eq(5).text().trim(),
                            $cells.eq(6).text().trim(),
                            $cells.eq(7).text().trim(),
                            $cells.eq(8).text().trim(),
                            $cells.eq(9).text().trim()
                        ];
                        rows.push(row);
                    }
                });
                return { headers: headers, rows: rows };
            }

            // Baixar CSV
            $('#btnBaixarCSV').on('click', function() {
                var dados = extrairDadosTabelaVisivel();
                var csvContent = '\uFEFF'; // BOM UTF-8 para Excel
                csvContent += dados.headers.join(';') + '\n';
                dados.rows.forEach(function(row) {
                    var linha = row.map(function(cell) {
                        var escaped = cell.replace(/"/g, '""');
                        return '"' + escaped + '"';
                    });
                    csvContent += linha.join(';') + '\n';
                });

                var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                var dataAtual = new Date().toISOString().slice(0, 10);
                link.download = 'lista_criancas_' + dataAtual + '.csv';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
            });

            // Baixar XLS
            $('#btnBaixarXLS').on('click', function() {
                var dados = extrairDadosTabelaVisivel();
                var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                html += '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
                html += '<x:Name>Lista</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
                html += '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>';
                html += '<table border="1">';
                html += '<thead><tr>';
                dados.headers.forEach(function(h) {
                    html += '<th style="background-color:#007bff;color:#fff;font-weight:bold;padding:5px;">' + h + '</th>';
                });
                html += '</tr></thead><tbody>';
                dados.rows.forEach(function(row) {
                    html += '<tr>';
                    row.forEach(function(cell) {
                        html += '<td style="padding:3px;">' + $('<span>').text(cell).html() + '</td>';
                    });
                    html += '</tr>';
                });
                html += '</tbody></table></body></html>';

                var blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                var dataAtual = new Date().toISOString().slice(0, 10);
                link.download = 'lista_criancas_' + dataAtual + '.xls';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
            });


            // Inicializar modos
            atualizarEstadoModoDebug();
            atualizarEstadoModoTeste();

        });

        // ============ FUNÇÕES DE CONFIGURAÇÃO E DEBUG ============

        function abrirModalConfigImpressora() {
            $('#modalConfigImpressora').modal('show');
        }

        function toggleModoDebugImpressao() {
            const modoDebugAtual = localStorage.getItem('modoDebugImpressao') === 'true';
            const novoEstado = !modoDebugAtual;
            localStorage.setItem('modoDebugImpressao', novoEstado);
            atualizarEstadoModoDebug();

            if (novoEstado) {
                alert('Modo Debug de Impressão ATIVADO!\n\nAgora, ao clicar em "Imprimir", uma janela será aberta mostrando o código ZPL antes de enviar para a impressora.');
            } else {
                alert('Modo Debug de Impressão DESATIVADO.\n\nAs impressões serão enviadas diretamente para a impressora.');
            }
        }

        function atualizarEstadoModoDebug() {
            const modoDebugAtivo = localStorage.getItem('modoDebugImpressao') === 'true';
            const label = $('#labelDebugMode');
            const btn = $('#btnToggleDebug');

            if (modoDebugAtivo) {
                label.text('Modo Debug: ON').css('font-weight', 'bold').css('color', '#ffc107');
                btn.css('background-color', '#fff3cd');
            } else {
                label.text('Modo Debug: OFF').css('font-weight', 'normal').css('color', '');
                btn.css('background-color', '');
            }
        }

        // ============ MODO TESTAR IMPRESSÃO ============

        function toggleModoTesteImpressao() {
            const modoAtual = localStorage.getItem('modoTesteImpressao') === 'true';
            const novoEstado = !modoAtual;
            localStorage.setItem('modoTesteImpressao', novoEstado);
            atualizarEstadoModoTeste();

            if (novoEstado) {
                alert('Modo Testar Impressão ATIVADO!\n\nAo imprimir, será enviado apenas o nome da criança com o ZPL simplificado.\nEtiqueta do responsável NÃO será impressa.');
            } else {
                alert('Modo Testar Impressão DESATIVADO.\nO sistema voltará a imprimir normalmente.');
            }
        }

        function atualizarEstadoModoTeste() {
            const ativo = localStorage.getItem('modoTesteImpressao') === 'true';
            const label = $('#labelTesteMode');
            const btn   = $('#btnToggleTeste');
            const painel = $('#painelTesteImpressao');

            if (ativo) {
                label.text('Testar Impressão: ON').css('font-weight', 'bold').css('color', '#e65100');
                btn.css('background-color', '#ffe0b2');
                painel.removeClass('d-none');
                // Restaurar valores salvos nos campos
                $('#testeX').val(localStorage.getItem('testeX') || '140');
                $('#testeY').val(localStorage.getItem('testeY') || '30');
                $('#testeFontSize').val(localStorage.getItem('testeFontSize') || '20');
            } else {
                label.text('Testar Impressão: OFF').css('font-weight', 'normal').css('color', '');
                btn.css('background-color', '');
                painel.addClass('d-none');
            }
        }

        function salvarConfigTeste() {
            localStorage.setItem('testeX',        $('#testeX').val()        || '140');
            localStorage.setItem('testeY',        $('#testeY').val()        || '30');
            localStorage.setItem('testeFontSize', $('#testeFontSize').val() || '20');
            const status = $('#testeConfigStatus');
            status.removeClass('d-none');
            setTimeout(function() { status.addClass('d-none'); }, 2000);
        }

        // Variável global para armazenar os dados de debug
        window.debugPrintQueue = [];

        function abrirModalDebugZPL(zplCode, info) {
            $('#debug_zpl_code').val(zplCode);
            $('#debug_nome_crianca').text(info.nomeCrianca || 'N/A');
            $('#debug_codigo').text(info.codigo || 'N/A');
            $('#debug_tipo_pulseira').text(info.tipo || 'Criança');
            $('#debug_url_impressora').val(info.urlImpressora || '<?php echo URL_IMPRESSORA; ?>');

            atualizarCurlCommand();
            $('#modalDebugZPL').modal('show');
        }

        function atualizarCurlCommand() {
            const zpl = $('#debug_zpl_code').val();
            const url = $('#debug_url_impressora').val();
            const printerName = localStorage.getItem('qzPrinterSelecionada') || '';
            const viaQZ = printerName && qzConnected && qz.websocket.isActive();

            const payload = {
                "device": {
                    "name": "<?php echo PRINTER_NAME; ?>",
                    "uid": "<?php echo PRINTER_NAME; ?>",
                    "connection": "driver",
                    "deviceType": "printer",
                    "version": 2,
                    "provider": "com.zebra.ds.webdriver.desktop.provider.DefaultDeviceProvider",
                    "manufacturer": "Zebra Technologies"
                },
                "data": zpl
            };

            let commandText;
            if (viaQZ) {
                commandText  = '// Impressão via QZ Tray (impressora: "' + printerName + '")\n';
                commandText += '// ZPL convertido para hex UTF-8 e enviado direto ao driver.\n';
                commandText += '// Equivalente JS:\n';
                commandText += 'var utf8 = new TextEncoder().encode(zpl);\n';
                commandText += 'var hex = [...utf8].map(b => b.toString(16).padStart(2,"0")).join("");\n';
                commandText += 'qz.print(\n';
                commandText += '  qz.configs.create("' + printerName + '"),\n';
                commandText += '  [{ type: "raw", format: "hex", data: hex }]\n';
                commandText += ');';

                $('#debug_rota_alert').removeClass('alert-secondary alert-info').addClass('alert-success');
                $('#debug_rota_text').html('<strong>QZ Tray</strong> — impressora: <em>' + printerName + '</em>');
                $('#debug_btn_label').text('Enviar via QZ Tray');
            } else {
                const curlCmd = `curl -X POST '${url}' \\\n  -H 'Content-Type: application/json' \\\n  -d '${JSON.stringify(payload).replace(/'/g, "'\\''")}'`;
                commandText = curlCmd;

                $('#debug_rota_alert').removeClass('alert-success alert-info').addClass('alert-secondary');
                $('#debug_rota_text').html('<strong>HTTP</strong> — ' + url + (printerName ? ' <em>(QZ Tray desconectado — reconecte para usar QZ Tray)</em>' : ''));
                $('#debug_btn_label').text('Enviar via HTTP');
            }

            $('#debug_curl_command').val(commandText);
        }

        $('#debug_zpl_code, #debug_url_impressora').on('input', function() {
            atualizarCurlCommand();
        });

        function copiarCurl() {
            const curlText = $('#debug_curl_command').val();
            navigator.clipboard.writeText(curlText).then(function() {
                alert('Comando cURL copiado para a área de transferência!');
            }, function() {
                // Fallback para navegadores mais antigos
                const textarea = document.createElement('textarea');
                textarea.value = curlText;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('Comando cURL copiado para a área de transferência!');
            });
        }

        async function enviarZPLDebug() {
            const zpl = $('#debug_zpl_code').val();
            const url = $('#debug_url_impressora').val();

            if (!zpl.trim()) {
                alert('O código ZPL está vazio!');
                return;
            }

            const payload = {
                "device": {
                    "name": "<?php echo PRINTER_NAME; ?>",
                    "uid": "<?php echo PRINTER_NAME; ?>",
                    "connection": "driver",
                    "deviceType": "printer",
                    "version": 2,
                    "provider": "com.zebra.ds.webdriver.desktop.provider.DefaultDeviceProvider",
                    "manufacturer": "Zebra Technologies"
                },
                "data": zpl
            };

            $('#modalDebugZPL').modal('hide');

            try {
                const response = await _ebiPrint(url, payload);
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error('Falha na impressão: ' + (response.status || '') + ' ' + text);
                }
                const result = await response.text();
                console.log('Impressão debug enviada com sucesso:', result);
                alert('✓ ZPL enviado com sucesso!\n\nResposta: ' + result);
            } catch (error) {
                console.error('Erro ao enviar impressão debug:', error);
                alert('✗ Erro ao enviar:\n\n' + error.message);
            }
        }

        function abrirModalZerarArquivo() {
            $('#modalZerarArquivo').modal('show');
        }

        function fecharModalRecuperacao() {
            $('#modalRecuperarBackup').removeClass('show').hide();
            $('#modalRecuperarBackdrop').removeClass('show').hide();
            $('body').removeClass('modal-open'); 
            $('.modal-backdrop').remove(); 

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>';
            var hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = 'limpar_flag_modal_recuperacao';
            hiddenField.value = '1';
            form.appendChild(hiddenField);
            var csrfField = document.createElement('input');
            csrfField.type = 'hidden';
            csrfField.name = 'csrf_token';
            csrfField.value = csrfToken;
            form.appendChild(csrfField);
            document.body.appendChild(form);
            form.submit();
        }

        function confirmarApagarLinha(id, nomeCrianca) {
            if (confirm("Tem certeza que deseja apagar o cadastro de '" + nomeCrianca + "' (ID: " + id + ")?\nEsta ação não pode ser desfeita. Um backup do arquivo atual será criado.")) {
                document.getElementById('id_para_apagar_input').value = id;
                document.getElementById('formApagarLinha').submit();
            }
        }

        // ============ QZ TRAY ============

        var qzConnected = false;
        var qzReadyPromise = null; // Promise que resolve quando auto-connect termina (sucesso ou falha)

        // Segurança: certificado e assinatura (relativos ao diretório do PHP)
        qz.security.setCertificatePromise(function(resolve, reject) {
            fetch('assets/signing/digital-certificate.txt', {
                cache: 'no-store',
                headers: { 'Content-Type': 'text/plain' }
            }).then(function(data) {
                data.ok ? resolve(data.text()) : reject(data.text());
            }).catch(reject);
        });

        qz.security.setSignatureAlgorithm('SHA512');

        qz.security.setSignaturePromise(function(toSign) {
            return function(resolve, reject) {
                fetch('assets/signing/sign-message.php?request=' + toSign, {
                    cache: 'no-store',
                    headers: { 'Content-Type': 'text/plain' }
                }).then(function(data) {
                    data.ok ? resolve(data.text()) : reject(data.text());
                }).catch(reject);
            };
        });

        function qzAtualizarBadge() {
            var dot = document.getElementById('qzStatusDot');
            var txt = document.getElementById('qzStatusText');
            var modalDot = document.getElementById('qzModalStatusDot');
            var modalTxt = document.getElementById('qzModalStatusText');
            var badge = document.getElementById('qzStatusBadge');
            var btnConectar = document.getElementById('btnQZConectar');
            var btnDesconectar = document.getElementById('btnQZDesconectar');
            var btnRefresh = document.getElementById('btnRefreshQZPrinters');
            var printerSel = document.getElementById('qzPrinterSelect');
            var btnSalvar = document.getElementById('btnQZSalvarImpressora');

            if (qzConnected) {
                dot.style.background = '#28a745';
                txt.textContent = 'QZ: Conectado';
                badge.className = 'badge badge-success mr-2';
                if (modalDot) modalDot.style.background = '#28a745';
                if (modalTxt) modalTxt.textContent = 'Conectado ao QZ Tray';
                if (document.getElementById('qzModalStatus')) document.getElementById('qzModalStatus').className = 'alert alert-success py-2 mb-3';
                if (btnConectar) { btnConectar.classList.add('d-none'); }
                if (btnDesconectar) { btnDesconectar.classList.remove('d-none'); }
                if (btnRefresh) btnRefresh.disabled = false;
                if (printerSel) printerSel.disabled = false;
                if (btnSalvar) btnSalvar.disabled = false;

                var savedPrinter = localStorage.getItem('qzPrinterSelecionada') || '';
                var info = document.getElementById('qzPrinterSavedInfo');
                if (info) {
                    info.textContent = savedPrinter
                        ? 'Impressora ativa: ' + savedPrinter
                        : 'Nenhuma impressora QZ Tray selecionada — impressão usará URL HTTP.';
                }
            } else {
                var savedPrinterDisc = localStorage.getItem('qzPrinterSelecionada') || '';
                dot.style.background = savedPrinterDisc ? '#ffc107' : '#dc3545';
                txt.textContent = savedPrinterDisc ? 'QZ: ' + savedPrinterDisc + ' (offline)' : 'QZ: Desconectado';
                badge.className = savedPrinterDisc ? 'badge badge-warning mr-2' : 'badge badge-secondary mr-2';
                if (modalDot) modalDot.style.background = '#dc3545';
                if (modalTxt) modalTxt.textContent = savedPrinterDisc ? 'Desconectado (impressora salva: ' + savedPrinterDisc + ')' : 'Desconectado';
                if (document.getElementById('qzModalStatus')) document.getElementById('qzModalStatus').className = 'alert alert-secondary py-2 mb-3';
                if (btnConectar) { btnConectar.classList.remove('d-none'); }
                if (btnDesconectar) { btnDesconectar.classList.add('d-none'); }
                if (btnRefresh) btnRefresh.disabled = true;
                if (printerSel) { printerSel.disabled = true; printerSel.innerHTML = '<option value="">— Conecte ao QZ Tray primeiro —</option>'; }
                if (btnSalvar) btnSalvar.disabled = true;
                var info = document.getElementById('qzPrinterSavedInfo');
                if (info) {
                    info.textContent = savedPrinterDisc
                        ? 'Impressora salva: ' + savedPrinterDisc + ' — conecte para usar QZ Tray.'
                        : 'Nenhuma impressora QZ Tray configurada.';
                }
            }
        }

        function abrirModalQZTray() {
            qzAtualizarBadge();
            $('#modalQZTray').modal('show');
        }

        async function qzConectar() {
            try {
                var btnConectar = document.getElementById('btnQZConectar');
                if (btnConectar) { btnConectar.disabled = true; btnConectar.textContent = 'Conectando…'; }
                await qz.websocket.connect({
                    host: 'localhost',
                    port: { secure: [8181], insecure: [8182] },
                    usingSecure: false,
                    retries: 3,
                    delay: 1
                });
                qzConnected = true;
                qzAtualizarBadge();
                await qzRefreshPrinters();
            } catch (e) {
                qzConnected = false;
                qzAtualizarBadge();
                alert('Erro ao conectar ao QZ Tray.\nVerifique se o QZ Tray está instalado e em execução.\n\nDetalhes: ' + e.message);
            } finally {
                var btnConectar = document.getElementById('btnQZConectar');
                if (btnConectar) { btnConectar.disabled = false; btnConectar.textContent = 'Conectar'; }
            }
        }

        async function qzDesconectar() {
            try {
                await qz.websocket.disconnect();
            } catch (e) { /* ignora */ }
            qzConnected = false;
            // NÃO remove a impressora salva — ela será reutilizada no próximo acesso/refresh
            qzAtualizarBadge();
        }

        function qzEsquecerImpressora() {
            if (confirm('Remover a impressora QZ Tray salva?\nAs próximas impressões voltarão a usar HTTP.')) {
                localStorage.removeItem('qzPrinterSelecionada');
                qzAtualizarBadge();
            }
        }

        async function qzRefreshPrinters() {
            if (!qzConnected) { alert('Conecte ao QZ Tray primeiro.'); return; }
            try {
                var list = await qz.printers.find();
                var sel = document.getElementById('qzPrinterSelect');
                var savedPrinter = localStorage.getItem('qzPrinterSelecionada') || '';
                sel.innerHTML = '<option value="">— Selecione a impressora —</option>';
                list.forEach(function(p) {
                    var o = document.createElement('option');
                    o.value = p; o.textContent = p;
                    if (p === savedPrinter) o.selected = true;
                    sel.appendChild(o);
                });
            } catch (e) {
                alert('Erro ao listar impressoras: ' + e.message);
            }
        }

        function qzSalvarImpressora() {
            var sel = document.getElementById('qzPrinterSelect');
            var printerName = sel ? sel.value : '';
            if (!printerName) {
                alert('Selecione uma impressora da lista.');
                return;
            }
            localStorage.setItem('qzPrinterSelecionada', printerName);
            qzAtualizarBadge();
            $('#modalQZTray').modal('hide');
            alert('Impressora QZ Tray configurada: ' + printerName + '\n\nAs próximas impressões usarão QZ Tray diretamente.');
        }

        /**
         * Função central de impressão.
         * Aguarda o auto-connect terminar (qzReadyPromise) antes de decidir a rota.
         * Se QZ Tray estiver conectado e impressora salva → imprime via QZ Tray.
         * Caso contrário → fallback HTTP para URL_IMPRESSORA.
         *
         * ENCODING: ZPL usa ^CI28 (UTF-8). Convertemos para hex byte-a-byte via
         * TextEncoder para que os bytes UTF-8 cheguem intactos à impressora,
         * igual ao sendRawBytes() do calibrar.php (format:'hex').
         */
        async function _ebiPrint(url, payload) {
            // Aguarda o auto-connect terminar (nunca rejeita — catch interno)
            if (qzReadyPromise) {
                try { await qzReadyPromise; } catch (e) { /* falha silenciosa */ }
            }

            var printerName = localStorage.getItem('qzPrinterSelecionada') || '';
            console.log('[_ebiPrint] printerName=' + printerName + ' qzConnected=' + qzConnected);

            if (printerName && qzConnected && qz.websocket.isActive()) {
                try {
                    // Converte ZPL para hex UTF-8 (igual a sendRawBytes em calibrar.php)
                    var utf8Bytes = new TextEncoder().encode(payload.data);
                    var hex = '';
                    utf8Bytes.forEach(function(b) { hex += ('0' + b.toString(16)).slice(-2); });

                    var cfg = qz.configs.create(printerName);
                    await qz.print(cfg, [{ type: 'raw', format: 'hex', data: hex }]);
                    console.log('[QZ Tray] Etiqueta enviada para: ' + printerName);
                    return { ok: true, text: function() { return Promise.resolve('OK via QZ Tray (' + printerName + ')'); } };
                } catch (e) {
                    console.error('[QZ Tray] Erro ao imprimir:', e);
                    alert('⚠️ Erro no QZ Tray ao imprimir:\n\n' + (e.message || e) + '\n\nUsando servidor HTTP como fallback...');
                    qzConnected = false;
                    try { qzAtualizarBadge(); } catch(_) {}
                    // Cai no fetch HTTP abaixo
                }
            } else {
                console.log('[_ebiPrint] Usando HTTP fallback (QZ Tray não disponível)');
            }

            // Fallback: HTTP para URL_IMPRESSORA
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
        }

        // Auto-connect iniciado IMEDIATAMENTE (fora do document.ready) para que
        // qzReadyPromise esteja disponível quando os scripts de impressão rodarem.
        // Os scripts de impressão ($scriptsImpressao) executam antes do DOMContentLoaded,
        // por isso a conexão deve ser iniciada aqui e não dentro do $(document).ready().
        (function() {
            var savedPrinter = localStorage.getItem('qzPrinterSelecionada') || '';
            if (!savedPrinter) {
                qzReadyPromise = Promise.resolve(); // nenhuma impressora — resolve imediatamente
                return;
            }
            console.log('[QZ Tray] Impressora salva: "' + savedPrinter + '". Iniciando auto-conexão...');
            qzReadyPromise = qz.websocket.connect({
                host: 'localhost',
                port: { secure: [8181], insecure: [8182] },
                usingSecure: false,
                retries: 2,
                delay: 1
            }).then(function() {
                qzConnected = true;
                console.log('[QZ Tray] Auto-conectado. Impressões irão para: "' + savedPrinter + '"');
                try { qzAtualizarBadge(); } catch(e) {}
            }).catch(function(e) {
                console.warn('[QZ Tray] Auto-conexão falhou:', e.message || e);
                try { qzAtualizarBadge(); } catch(e) {}
            });
        })();

        // Badge atualizado quando o DOM estiver pronto
        $(document).ready(function() {
            qzAtualizarBadge();
            // Aguarda qzReadyPromise para atualizar badge após conexão resolver
            if (qzReadyPromise) {
                qzReadyPromise.then(function() { qzAtualizarBadge(); }).catch(function() {});
            }
        });

    </script>

    <?php if (!empty($scriptsImpressao)): ?>
        <!-- Scripts de impressão gerados pelo servidor -->
        <?php echo $scriptsImpressao; ?>
    <?php endif; ?>
</body>
</html>
