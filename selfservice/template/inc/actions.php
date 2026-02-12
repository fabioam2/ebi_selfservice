<?php
/**
 * Tratamento de POST (ações). CSRF já validado pelo index.
 * Depende de $todosOsCadastros estar definido no escopo de quem inclui.
 */

$urlImpressora = URL_IMPRESSORA;

if (isset($_POST['cadastrar'])) {
    $nomesCrianca = $_POST['nome_crianca'] ?? [];
    $nomesResponsavel = $_POST['nome_responsavel'] ?? [];
    $idades = $_POST['idade'] ?? [];
    $telefones = $_POST['telefone'] ?? [];
    $comuns = $_POST['comum'] ?? [];
    $portariaCadastro = strtoupper(trim($_POST['portaria_cadastro'] ?? ''));

    $cadastrosRealizadosComSucesso = 0;
    $errosNoCadastro = [];
    $backupRealizadoNestaOperacao = false;
    $linhasParaAdicionarAoArquivo = [];
    $codRespParaEsteLote = 0;
    $codRespDeterminado = false;

    if (empty($portariaCadastro) || !preg_match('/^[A-Z]$/', $portariaCadastro)) {
        $errosNoCadastro[] = "Portaria inválida. Coloque o código da portaria, uma única letra (A-Z). Nenhum cadastro foi realizado.";
    } else {
        $proximoIdDisponivel = gerarCodigoSequencialBase(ARQUIVO_DADOS);
        $contadorDeNovosIds = 0;

        for ($i = 0; $i < NUM_LINHAS_FORMULARIO_CADASTRO; $i++) {
            $nomeCriancaAtualTrimmed = trim($nomesCrianca[$i] ?? '');
            $nomeResponsavelAtualTrimmed = trim($nomesResponsavel[$i] ?? '');
            $idadeAtualTrimmed = trim($idades[$i] ?? '');
            $telefoneAtualTrimmed = trim($telefones[$i] ?? '');
            $comumAtualTrimmed = trim($comuns[$i] ?? '');

            $linhaFoiIniciada = !empty($nomeCriancaAtualTrimmed) || !empty($nomeResponsavelAtualTrimmed) || !empty($idadeAtualTrimmed) || !empty($telefoneAtualTrimmed) || !empty($comumAtualTrimmed);

            if ($linhaFoiIniciada) {
                if (empty($nomeCriancaAtualTrimmed) || empty($nomeResponsavelAtualTrimmed) || empty($idadeAtualTrimmed) || empty($telefoneAtualTrimmed) || empty($comumAtualTrimmed)) {
                    $errosNoCadastro[] = "Linha " . ($i + 1) . ": Todos os campos são obrigatórios se a linha for preenchida.";
                    continue;
                }

                if (!$backupRealizadoNestaOperacao && file_exists(ARQUIVO_DADOS) && filesize(ARQUIVO_DADOS) > 0) {
                    gerenciarBackups(ARQUIVO_DADOS);
                    $backupRealizadoNestaOperacao = true;
                }

                if (!$codRespDeterminado) {
                    $codRespParaEsteLote = gerarProximoCodResp(ARQUIVO_DADOS);
                    $codRespDeterminado = true;
                }

                $idAtualParaNovoCadastro = $proximoIdDisponivel + $contadorDeNovosIds;

                $linhasParaAdicionarAoArquivo[] = implode(DELIMITADOR, [
                    $idAtualParaNovoCadastro,
                    sanitize_for_file($nomeCriancaAtualTrimmed),
                    sanitize_for_file($nomeResponsavelAtualTrimmed),
                    sanitize_for_file($telefoneAtualTrimmed),
                    sanitize_for_file($idadeAtualTrimmed),
                    sanitize_for_file($comumAtualTrimmed),
                    'N',
                    $portariaCadastro,
                    $codRespParaEsteLote
                ]);
                $cadastrosRealizadosComSucesso++;
                $contadorDeNovosIds++;
            }
        }

        if ($cadastrosRealizadosComSucesso > 0) {
            $conteudoParaEscrever = implode(PHP_EOL, $linhasParaAdicionarAoArquivo) . PHP_EOL;
            if (!file_put_contents(ARQUIVO_DADOS, $conteudoParaEscrever, FILE_APPEND | LOCK_EX)) {
                $errosNoCadastro[] = "Erro crítico: Falha ao salvar os novos cadastros no arquivo.";
                $cadastrosRealizadosComSucesso = 0;
            }
        }
    }

    if (!empty($errosNoCadastro)) {
        $mensagemDeErroFormatada = "<strong>Atenção - Erros no Cadastro:</strong><br>" . implode("<br>", $errosNoCadastro);
        if ($cadastrosRealizadosComSucesso > 0) {
            $_SESSION['mensagemSucesso'] = $cadastrosRealizadosComSucesso . " cadastro(s) realizado(s) com sucesso.";
            $_SESSION['mensagemErro'] = $mensagemDeErroFormatada;
        } else {
            $_SESSION['mensagemErro'] = $mensagemDeErroFormatada;
        }
        $_SESSION['focar_apos_acao'] = true;
    } elseif ($cadastrosRealizadosComSucesso > 0) {
        $_SESSION['mensagemSucesso'] = $cadastrosRealizadosComSucesso . " cadastro(s) realizado(s)!";
        $_SESSION['cadastro_realizado_sucesso'] = true;
    }

    header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

if (isset($_POST['imprimir'])) {
    $_SESSION['focar_apos_acao'] = true;

    $algumaImpressaoEnviada = false;
    $contadorImpressoesCriancas = 0;
    $contadorImpressoesResponsaveis = 0;
    $scriptsParaExecutar = "";
    $responsaveisParaEtiquetas = [];

    if (isset($_POST['selecionados']) && is_array($_POST['selecionados']) && count($_POST['selecionados']) > 0) {
        foreach ($_POST['selecionados'] as $idSelecionadoStr) {
            $idSelecionado = intval($idSelecionadoStr);
            if (isset($todosOsCadastros[$idSelecionado])) {
                $crianca = $todosOsCadastros[$idSelecionado];
                $codigoZPLCrianca = gerarCodigoZPL($crianca['nomeCrianca'], $crianca['nomeResponsavel'], $crianca['idade'], $crianca['id'], $crianca['telefone']);
                $payloadCrianca = ["device" => obterPayloadDispositivo(), "data" => $codigoZPLCrianca];

                $jsonPayloadCrianca = json_encode($payloadCrianca);
                if ($jsonPayloadCrianca === false) {
                    error_log("JSON encode error for child payload ID " . $idSelecionado . ": " . json_last_error_msg());
                    $_SESSION['mensagemErro'] = ($_SESSION['mensagemErro'] ?? '') . "<br>Erro ao preparar dados de impressão para criança ID " . $idSelecionado . ".";
                    continue;
                }

                $scriptsParaExecutar .= "<script>
(function() {
    var url = " . json_encode($urlImpressora) . ";
    var payload = " . $jsonPayloadCrianca . ";
    var currentId = " . $idSelecionado . ";
    var nomeCrianca = \"" . addslashes(sanitize_for_html($crianca['nomeCrianca'])) . "\";
    var zplCode = payload.data;
    var modoDebug = localStorage.getItem('modoDebugImpressao') === 'true';

    if (modoDebug) {
        // Modo debug: abrir modal com ZPL
        if (!window.debugPrintQueue) window.debugPrintQueue = [];
        window.debugPrintQueue.push({
            zpl: zplCode,
            url: url,
            info: {
                nomeCrianca: nomeCrianca,
                codigo: currentId,
                tipo: 'Pulseira Criança',
                urlImpressora: url
            },
            currentId: currentId
        });
    } else {
        // Modo normal: enviar diretamente
        fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
        .then(response => {
            if (!response.ok) return response.text().then(text => { throw new Error('Falha (criança ' + nomeCrianca + '): ' + response.status + ' ' + text); });
            return response.text();
        })
        .then(result => {
            console.log('Etiqueta CRIANÇA ID ' + currentId + ' (' + nomeCrianca + ') enviada. Resposta: ' + result);
            var row = $('tr[data-id=\"' + currentId + '\"]');
            if (row.length) {
                row.find('.status-icon').html('<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"18\" height=\"18\" fill=\"green\" class=\"bi bi-check-circle-fill\" viewBox=\"0 0 16 16\"><path d=\"M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z\"/></svg>');
                row.find('.checkbox-crianca').prop('checked', false);
            }
        })
        .catch(error => { console.error('Erro CRIANÇA ID ' + currentId + ' (' + nomeCrianca + '):', error); });
    }
})();
</script>";
                $todosOsCadastros[$idSelecionado]['statusImpresso'] = 'S';
                $contadorImpressoesCriancas++;
                $algumaImpressaoEnviada = true;

                $codResp = $crianca['cod_resp'];
                if (!empty($codResp)) {
                    if (!isset($responsaveisParaEtiquetas[$codResp])) {
                        $responsaveisParaEtiquetas[$codResp] = ['nomeResponsavel' => $crianca['nomeResponsavel'], 'criancas' => []];
                    }
                    $responsaveisParaEtiquetas[$codResp]['criancas'][] = $crianca['nomeCrianca'];
                }
            }
        }

        if ($algumaImpressaoEnviada) {
            if (file_exists(ARQUIVO_DADOS) && filesize(ARQUIVO_DADOS) > 0) {
                gerenciarBackups(ARQUIVO_DADOS);
            }
            salvarTodosCadastros(ARQUIVO_DADOS, $todosOsCadastros);

            foreach ($responsaveisParaEtiquetas as $codResp => $dataResp) {
                if (!empty($dataResp['criancas'])) {
                    $codigoZPLResponsavel = gerarCodigoZPLResponsavel($dataResp['nomeResponsavel'], $dataResp['criancas'], $codResp);
                    $payloadResponsavel = ["device" => obterPayloadDispositivo(), "data" => $codigoZPLResponsavel];

                    $jsonPayloadResponsavel = json_encode($payloadResponsavel);
                    if ($jsonPayloadResponsavel === false) {
                        error_log("JSON encode error for responsible payload cod_resp " . $codResp . ": " . json_last_error_msg());
                        $_SESSION['mensagemErro'] = ($_SESSION['mensagemErro'] ?? '') . "<br>Erro ao preparar dados de impressão para responsável do lote " . $codResp . ".";
                        continue;
                    }

                    $scriptsParaExecutar .= "<script>
(function() {
    var url = " . json_encode($urlImpressora) . ";
    var payload = " . $jsonPayloadResponsavel . ";
    var codResp = \"" . addslashes(sanitize_for_html($codResp)) . "\";
    var nomeResp = \"" . addslashes(sanitize_for_html($dataResp['nomeResponsavel'])) . "\";
    var zplCode = payload.data;
    var modoDebug = localStorage.getItem('modoDebugImpressao') === 'true';

    if (modoDebug) {
        // Modo debug: abrir modal com ZPL
        if (!window.debugPrintQueue) window.debugPrintQueue = [];
        window.debugPrintQueue.push({
            zpl: zplCode,
            url: url,
            info: {
                nomeCrianca: 'Responsável: ' + nomeResp,
                codigo: codResp,
                tipo: 'Pulseira Responsável',
                urlImpressora: url
            },
            codResp: codResp
        });
    } else {
        // Modo normal: enviar diretamente
        fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
        .then(response => {
            if (!response.ok) return response.text().then(text => { throw new Error('Falha (responsável ' + nomeResp + '): ' + response.status + ' ' + text); });
            return response.text();
        })
        .then(result => { console.log('Etiqueta RESPONSÁVEL Cod Resp ' + codResp + ' (' + nomeResp + ') enviada. Resposta: ' + result); })
        .catch(error => { console.error('Erro RESPONSÁVEL Cod Resp ' + codResp + ' (' + nomeResp + '):', error); });
    }
})();
</script>";
                    $contadorImpressoesResponsaveis++;
                }
            }
        }

        if ($contadorImpressoesCriancas > 0) {
            $msg = $contadorImpressoesCriancas . " etiqueta(s) de criança(s) processada(s).";
            if ($contadorImpressoesResponsaveis > 0) {
                $msg .= " " . $contadorImpressoesResponsaveis . " etiqueta(s) de responsável(is) também processada(s).";
            }
            $_SESSION['mensagemSucesso'] = ($_SESSION['mensagemSucesso'] ?? '') . $msg . " Status atualizado.";
        } elseif (isset($_POST['selecionados']) && count($_POST['selecionados']) > 0 && empty($_SESSION['mensagemErro'])) {
            $_SESSION['mensagemErro'] = "Nenhuma criança válida para os IDs selecionados foi encontrada para impressão.";
        }

        if ($algumaImpressaoEnviada) {
            $scriptsParaExecutar .= "<script>
(function() {
    $(document).ready(function() {
        setTimeout(function() {
            if ($('.checkbox-crianca:checked').length === 0) { $('#selecionarTodos').prop('checked', false); }
        }, 2000);

        // Processar fila de debug se modo debug estiver ativo
        if (localStorage.getItem('modoDebugImpressao') === 'true' && window.debugPrintQueue && window.debugPrintQueue.length > 0) {
            var currentDebugIndex = 0;

            function mostrarProximoDebug() {
                if (currentDebugIndex < window.debugPrintQueue.length) {
                    var item = window.debugPrintQueue[currentDebugIndex];
                    abrirModalDebugZPL(item.zpl, item.info);

                    // Quando o modal fechar, mostrar o próximo
                    $('#modalDebugZPL').off('hidden.bs.modal').on('hidden.bs.modal', function() {
                        currentDebugIndex++;
                        if (currentDebugIndex < window.debugPrintQueue.length) {
                            setTimeout(mostrarProximoDebug, 500);
                        } else {
                            // Limpar a fila após processar todos
                            window.debugPrintQueue = [];
                        }
                    });
                }
            }

            // Iniciar processamento da fila após um pequeno delay
            setTimeout(mostrarProximoDebug, 1000);
        }
    });
})();
</script>";
        }

        // Salvar scripts na sessão para execução após redirect
        $_SESSION['scripts_impressao'] = $scriptsParaExecutar;
        header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
        return;
    }

    $_SESSION['mensagemErro'] = "Nenhuma criança selecionada para impressão!";
    header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

if (isset($_POST['acao']) && $_POST['acao'] === 'apagar_linha') {
    if (isset($_POST['id_para_apagar'])) {
        $idParaApagar = intval($_POST['id_para_apagar']);
        if (isset($todosOsCadastros[$idParaApagar])) {
            $nomeCriancaApagada = $todosOsCadastros[$idParaApagar]['nomeCrianca'];
            unset($todosOsCadastros[$idParaApagar]);
            if (file_exists(ARQUIVO_DADOS) && filesize(ARQUIVO_DADOS) > 0) {
                gerenciarBackups(ARQUIVO_DADOS);
            }
            if (salvarTodosCadastros(ARQUIVO_DADOS, $todosOsCadastros)) {
                $_SESSION['mensagemSucesso'] = "Cadastro de '" . sanitize_for_html($nomeCriancaApagada) . "' (ID: " . $idParaApagar . ") apagado.";
            } else {
                $_SESSION['mensagemErro'] = "Erro ao salvar após apagar.";
                $_SESSION['focar_apos_acao'] = true;
            }
        } else {
            $_SESSION['mensagemErro'] = "Cadastro ID " . $idParaApagar . " não encontrado.";
            $_SESSION['focar_apos_acao'] = true;
        }
    } else {
        $_SESSION['mensagemErro'] = "ID para apagar não especificado.";
        $_SESSION['focar_apos_acao'] = true;
    }
    header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

if (isset($_POST['zerar_arquivo_confirmado'])) {
    if (isset($_POST['admin_senha']) && $_POST['admin_senha'] === SENHA_ADMIN_REAL) {
        $bkp1_path = ARQUIVO_DADOS . '.bkp.1';

        if (file_exists(ARQUIVO_DADOS) && filesize(ARQUIVO_DADOS) > 0) {
            gerenciarBackups(ARQUIVO_DADOS);
        }

        if (file_put_contents(ARQUIVO_DADOS, "", LOCK_EX) !== false) {
            $_SESSION['mensagemSucesso'] = "Arquivo de cadastros zerado com sucesso!";
            $outrosRemovidos = false;
            for ($i = 2; $i <= MAX_BACKUPS; $i++) {
                $backupParaApagar = ARQUIVO_DADOS . '.bkp.' . $i;
                if (file_exists($backupParaApagar)) {
                    @unlink($backupParaApagar);
                    $outrosRemovidos = true;
                }
            }
            if ($outrosRemovidos) $_SESSION['mensagemSucesso'] .= " Backups antigos (.bkp.2+) removidos.";

            $backupsRestantes = listarBackups(ARQUIVO_DADOS);
            if (count($backupsRestantes) === 1 && basename($backupsRestantes[0]) === basename($bkp1_path)) {
                if (file_exists($bkp1_path)) {
                    @unlink($bkp1_path);
                    $_SESSION['mensagemSucesso'] .= " Backup .bkp.1 (sendo o único restante) também foi removido.";
                }
            } elseif (file_exists($bkp1_path)) {
                $_SESSION['mensagemSucesso'] .= " Backup .bkp.1 mantido.";
            }
        } else {
            $_SESSION['mensagemErro'] = "Erro ao zerar o arquivo.";
            $_SESSION['focar_apos_acao'] = true;
        }
    } else {
        $_SESSION['mensagemErro'] = "Senha administrativa incorreta.";
        $_SESSION['focar_apos_acao'] = true;
    }
    header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

if (isset($_POST['preparar_recuperacao'])) {
    $_SESSION['exibirModalRecuperacao'] = true;
    $_SESSION['focar_apos_acao'] = true;
    header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

if (isset($_POST['limpar_flag_modal_recuperacao'])) {
    $_SESSION['exibirModalRecuperacao'] = false;
    $_SESSION['focar_apos_acao'] = true;
    header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

if (isset($_POST['salvar_config_impressora'])) {
    if (isset($_POST['admin_senha']) && $_POST['admin_senha'] === SENHA_ADMIN_REAL) {
        $config_file = __DIR__ . '/../config.ini';

        // Validar inputs
        $printer_name = trim($_POST['config_printer_name'] ?? 'ZDesigner 105SL');
        $tampulseira = intval($_POST['config_tampulseira'] ?? 269);
        $dots = intval($_POST['config_dots'] ?? 8);
        $fecho = intval($_POST['config_fecho'] ?? 30);
        $fechoini = intval($_POST['config_fechoini'] ?? 1);
        $url_impressora = trim($_POST['config_url_impressora'] ?? 'http://127.0.0.1:9100/write');
        $largura_pulseira = intval($_POST['config_largura_pulseira'] ?? 192);

        // Ler o arquivo config.ini atual
        $config_content = file_get_contents($config_file);

        if ($config_content !== false) {
            // Atualizar valores usando regex
            $config_content = preg_replace('/^PRINTER_NAME\s*=\s*.+$/m', 'PRINTER_NAME = "' . addslashes($printer_name) . '"', $config_content);
            $config_content = preg_replace('/^TAMPULSEIRA\s*=\s*.+$/m', 'TAMPULSEIRA = ' . $tampulseira, $config_content);
            $config_content = preg_replace('/^DOTS\s*=\s*.+$/m', 'DOTS = ' . $dots, $config_content);
            $config_content = preg_replace('/^FECHO\s*=\s*.+$/m', 'FECHO = ' . $fecho, $config_content);
            $config_content = preg_replace('/^FECHOINI\s*=\s*.+$/m', 'FECHOINI = ' . $fechoini, $config_content);
            $config_content = preg_replace('/^URL_IMPRESSORA\s*=\s*.+$/m', 'URL_IMPRESSORA = "' . addslashes($url_impressora) . '"', $config_content);
            $config_content = preg_replace('/^LARGURA_PULSEIRA\s*=\s*.+$/m', 'LARGURA_PULSEIRA = ' . $largura_pulseira, $config_content);

            // Salvar arquivo
            if (file_put_contents($config_file, $config_content, LOCK_EX) !== false) {
                $_SESSION['mensagemSucesso'] = "Configurações da impressora salvas com sucesso! As alterações entrarão em vigor no próximo acesso.";
            } else {
                $_SESSION['mensagemErro'] = "Erro ao salvar as configurações no arquivo config.ini.";
            }
        } else {
            $_SESSION['mensagemErro'] = "Erro ao ler o arquivo config.ini.";
        }
    } else {
        $_SESSION['mensagemErro'] = "Senha administrativa incorreta.";
    }
    $_SESSION['focar_apos_acao'] = true;
    header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

if (isset($_POST['confirmar_recuperacao'])) {
    if (isset($_POST['admin_senha'], $_POST['arquivo_backup_selecionado']) && $_POST['admin_senha'] === SENHA_ADMIN_REAL) {
        $backupSelecionadoNome = basename(sanitize_for_file($_POST['arquivo_backup_selecionado']));
        $diretorioBase = dirname(ARQUIVO_DADOS);
        $caminhoBackupSelecionado = ($diretorioBase === '.' ? '' : $diretorioBase . DIRECTORY_SEPARATOR) . $backupSelecionadoNome;

        if (strpos($backupSelecionadoNome, basename(ARQUIVO_DADOS) . '.bkp.') === 0 && file_exists($caminhoBackupSelecionado)) {
            if (file_exists(ARQUIVO_DADOS) && filesize(ARQUIVO_DADOS) > 0) {
                gerenciarBackups(ARQUIVO_DADOS);
            }
            if (copy($caminhoBackupSelecionado, ARQUIVO_DADOS)) {
                $_SESSION['mensagemSucesso'] = "Backup '" . sanitize_for_html($backupSelecionadoNome) . "' restaurado com sucesso!";
            } else {
                $_SESSION['mensagemErro'] = "Erro ao restaurar o backup '" . sanitize_for_html($backupSelecionadoNome) . "'.";
            }
        } else {
            $_SESSION['mensagemErro'] = "Arquivo de backup '" . sanitize_for_html($backupSelecionadoNome) . "' inválido ou não encontrado.";
        }
    } else {
        $_SESSION['mensagemErro'] = (isset($_POST['admin_senha']) && $_POST['admin_senha'] !== SENHA_ADMIN_REAL)
            ? "Senha administrativa incorreta para recuperação."
            : "Informações insuficientes para recuperação.";
    }
    $_SESSION['exibirModalRecuperacao'] = false;
    $_SESSION['focar_apos_acao'] = true;
    header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}
