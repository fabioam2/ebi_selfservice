<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Crianças (TESTE v2 — QR Code)</title>
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

        .col-nome-crianca { flex: 0 0 22%; max-width: 22%; }
        .col-responsavel { flex: 0 0 22%; max-width: 22%; }
        .col-idade { flex: 0 0 8%; max-width: 8%; }
        .col-telefone { flex: 0 0 16%; max-width: 16%; }
        .col-comum { flex: 0 0 16%; max-width: 16%; }
        .col-nascimento { flex: 0 0 7%; max-width: 7%; }
        .col-acao { flex: 0 0 9%; max-width: 9%; }
        .badge-teste-v2 { font-size: .65rem; vertical-align: middle; }


        .dropdown-menu button.dropdown-item, .dropdown-menu a.dropdown-item { cursor: pointer; }
        /* Submenu aninhado */
        .dropdown-submenu { position: relative; }
        .dropdown-submenu .dropdown-menu { top: 0; left: 100%; margin-top: -4px; display: none; }
        .dropdown-submenu:hover .dropdown-menu { display: block; }
        .dropdown-submenu > a::after { float: right; margin-top: 5px; }
        /* Botão Como Usar */
        .btn-ajuda { font-size: .8rem; padding: 4px 10px; }
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

        .instancia-info-topo {
            text-align: center;
            font-size: 0.72rem;
            letter-spacing: 0.04em;
            color: #6c757d;
            margin-bottom: 0.4rem;
            text-transform: uppercase;
        }

    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="instancia-info-topo">
            <?php
            $cidadeTopo = defined('INSTANCE_CIDADE') ? trim((string)INSTANCE_CIDADE) : '';
            $comumTopo  = defined('INSTANCE_COMUM') ? trim((string)INSTANCE_COMUM) : '';
            $cabecalhoInstancia = trim($cidadeTopo . ' - ' . $comumTopo, ' -');
            echo sanitize_for_html($cabecalhoInstancia !== '' ? $cabecalhoInstancia : 'Cidade - Comum');
            ?>
        </div>
        <header class="d-flex align-items-center justify-content-between mb-3">
            <div class="dropdown" style="min-width: 220px;">
                <button class="btn btn-light border" type="button" id="dropdownMenuAdmin" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Menu Administração" style="line-height:1; padding: 6px 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuAdmin">
                    <!-- Estatísticas BI -->
                    <h6 class="dropdown-header">Relatórios</h6>
                    <a class="dropdown-item" href="?acao=stats">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bar-chart-fill mr-1" viewBox="0 0 16 16"><path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1z"/></svg>
                        Estatísticas (BI)
                    </a>
                    <!-- Lista com sub-itens -->
                    <div class="dropdown-submenu">
                        <a class="dropdown-item dropdown-toggle" href="#" id="menuLista">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list-ul mr-1" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m-3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/></svg>
                            Lista
                        </a>
                        <div class="dropdown-menu">
                            <button class="dropdown-item" type="button" id="btnBaixarPDF">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer mr-1" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                                Baixar PDF
                            </button>
                            <button class="dropdown-item" type="button" id="btnBaixarCSV">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filetype-csv mr-1" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M14 4.5V14a2 2 0 0 1-2 2h-1v-1h1a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5z"/></svg>
                                Baixar CSV
                            </button>
                            <button class="dropdown-item" type="button" id="btnBaixarXLS">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-spreadsheet mr-1" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V9H3V2a1 1 0 0 1 1-1h5.5zM3 12v-2h2v2zm0 1h2v2H4a1 1 0 0 1-1-1zm3 2v-2h3v2zm4 0v-2h3v1a1 1 0 0 1-1 1zm3-3h-3v-2h3zm-7 0v-2h3v2z"/></svg>
                                Baixar XLS
                            </button>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header">Impressora</h6>
                    <button class="dropdown-item" type="button" onclick="toggleModoDebugImpressao()" id="btnToggleDebug">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bug mr-1" viewBox="0 0 16 16"><path d="M4.355.522a.5.5 0 0 1 .623.333l.291.956A4.979 4.979 0 0 1 8 1c1.007 0 1.946.298 2.731.811l.29-.956a.5.5 0 1 1 .957.29l-.41 1.352A4.985 4.985 0 0 1 13 6h.5a.5.5 0 0 0 0-1h-.538l-.853-2.56a.5.5 0 1 1 .957-.29l.956 2.87A2 2 0 0 1 15.5 7.5v1a2 2 0 0 1-2 2h-.5v.5a5 5 0 0 1-10 0V10h-.5a2 2 0 0 1-2-2v-1a2 2 0 0 1 1.478-1.93l.956-2.87a.5.5 0 1 1 .957.29L2.538 5H2a.5.5 0 0 0 0 1h.5a4.985 4.985 0 0 1 1.432-3.503l-.41-1.352a.5.5 0 0 1 .333-.623zM4 7v4a4 4 0 0 0 8 0V7a4 4 0 0 0-8 0z"/></svg>
                        <span id="labelDebugMode">Modo Debug: OFF</span>
                    </button>
                    <button class="dropdown-item" type="button" onclick="toggleModoTesteImpressao()" id="btnToggleTeste">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tag mr-1" viewBox="0 0 16 16"><path d="M6 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-1 0a.5.5 0 1 0-1 0 .5.5 0 0 0 1 0z"/><path d="M2 1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 1 6.586V2a1 1 0 0 1 1-1zm0 5.586 7 7L13.586 9l-7-7H2v4.586z"/></svg>
                        <span id="labelTesteMode">Testar Impressão: OFF</span>
                    </button>
                    <a class="dropdown-item" href="calibrar.php" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-sliders mr-1" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M11.5 2a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM9.05 3a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0V3h9.05zM4.5 7a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM2.05 8a2.5 2.5 0 0 1 4.9 0H16v1H6.95a2.5 2.5 0 0 1-4.9 0H0V8h2.05zm9.45 4a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm-2.45 1a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0v-1h9.05z"/></svg>
                        Calibrar
                    </a>
                    <button class="dropdown-item" type="button" onclick="abrirModalQZTray()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-usb-plug-fill mr-1" viewBox="0 0 16 16"><path d="M6.5 6a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/><path d="M3 10.5a.5.5 0 0 1 .5-.5H4V9H2V7H1.5a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5H4V3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v1h2.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5H14v2h-1v1h.5a.5.5 0 0 1 0 1h-3l-1 1H7l-1-1H3.5a.5.5 0 0 1-.5-.5z"/></svg>
                        Impressora QZ Tray
                    </button>
                    <div class="dropdown-submenu">
                        <a class="dropdown-item dropdown-toggle" href="#" id="menuInstalar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download mr-1" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                            Instalar
                        </a>
                        <div class="dropdown-menu">
                            <?php $qzDownload = defined('INSTANCE_DIR') ? '../template/download.php' : 'download.php'; ?>
                            <a class="dropdown-item" href="<?php echo $qzDownload; ?>?file=qztray">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-zip mr-1" viewBox="0 0 16 16"><path d="M5 7.5a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v.938l.4 1.599a1 1 0 0 1-.416 1.074l-.93.62a1 1 0 0 1-1.11 0l-.929-.62a1 1 0 0 1-.416-1.074L5 8.438zm2 0h-1v.938a1 1 0 0 1-.03.243l-.4 1.598.93.62.929-.62-.4-1.598A1 1 0 0 1 7 8.438z"/><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5z"/></svg>
                                QZ Tray + Certificados (.zip)
                            </a>
                            <a class="dropdown-item" href="<?php echo $qzDownload; ?>?file=cert">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-lock mr-1" viewBox="0 0 16 16"><path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.293.118a1 1 0 0 0 .101.025 1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56"/><path d="M9.5 6.5a1.5 1.5 0 0 1-1 1.415l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99a1.5 1.5 0 1 1 2-1.415"/></svg>
                                Certificado (.zip)
                            </a>
                            <a class="dropdown-item" href="https://github.com/qzind/tray/releases/download/v2.2.5/qz-tray-2.2.5-x86_64.exe" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download mr-1" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                                QZ Tray 2.2.5 x64 (.exe)
                            </a>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <button class="dropdown-item" type="button" onclick="abrirModalConfigImpressora()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear mr-1" viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/></svg>
                        Configurar Impressora e Instância
                    </button>
                    <button class="dropdown-item" type="button" onclick="toggleAutoImpressao()" id="btnToggleAutoImpressao">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-lightning mr-1" viewBox="0 0 16 16"><path d="M5.52.359A.5.5 0 0 1 6 0h4a.5.5 0 0 1 .474.658L8.694 6H12.5a.5.5 0 0 1 .395.807l-7 9a.5.5 0 0 1-.873-.454L6.823 9.5H3.5a.5.5 0 0 1-.48-.641z"/></svg>
                        <span id="labelAutoImpressao">Auto-Imprimir: OFF</span>
                    </button>
                    <button class="dropdown-item" type="button" onclick="abrirModalAlterarSenha()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-key-fill mr-1" viewBox="0 0 16 16"><path d="M3 8a4 4 0 1 1 7.937.5H14a1 1 0 0 1 1 1v1h-1v1h-1v1h-2.062A4.001 4.001 0 0 1 3 8m4-3a3 3 0 1 0 2.83 4H11v1h1v1h1v-1h1V9h-3.17A3.001 3.001 0 0 0 7 5"/></svg>
                        Alterar Senha
                    </button>
                    <div class="dropdown-divider"></div>
                    <button class="dropdown-item" type="button" onclick="abrirModalZerarArquivo()">Zerar Arquivo</button>
                    <button class="dropdown-item" type="submit" name="preparar_recuperacao" form="formListaCriancas">Recuperar Backup <small class="text-muted">(.bkp.1 é o mais recente)</small></button>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo sanitize_for_html($_SERVER['PHP_SELF']); ?>?acao=logout">Sair do Sistema</a>
                </div>
            </div>
            <h1 class="text-center mb-0">
                <img src="https://placehold.co/60x60/007bff/white?text=Kids" alt="Ícone de Criança" style="vertical-align: middle; border-radius: 50%; margin-right: 10px;">
                Cadastro de Crianças
                <span class="badge badge-warning badge-teste-v2">TESTE v2 — QR c/ Nascimento</span>
            </h1>
            <div class="d-flex align-items-center" style="min-width: 220px; justify-content: flex-end;">
                <a href="./saida/index.php" class="btn btn-outline-secondary btn-sm mr-1" target="_blank">Saída</a>
                <a href="./saida/painel.php" class="btn btn-outline-secondary btn-sm mr-1" target="_blank">Painel Saída</a>
                <a href="<?php echo defined('INSTANCE_DIR') ? '../../../qrcode/default.php' : '../../qrcode/default.php'; ?>" class="btn btn-outline-secondary btn-sm mr-1" target="_blank">QrCode</a>
                <a href="?acao=mobile" class="btn btn-outline-success btn-sm mr-1" target="_blank" title="Versão para Smartphone"><i class="fas fa-mobile-alt mr-1"></i>Mobile</a>
                <button type="button" class="btn btn-outline-info btn-sm btn-ajuda" onclick="abrirModalAjuda()" title="Como usar o sistema">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-question-circle mr-1" viewBox="0 0 16 16"><path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/></svg>
                    Como Usar
                </button>
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
                <div class="col col-nascimento text-center" title="Data de Nascimento">DT</div>
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
                <div class="form-group col-md col-nascimento">
                    <label for="input_<?php echo $linha; ?>_5" class="d-md-none" title="Data de Nascimento">DT <?php echo $linha + 1; ?>:</label>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm data-nascimento-mask cadastro-input text-center" id="input_<?php echo $linha; ?>_5" name="data_nascimento[]" data-linha="<?php echo $linha; ?>" data-col="5" title="Data de Nascimento" placeholder="dd/mm" maxlength="10" style="font-size:0.7rem;padding:0.2rem;">
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
                        <input type="text" class="form-control form-control-sm" id="portaria_cadastro" name="portaria_cadastro" value="A" placeholder="A" maxlength="1">
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
                    <div class="total-cadastros-info" title="Total de cadastros da comum configurada (<?php echo sanitize_for_html($nomeComumDestaque); ?>)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-house-heart-fill" viewBox="0 0 16 16">
                            <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L8 2.207l6.646 6.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                            <path d="m8 3.293 6 6V13.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5V9.293l6-6Zm0 5.189c1.664-1.673 5.825 1.254 0 5.018-5.825-3.764-1.664-6.691 0-5.018Z"/>
                        </svg>
                        Comum: <?php echo $totalComumDestaque; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Badge status QZ Tray -->
                <span id="qzStatusBadge" class="badge badge-secondary mr-2" style="font-size:0.75rem; cursor:pointer; padding: 6px 10px; vertical-align: middle;" onclick="abrirModalQZTray()" title="Status do QZ Tray — clique para configurar">
                    <span id="qzStatusDot" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#dc3545;margin-right:4px;vertical-align:middle;"></span>
                    <span id="qzStatusText">QZ: Desconectado</span>
                </span>

                <div class="d-flex align-items-center">
                    <label for="filtroPortaria" class="mb-0 mr-1 small text-nowrap">Filtrar Portaria:</label>
                    <select multiple class="form-control form-control-sm" id="filtroPortaria" name="filtro_portaria_selecionadas[]" style="min-width:80px; max-width:110px;"></select>
                    <button type="button" class="btn btn-outline-secondary btn-sm ml-1" id="limparFiltroPortaria" title="Limpar filtro de portaria">✕</button>
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
                            <th style="width: 5%;" title="Data de Nascimento">DT</th>
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
                                    <td><?php
                                        echo sanitize_for_html($crianca['nomeCrianca']);
                                        if (function_exists('verificarAniversario')) {
                                            $tagAniv = verificarAniversario($crianca['dataNascimento'] ?? '');
                                            if ($tagAniv === 'hoje') {
                                                echo ' <span class="badge badge-warning" title="Aniversário HOJE!">🎂 Hoje</span>';
                                            } elseif ($tagAniv === 'semana') {
                                                echo ' <span class="badge badge-info" title="Aniversário esta semana">🎈 Semana</span>';
                                            }
                                        }
                                    ?></td>
                                    <td><?php echo sanitize_for_html($crianca['nomeResponsavel']); ?></td>
                                    <td style="text-align: center;"><?php echo sanitize_for_html($crianca['telefone']); ?></td>
                                    <td style="text-align: center;"><?php echo sanitize_for_html($crianca['idade']); ?></td>
                                    <td style="text-align: center; font-size:0.75rem;" title="<?php echo sanitize_for_html($crianca['dataNascimento'] ?? ''); ?>"><?php
                                        $dn = $crianca['dataNascimento'] ?? '';
                                        echo $dn ? sanitize_for_html(substr($dn, 0, 5)) : '';
                                    ?></td>
                                    <td><?php echo sanitize_for_html($crianca['comum']); ?></td>
                                    <td style="text-align: center;" class="no-print">
                                        <button type="button" class="btn btn-sm btn-danger-linha" onclick="confirmarApagarLinha(<?php echo sanitize_for_html($crianca['id']); ?>, '<?php echo addslashes(sanitize_for_html($crianca['nomeCrianca'])); ?>')"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16"><path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0"/></svg></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="12" class="text-center py-4">Nenhuma criança cadastrada ainda.</td></tr>
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
                                Configuração da Impressora e Instância
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
                                <label for="config_cidade_instancia">Cidade da Instância</label>
                                <input type="text" class="form-control" id="config_cidade_instancia" name="config_cidade_instancia" value="<?php echo sanitize_for_html(defined('INSTANCE_CIDADE') ? INSTANCE_CIDADE : ''); ?>" required>
                                <small class="form-text text-muted">Usada no cabeçalho da EBI e nas estatísticas administrativas.</small>
                            </div>

                            <div class="form-group">
                                <label for="config_comum_instancia">Comum da Instância</label>
                                <input type="text" class="form-control" id="config_comum_instancia" name="config_comum_instancia" value="<?php echo sanitize_for_html(defined('INSTANCE_COMUM') ? INSTANCE_COMUM : ''); ?>" required>
                                <small class="form-text text-muted">Usada no contador "Comum" e nas estatísticas administrativas.</small>
                            </div>

                            <div class="form-group">
                                <label for="config_printer_name">Nome da Impressora</label>
                                <input type="text" class="form-control" id="config_printer_name" name="config_printer_name" value="<?php echo PRINTER_NAME; ?>" required>
                                <small class="form-text text-muted">Nome do dispositivo de impressão (ex: ZDesigner 105SL, ZDesigner GK420d, etc.)</small>
                            </div>

                            <div class="form-group">
                                <label>Base do Contador "Comum"</label>
                                <input type="text" class="form-control" value="<?php echo sanitize_for_html(defined('INSTANCE_COMUM') ? INSTANCE_COMUM : ''); ?>" readonly>
                                <small class="form-text text-muted">A base do contador é sempre a Comum da instância. Para alterar, edite o campo "Comum da Instância" acima.</small>
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

        <!-- Modal Alterar Senha -->
        <div class="modal fade" id="modalAlterarSenha" tabindex="-1" role="dialog" aria-labelledby="modalAlterarSenhaLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>">
                        <?php echo csrf_field(); ?>
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="modalAlterarSenhaLabel">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-key-fill mr-1" viewBox="0 0 16 16"><path d="M3 8a4 4 0 1 1 7.937.5H14a1 1 0 0 1 1 1v1h-1v1h-1v1h-2.062A4.001 4.001 0 0 1 3 8m4-3a3 3 0 1 0 2.83 4H11v1h1v1h1v-1h1V9h-3.17A3.001 3.001 0 0 0 7 5"/></svg>
                                Alterar Senha da Instância
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="senha_atual_instancia">Senha Atual</label>
                                <input type="password" class="form-control" id="senha_atual_instancia" name="senha_atual" required>
                            </div>
                            <div class="form-group">
                                <label for="nova_senha_instancia">Nova Senha</label>
                                <input type="password" class="form-control" id="nova_senha_instancia" name="nova_senha" minlength="8" required>
                                <small class="form-text text-muted">Mínimo de 8 caracteres.</small>
                            </div>
                            <div class="form-group mb-0">
                                <label for="confirmar_nova_senha_instancia">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" id="confirmar_nova_senha_instancia" name="confirmar_nova_senha" minlength="8" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" name="alterar_senha_instancia">Salvar Nova Senha</button>
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

        <!-- Modal Como Usar -->
        <div class="modal fade" id="modalAjuda" tabindex="-1" role="dialog" aria-labelledby="modalAjudaLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="modalAjudaLabel">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-question-circle mr-2" viewBox="0 0 16 16"><path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/></svg>
                            Como Usar o Sistema de Cadastro
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Passo 1 -->
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-primary font-weight-bold">
                                        <span class="badge badge-primary mr-1">1</span> Cadastrar Crianças
                                    </h6>
                                    <p class="small mb-2">Preencha os campos do formulário para cada criança:</p>
                                    <ul class="small pl-3 mb-0">
                                        <li><strong>Nome da Criança</strong> — como aparecerá na pulseira</li>
                                        <li><strong>Responsável</strong> — nome do pai/mãe que vai retirar</li>
                                        <li><strong>Telefone</strong> — para contato se necessário</li>
                                        <li><strong>Idade</strong> — usada para direcionar à sala correta</li>
                                        <li><strong>Comum</strong> — congregação de origem</li>
                                        <li><strong>Portaria</strong> — portaria de entrada (A, B, C…)</li>
                                    </ul>
                                    <div class="alert alert-info mt-2 mb-0 p-2 small">
                                        <strong>Dica:</strong> Cadastre todas as crianças do mesmo responsável de uma vez — use o botão <em>Copiar Responsável</em> para agilizar.
                                    </div>
                                </div>
                            </div>
                            <!-- Passo 2 -->
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-success font-weight-bold">
                                        <span class="badge badge-success mr-1">2</span> QR Code (pistola)
                                    </h6>
                                    <p class="small mb-2">Acelere o cadastro com um leitor de QR Code (pistola ou celular):</p>
                                    <ul class="small pl-3 mb-2">
                                        <li>Conecte o leitor ao computador (USB ou Bluetooth)</li>
                                        <li>Clique no campo <strong>Responsável</strong> ou <strong>Telefone</strong></li>
                                        <li>Aponte o leitor para o QR Code do responsável</li>
                                        <li>Os dados são preenchidos automaticamente</li>
                                    </ul>
                                    <div class="alert alert-success mb-0 p-2 small">
                                        <strong>Gerar QR Codes:</strong> Acesse o botão <strong>QrCode</strong> no canto superior direito ou clique <a href="<?php echo defined('INSTANCE_DIR') ? '../../../qrcode/default.php' : '../../qrcode/default.php'; ?>" target="_blank">aqui</a> para criar os QR Codes dos responsáveis com seus dados pré-preenchidos.
                                    </div>
                                </div>
                            </div>
                            <!-- Passo 3 -->
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-warning font-weight-bold">
                                        <span class="badge badge-warning mr-1">3</span> Imprimir Pulseiras
                                    </h6>
                                    <p class="small mb-2">Após o cadastro, selecione as crianças e imprima as pulseiras:</p>
                                    <ul class="small pl-3 mb-0">
                                        <li>Marque as caixas ao lado de cada criança</li>
                                        <li>Clique em <strong>Imprimir</strong> (botão verde) ou use <kbd>Enter</kbd></li>
                                        <li>A pulseira sai com nome, responsável e QR Code</li>
                                        <li>O leitor QR Code na saída usa esse código para identificar as crianças</li>
                                    </ul>
                                </div>
                            </div>
                            <!-- Passo 4 -->
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-danger font-weight-bold">
                                        <span class="badge badge-danger mr-1">4</span> Controle de Saída
                                    </h6>
                                    <p class="small mb-2">A portaria usa o módulo de <strong>Saída</strong> para liberar as crianças:</p>
                                    <ul class="small pl-3 mb-2">
                                        <li>Acesse <strong>Saída</strong> no canto superior direito</li>
                                        <li>O responsável apresenta o QR Code da pulseira</li>
                                        <li>O sistema identifica e lista as crianças vinculadas</li>
                                        <li>A portaria confirma e registra a saída</li>
                                    </ul>
                                    <div class="alert alert-secondary mb-0 p-2 small">
                                        Use <strong>Painel Saída</strong> para monitorar em tempo real as saídas registradas.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Menu de Relatórios -->
                        <div class="border rounded p-3 mt-1 bg-light">
                            <h6 class="font-weight-bold mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bar-chart mr-1" viewBox="0 0 16 16"><path d="M4 11H2v3h2zm5-4H7v7h2zm5-5v12h-2V2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zm-5 4a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1zm-5 4a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/></svg>
                                Menu ☰ — Opções Disponíveis
                            </h6>
                            <div class="row small">
                                <div class="col-md-4">
                                    <strong>Relatórios</strong>
                                    <ul class="pl-3 mb-0">
                                        <li><strong>Estatísticas (BI)</strong> — gráficos e histórico por período</li>
                                        <li><strong>Lista → Imprimir</strong> — impressão da lista de cadastros</li>
                                        <li><strong>Lista → CSV / XLS</strong> — exportar para planilha</li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <strong>Impressora</strong>
                                    <ul class="pl-3 mb-0">
                                        <li>Configurar impressora ZPL</li>
                                        <li>QZ Tray — conexão USB/rede</li>
                                        <li>Calibrar — ajustar posição</li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <strong>Dados</strong>
                                    <ul class="pl-3 mb-0">
                                        <li>Zerar Arquivo — apaga todos os cadastros do dia</li>
                                        <li>Recuperar Backup — restaura cadastros anteriores</li>
                                        <li>Sair — encerrar sessão</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <label class="mr-auto small text-muted">
                            <input type="checkbox" id="chkNaoMostrarAjuda"> Não mostrar novamente
                        </label>
                        <button type="button" class="btn btn-info text-white" data-dismiss="modal">Entendido!</button>
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
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
    <script>
        var csrfToken = <?php echo json_encode(csrf_token()); ?>;
        const NUM_LINHAS_FORM_CADASTRO = <?php echo NUM_LINHAS_FORMULARIO_CADASTRO; ?>;
        // TESTE v2: agora suporta 6 colunas por linha (a 6ª é a data de nascimento,
        // presente apenas no novo formato de QR Code gerado por qrcode/qrcode.2.php).
        const NUM_CAMPOS_POR_LINHA_CADASTRO = 6;

        function focarPrimeiroCampoCadastro() {
            $('#input_0_0').focus();
        }

        function limparLinhaCadastro(linha) {
            $('#input_' + linha + '_0').val('');
            $('#input_' + linha + '_1').val('');
            $('#input_' + linha + '_2').val('');
            $('#input_' + linha + '_3').val('').trigger('input');
            $('#input_' + linha + '_4').val('');
            $('#input_' + linha + '_5').val('').trigger('input');
        }

        $(document).ready(function(){
            $('.telefone-mask').mask('(00) 00000-0000');
            $('.data-nascimento-mask').mask('00/00/0000');

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
                // Auto-imprimir: apenas os cadastros recém-feitos (últimos N)
                if (localStorage.getItem('autoImpressao') === 'true') {
                    var cadastrosRecentes = <?php echo (int)$cadastrosRecentesCount; ?>;
                    function tentarAutoImprimir() {
                        if (cadastrosRecentes <= 0) return;
                        // Selecionar apenas os primeiros N não-impressos no topo da tabela
                        var count = 0;
                        $('#lista-criancas tr').each(function() {
                            if (count >= cadastrosRecentes) return false;
                            var impresso = $(this).find('.status-icon svg[fill="green"]').length > 0;
                            if (!impresso) {
                                $(this).find('.checkbox-crianca').prop('checked', true);
                                count++;
                            }
                        });
                        if (count > 0) {
                            $('#formListaCriancas').find('input[name="imprimir"]').remove();
                            $('#formListaCriancas').append('<input type="hidden" name="imprimir" value="1">');
                            $('#formListaCriancas').submit();
                        }
                    }
                    if (typeof qzReadyPromise !== 'undefined' && qzReadyPromise) {
                        qzReadyPromise.then(function() {
                            if (qzConnected) tentarAutoImprimir();
                        });
                    } else {
                        setTimeout(function() {
                            tentarAutoImprimir();
                        }, 2000);
                    }
                }
            <?php elseif ($focarAposAcao): ?>
                focarPrimeiroCampoCadastro();
            <?php elseif (!empty($mensagemErro)): ?>
            <?php endif; ?>


            // TESTE v2 — leitura de 2 formatos de QR Code:
            //  - Formato atual (5 campos por criança): nome \t responsavel \t idade \t telefone \t comum
            //  - Novo formato (6 campos): nome \t responsavel \t idade \t telefone \t comum \t data_nascimento
            // O leitor de QR Code (pistola/celular) envia \t (Tab) entre campos da MESMA criança
            // e \r (Enter) para separar uma criança da próxima. Por isso o Tab sempre avança uma
            // coluna na mesma linha, e o Enter sempre pula para a coluna 0 da próxima linha —
            // independentemente de quantas colunas (5 ou 6) foram preenchidas antes do Enter.
            // Isso permite que a mesma tela funcione com os dois tipos de QR Code sem
            // precisar saber de antemão qual formato está sendo lido.
            $('.cadastro-input').on('keydown', function(e) {
                const key = e.key;
                if (key !== 'Enter' && key !== 'Tab') {
                    return;
                }
                e.preventDefault();

                const target = e.target;
                const currentLinha = parseInt($(target).data('linha'));
                const currentCol = parseInt($(target).data('col'));

                if (key === 'Tab') {
                    // Avança um campo dentro da mesma criança (ou pula pra próxima linha/portaria
                    // se já estiver no último campo — comportamento normal de Tab do formulário).
                    if (currentCol < NUM_CAMPOS_POR_LINHA_CADASTRO - 1) {
                        $('#input_' + currentLinha + '_' + (currentCol + 1)).focus();
                    } else if (currentLinha < NUM_LINHAS_FORM_CADASTRO - 1) {
                        $('#input_' + (currentLinha + 1) + '_0').focus();
                    } else {
                        $('#portaria_cadastro').focus();
                    }
                    return;
                }

                // Enter: fecha o registro da criança atual (5 ou 6 colunas preenchidas)
                // e vai direto para a coluna 0 da próxima linha.
                if (currentLinha < NUM_LINHAS_FORM_CADASTRO - 1) {
                    $('#input_' + (currentLinha + 1) + '_0').focus();
                } else {
                    // Auto-cadastrar: se portaria já está preenchida e auto-imprimir ON
                    var portariaVal = $('#portaria_cadastro').val().trim();
                    if (localStorage.getItem('autoImpressao') === 'true' && portariaVal.length === 1 && $('#input_0_0').val().trim() !== '') {
                        $('#btnCadastrar').click();
                        return;
                    }
                    $('#portaria_cadastro').focus();
                }
            });

            $('#portaria_cadastro').on('keydown', function(e) {
                if (e.key === 'Enter' || (e.key === 'Tab' && !e.shiftKey)) {
                    e.preventDefault();
                    var portariaVal = $(this).val().trim();
                    // Auto-cadastrar: se auto-imprimir está ON e portaria preenchida e há dados no formulário
                    if (localStorage.getItem('autoImpressao') === 'true' && portariaVal.length === 1) {
                        var temDados = $('#input_0_0').val().trim() !== '';
                        if (temDados) {
                            $('#btnCadastrar').click();
                            return;
                        }
                    }
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

            $('#btnBaixarPDF').on('click', function() {
                var dados = extrairDadosTabelaVisivel();
                if (!dados.rows.length) {
                    alert('Nenhum registro visível para exportar em PDF.');
                    return;
                }

                if (!window.jspdf || !window.jspdf.jsPDF) {
                    alert('Biblioteca de PDF não carregada. Atualize a página e tente novamente.');
                    return;
                }

                var jsPDF = window.jspdf.jsPDF;
                var doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
                var titulo = 'Lista de Crianças Cadastradas';
                var agora = new Date();
                var ts = agora.getFullYear()
                    + String(agora.getMonth() + 1).padStart(2, '0')
                    + String(agora.getDate()).padStart(2, '0')
                    + '_'
                    + String(agora.getHours()).padStart(2, '0')
                    + String(agora.getMinutes()).padStart(2, '0');

                doc.setFontSize(13);
                doc.text(titulo, 40, 34);
                doc.setFontSize(9);
                doc.text('Gerado em: ' + agora.toLocaleString('pt-BR'), 40, 50);

                doc.autoTable({
                    startY: 62,
                    head: [dados.headers],
                    body: dados.rows,
                    styles: { fontSize: 8, cellPadding: 3 },
                    headStyles: { fillColor: [0, 123, 255] },
                    theme: 'striped',
                    margin: { left: 20, right: 20 }
                });

                doc.save('lista_criancas_' + ts + '.pdf');
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

        // ============ MODAL COMO USAR ============

        function abrirModalAjuda() {
            $('#modalAjuda').modal('show');
        }

        // Mostrar automaticamente no primeiro acesso
        (function() {
            if (!localStorage.getItem('ebi_ajuda_visto')) {
                setTimeout(function() { $('#modalAjuda').modal('show'); }, 600);
            }
            $('#modalAjuda').on('hide.bs.modal', function() {
                if ($('#chkNaoMostrarAjuda').is(':checked')) {
                    localStorage.setItem('ebi_ajuda_visto', '1');
                }
            });
        })();

        // ============ FUNÇÕES DE CONFIGURAÇÃO E DEBUG ============

        function abrirModalConfigImpressora() {
            $('#modalConfigImpressora').modal('show');
        }

        function abrirModalAlterarSenha() {
            $('#modalAlterarSenha').modal('show');
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

        // ============ AUTO-IMPRIMIR APÓS CADASTRO ============

        var autoImpressaoAtiva = localStorage.getItem('autoImpressao') === 'true';

        function toggleAutoImpressao() {
            autoImpressaoAtiva = !autoImpressaoAtiva;
            localStorage.setItem('autoImpressao', autoImpressaoAtiva);
            atualizarEstadoAutoImpressao();
            if (autoImpressaoAtiva) {
                alert('Auto-Imprimir ATIVADO!\n\nApós cadastrar, o sistema imprimirá automaticamente as pulseiras.');
            } else {
                alert('Auto-Imprimir DESATIVADO.\nApós cadastrar, será necessário imprimir manualmente.');
            }
        }

        function atualizarEstadoAutoImpressao() {
            var label = document.getElementById('labelAutoImpressao');
            var btn = document.getElementById('btnToggleAutoImpressao');
            if (autoImpressaoAtiva) {
                label.textContent = 'Auto-Imprimir: ON';
                label.style.fontWeight = 'bold';
                label.style.color = '#1b5e20';
                btn.style.backgroundColor = '#c8e6c9';
            } else {
                label.textContent = 'Auto-Imprimir: OFF';
                label.style.fontWeight = 'normal';
                label.style.color = '';
                btn.style.backgroundColor = '';
            }
        }

        // Inicializar estado visual
        atualizarEstadoAutoImpressao();

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
