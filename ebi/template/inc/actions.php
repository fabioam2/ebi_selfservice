<?php
/**
 * Handlers de POST. CSRF já validado pelo index.php antes de incluir este arquivo.
 * Depende de funcoes.php (ZPL, lerTodosCadastros…) e stats.php — incluídos pelo index.
 */

require_once __DIR__ . '/stats.php';

$urlImpressora = URL_IMPRESSORA;

// ── Cadastrar ─────────────────────────────────────────────────────────────────

if (isset($_POST['cadastrar'])) {
    $nomesCrianca      = $_POST['nome_crianca']      ?? [];
    $nomesResponsavel  = $_POST['nome_responsavel']  ?? [];
    $idades            = $_POST['idade']             ?? [];
    $telefones         = $_POST['telefone']          ?? [];
    $comuns            = $_POST['comum']             ?? [];
    $portariaCadastro  = strtoupper(trim($_POST['portaria_cadastro'] ?? ''));

    $cadastrosOk  = 0;
    $erros        = [];
    $novosParaStats = [];

    if (empty($portariaCadastro) || !preg_match('/^[A-Z]$/', $portariaCadastro)) {
        $erros[] = 'Portaria inválida. Insira uma única letra (A-Z). Nenhum cadastro realizado.';
    } else {
        $codResp      = gerarProximoCodResp();
        $codRespDef   = false;

        try {
            $pdo = ebi_db();
            $pdo->beginTransaction();

            for ($i = 0; $i < NUM_LINHAS_FORMULARIO_CADASTRO; $i++) {
                $nome     = trim($nomesCrianca[$i]     ?? '');
                $resp     = trim($nomesResponsavel[$i]  ?? '');
                $idade    = trim($idades[$i]            ?? '');
                $tel      = trim($telefones[$i]         ?? '');
                $comum    = trim($comuns[$i]            ?? '');

                if ($nome === '' && $resp === '' && $idade === '' && $tel === '' && $comum === '') {
                    continue;
                }

                if ($nome === '' || $resp === '' || $idade === '' || $tel === '' || $comum === '') {
                    $erros[] = 'Linha ' . ($i + 1) . ': Todos os campos são obrigatórios se a linha for preenchida.';
                    continue;
                }

                if (!$codRespDef) {
                    $codRespDef = true;
                }

                db_inserir_cadastro(
                    sanitize_for_file($nome),
                    sanitize_for_file($resp),
                    sanitize_for_file($tel),
                    (int)$idade,
                    sanitize_for_file($comum),
                    $portariaCadastro,
                    $codResp
                );

                $novosParaStats[] = [
                    'idade'    => (int)$idade,
                    'comum'    => sanitize_for_file($comum),
                    'portaria' => $portariaCadastro,
                ];
                $cadastrosOk++;
            }

            $pdo->commit();

        } catch (PDOException $e) {
            try { ebi_db()->rollBack(); } catch (Throwable $_) {}
            $erros[] = 'Erro crítico ao salvar cadastros: ' . $e->getMessage();
            $cadastrosOk = 0;
        }

        // Backup e stats apenas se algo foi inserido
        if ($cadastrosOk > 0) {
            gerenciarBackups();
            stats_on_cadastro($novosParaStats);
        }
    }

    if (!empty($erros)) {
        $fmt = '<strong>Atenção — Erros no Cadastro:</strong><br>' . implode('<br>', $erros);
        if ($cadastrosOk > 0) {
            $_SESSION['mensagemSucesso'] = $cadastrosOk . ' cadastro(s) realizado(s) com sucesso.';
            $_SESSION['mensagemErro']    = $fmt;
        } else {
            $_SESSION['mensagemErro'] = $fmt;
        }
        $_SESSION['focar_apos_acao'] = true;
    } elseif ($cadastrosOk > 0) {
        $_SESSION['mensagemSucesso']          = $cadastrosOk . ' cadastro(s) realizado(s)!';
        $_SESSION['cadastro_realizado_sucesso'] = true;
    }

    header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

// ── Imprimir ──────────────────────────────────────────────────────────────────

if (isset($_POST['imprimir'])) {
    $_SESSION['focar_apos_acao'] = true;

    if (!isset($_POST['selecionados']) || !is_array($_POST['selecionados']) || empty($_POST['selecionados'])) {
        $_SESSION['mensagemErro'] = 'Nenhuma criança selecionada para impressão!';
        header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
        return;
    }

    $todosOsCadastros          = lerTodosCadastros();
    $algumaImpressao           = false;
    $contadorCriancas          = 0;
    $contadorResponsaveis      = 0;
    $scriptsParaExecutar       = '';
    $responsaveisParaEtiquetas = [];
    $idsImpressos              = [];

    foreach ($_POST['selecionados'] as $idStr) {
        $id = (int)$idStr;
        if (!isset($todosOsCadastros[$id])) continue;

        $crianca = $todosOsCadastros[$id];
        $zpl     = gerarCodigoZPL(
            $crianca['nomeCrianca'],
            $crianca['nomeResponsavel'],
            $crianca['idade'],
            $crianca['id'],
            $crianca['telefone']
        );
        $payload = json_encode(['device' => obterPayloadDispositivo(), 'data' => $zpl]);
        if ($payload === false) {
            $_SESSION['mensagemErro'] = ($_SESSION['mensagemErro'] ?? '') . '<br>Erro ao preparar impressão ID ' . $id . '.';
            continue;
        }

        $nomeSafe = addslashes(sanitize_for_html($crianca['nomeCrianca']));
        $scriptsParaExecutar .= "<script>
(function(){
    var url=" . json_encode($urlImpressora) . ";
    var payload={$payload};
    var id={$id};
    var nome=\"{$nomeSafe}\";
    var zpl=payload.data;
    var debug=localStorage.getItem('modoDebugImpressao')==='true';
    var teste=localStorage.getItem('modoTesteImpressao')==='true';
    if(teste){
        var x=parseInt(localStorage.getItem('testeX')||'140');
        var y=parseInt(localStorage.getItem('testeY')||'30');
        var fs=parseInt(localStorage.getItem('testeFontSize')||'20');
        zpl='^XA^CI28^PW192^LL2152^FO '+x+','+y+'^A0R,'+fs+','+fs+'^FD'+nome+' ^FS^PQ1,0,1,Y^XZ';
        payload={device:payload.device,data:zpl};
    }
    if(debug){
        if(!window.debugPrintQueue)window.debugPrintQueue=[];
        window.debugPrintQueue.push({zpl:zpl,url:url,info:{nomeCrianca:nome+(teste?' [TESTE]':''),codigo:id,tipo:teste?'Teste':'Pulseira Criança',urlImpressora:url},id:id});
    }else{
        _ebiPrint(url,payload)
        .then(r=>{if(!r.ok)return r.text().then(t=>{throw new Error(t);});return r.text();})
        .then(()=>{var row=$('tr[data-id=\"'+id+'\"]');if(row.length){row.find('.status-icon').html('<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"18\" height=\"18\" fill=\"green\" viewBox=\"0 0 16 16\"><path d=\"M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z\"/></svg>');row.find('.checkbox-crianca').prop('checked',false);}})
        .catch(e=>console.error('Erro ID '+id,e));
    }
})();
</script>";

        $idsImpressos[] = $id;
        $contadorCriancas++;
        $algumaImpressao = true;

        $codResp = $crianca['cod_resp'];
        if (!empty($codResp)) {
            if (!isset($responsaveisParaEtiquetas[$codResp])) {
                $responsaveisParaEtiquetas[$codResp] = ['nomeResponsavel' => $crianca['nomeResponsavel'], 'criancas' => []];
            }
            $responsaveisParaEtiquetas[$codResp]['criancas'][] = $crianca['nomeCrianca'];
        }
    }

    if ($algumaImpressao) {
        // Marcar como impresso no SQLite
        db_marcar_impresso($idsImpressos);
        gerenciarBackups();
        stats_on_impressao($contadorCriancas);

        // Etiquetas de responsáveis
        foreach ($responsaveisParaEtiquetas as $codResp => $dataResp) {
            if (empty($dataResp['criancas'])) continue;

            $zplResp = gerarCodigoZPLResponsavel($dataResp['nomeResponsavel'], $dataResp['criancas'], $codResp);
            $payloadResp = json_encode(['device' => obterPayloadDispositivo(), 'data' => $zplResp]);
            if ($payloadResp === false) continue;

            $nomeResp = addslashes(sanitize_for_html($dataResp['nomeResponsavel']));
            $codRespSafe = addslashes(sanitize_for_html((string)$codResp));
            $scriptsParaExecutar .= "<script>
(function(){
    var url=" . json_encode($urlImpressora) . ";
    var payload={$payloadResp};
    var codResp=\"{$codRespSafe}\";
    var nome=\"{$nomeResp}\";
    var zpl=payload.data;
    var debug=localStorage.getItem('modoDebugImpressao')==='true';
    var teste=localStorage.getItem('modoTesteImpressao')==='true';
    if(teste)return;
    if(debug){
        if(!window.debugPrintQueue)window.debugPrintQueue=[];
        window.debugPrintQueue.push({zpl:zpl,url:url,info:{nomeCrianca:'Responsável: '+nome,codigo:codResp,tipo:'Pulseira Responsável',urlImpressora:url},codResp:codResp});
    }else{
        _ebiPrint(url,payload)
        .then(r=>{if(!r.ok)return r.text().then(t=>{throw new Error(t);});return r.text();})
        .then(()=>console.log('Resp '+codResp+' enviado'))
        .catch(e=>console.error('Erro resp '+codResp,e));
    }
})();
</script>";
            $contadorResponsaveis++;
        }

        $scriptsParaExecutar .= "<script>
(function(){
    $(document).ready(function(){
        setTimeout(function(){if($('.checkbox-crianca:checked').length===0)$('#selecionarTodos').prop('checked',false);},2000);
        if(localStorage.getItem('modoDebugImpressao')==='true'&&window.debugPrintQueue&&window.debugPrintQueue.length>0){
            var idx=0;
            function show(){if(idx<window.debugPrintQueue.length){var item=window.debugPrintQueue[idx];abrirModalDebugZPL(item.zpl,item.info);$('#modalDebugZPL').off('hidden.bs.modal').on('hidden.bs.modal',function(){idx++;if(idx<window.debugPrintQueue.length)setTimeout(show,500);else window.debugPrintQueue=[];});}}
            setTimeout(show,1000);
        }
    });
})();
</script>";

        $msg = $contadorCriancas . ' etiqueta(s) de criança(s) processada(s).';
        if ($contadorResponsaveis > 0) $msg .= ' ' . $contadorResponsaveis . ' responsável(is) também.';
        $_SESSION['mensagemSucesso'] = ($_SESSION['mensagemSucesso'] ?? '') . $msg;
    } elseif (empty($_SESSION['mensagemErro'])) {
        $_SESSION['mensagemErro'] = 'Nenhuma criança válida nos IDs selecionados.';
    }

    $_SESSION['scripts_impressao'] = $scriptsParaExecutar;
    header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

// ── Apagar linha ──────────────────────────────────────────────────────────────

if (isset($_POST['acao']) && $_POST['acao'] === 'apagar_linha') {
    $id = (int)($_POST['id_para_apagar'] ?? 0);
    if ($id > 0) {
        $todos = lerTodosCadastros();
        if (isset($todos[$id])) {
            $nome = $todos[$id]['nomeCrianca'];
            gerenciarBackups();
            db_apagar_cadastro($id);
            $_SESSION['mensagemSucesso'] = "Cadastro de '" . sanitize_for_html($nome) . "' (ID: {$id}) apagado.";
        } else {
            $_SESSION['mensagemErro']    = "Cadastro ID {$id} não encontrado.";
            $_SESSION['focar_apos_acao'] = true;
        }
    } else {
        $_SESSION['mensagemErro']    = 'ID para apagar não especificado.';
        $_SESSION['focar_apos_acao'] = true;
    }
    header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

// ── Zerar cadastros ───────────────────────────────────────────────────────────

if (isset($_POST['zerar_arquivo_confirmado'])) {
    $senhaOk = verificar_senha_admin($_POST['admin_senha'] ?? '');

    if ($senhaOk) {
        gerenciarBackups();
        db_zerar_cadastros();
        $_SESSION['mensagemSucesso'] = 'Cadastros zerados com sucesso! Backup gerado antes da zeragem.';
    } else {
        $_SESSION['mensagemErro']    = 'Senha administrativa incorreta.';
        $_SESSION['focar_apos_acao'] = true;
    }
    header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

// ── Flags de modal ────────────────────────────────────────────────────────────

if (isset($_POST['preparar_recuperacao'])) {
    $_SESSION['exibirModalRecuperacao'] = true;
    $_SESSION['focar_apos_acao']        = true;
    header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

if (isset($_POST['limpar_flag_modal_recuperacao'])) {
    $_SESSION['exibirModalRecuperacao'] = false;
    $_SESSION['focar_apos_acao']        = true;
    header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

// ── Restaurar backup ──────────────────────────────────────────────────────────

if (isset($_POST['confirmar_recuperacao'])) {
    $senhaOk = verificar_senha_admin($_POST['admin_senha'] ?? '');

    if ($senhaOk && isset($_POST['arquivo_backup_selecionado'])) {
        $backupNome = basename((string)$_POST['arquivo_backup_selecionado']);
        if (db_backup_restore($backupNome)) {
            $_SESSION['mensagemSucesso'] = "Backup '" . sanitize_for_html($backupNome) . "' restaurado com sucesso!";
        } else {
            $_SESSION['mensagemErro'] = "Erro ao restaurar o backup '" . sanitize_for_html($backupNome) . "'. Arquivo inválido ou inacessível.";
        }
    } else {
        $_SESSION['mensagemErro'] = $senhaOk
            ? 'Informações insuficientes para recuperação.'
            : 'Senha administrativa incorreta para recuperação.';
    }

    $_SESSION['exibirModalRecuperacao'] = false;
    $_SESSION['focar_apos_acao']        = true;
    header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

// ── Configurações da impressora ───────────────────────────────────────────────

if (isset($_POST['alterar_senha_instancia'])) {
    $senhaAtual = (string)($_POST['senha_atual'] ?? '');
    $novaSenha  = (string)($_POST['nova_senha'] ?? '');
    $confirmar  = (string)($_POST['confirmar_nova_senha'] ?? '');

    if (!verificar_senha_admin($senhaAtual)) {
        $_SESSION['mensagemErro'] = 'Senha atual incorreta.';
    } elseif (strlen($novaSenha) < 8) {
        $_SESSION['mensagemErro'] = 'A nova senha deve ter ao menos 8 caracteres.';
    } elseif (!hash_equals($novaSenha, $confirmar)) {
        $_SESSION['mensagemErro'] = 'A confirmação da nova senha não confere.';
    } else {
        $config_file = CAMINHO_CONFIG_INI;
        $conteudo = file_get_contents($config_file);

        if ($conteudo === false) {
            $_SESSION['mensagemErro'] = 'Erro ao ler config.ini para alterar a senha.';
        } else {
            $novoHash = password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12]);
            $substituicoes = [
                '/^\s*SENHA_ADMIN_HASH\s*=.*$/mi'  => 'SENHA_ADMIN_HASH = "' . $novoHash . '"',
                '/^\s*SENHA_PAINEL_HASH\s*=.*$/mi' => 'SENHA_PAINEL_HASH = "' . $novoHash . '"',
                '/^\s*SENHA_ADMIN_REAL\s*=.*$/mi'  => 'SENHA_ADMIN_REAL = ""',
                '/^\s*SENHA_PAINEL\s*=.*$/mi'      => 'SENHA_PAINEL = ""',
            ];

            foreach ($substituicoes as $pattern => $replacement) {
                $conteudo = preg_replace($pattern, $replacement, $conteudo);
            }

            if (file_put_contents($config_file, $conteudo, LOCK_EX) !== false) {
                @chmod($config_file, 0600);
                $_SESSION['mensagemSucesso'] = 'Senha da instância alterada com sucesso!';
            } else {
                $_SESSION['mensagemErro'] = 'Erro ao salvar nova senha em config.ini.';
            }
        }
    }

    $_SESSION['focar_apos_acao'] = true;
    header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}

if (isset($_POST['salvar_config_impressora'])) {
    $senhaOk = verificar_senha_admin($_POST['admin_senha'] ?? '');

    if ($senhaOk) {
        $config_file = CAMINHO_CONFIG_INI;
        $conteudo    = file_get_contents($config_file);

        if ($conteudo !== false) {
            $cidadeInstancia = trim((string)($_POST['config_cidade_instancia'] ?? ''));
            $comumInstancia  = trim((string)($_POST['config_comum_instancia'] ?? ''));

            $campos = [
                'PRINTER_NAME'                  => trim($_POST['config_printer_name']                  ?? 'ZDesigner 105SL'),
                // Sempre sincroniza a base de contagem com a COMUM da instância.
                'PALAVRA_CONTADOR_COMUM'        => $comumInstancia,
                'LISTA_PALAVRAS_CONTADOR_COMUM' => trim($_POST['config_lista_palavras_contador_comum'] ?? ''),
                'URL_IMPRESSORA'                => trim($_POST['config_url_impressora']                ?? 'http://127.0.0.1:9100/write'),
            ];
            $intCampos = [
                'TAMPULSEIRA'   => (int)($_POST['config_tampulseira']    ?? 269),
                'DOTS'          => (int)($_POST['config_dots']           ?? 8),
                'FECHO'         => (int)($_POST['config_fecho']          ?? 30),
                'FECHOINI'      => (int)($_POST['config_fechoini']       ?? 1),
                'LARGURA_PULSEIRA' => (int)($_POST['config_largura_pulseira'] ?? 192),
            ];

            foreach ($campos as $key => $val) {
                $conteudo = preg_replace('/^' . preg_quote($key, '/') . '\s*=\s*.+$/m', $key . ' = "' . addslashes($val) . '"', $conteudo);
            }
            foreach ($intCampos as $key => $val) {
                $conteudo = preg_replace('/^' . preg_quote($key, '/') . '\s*=\s*.+$/m', $key . ' = ' . $val, $conteudo);
            }

            if ($cidadeInstancia !== '') {
                $conteudo = preg_replace('/^CIDADE\s*=\s*.+$/m', 'CIDADE = "' . addslashes($cidadeInstancia) . '"', $conteudo);
            }
            if ($comumInstancia !== '') {
                $conteudo = preg_replace('/^COMUM\s*=\s*.+$/m', 'COMUM = "' . addslashes($comumInstancia) . '"', $conteudo);
            }

            if (file_put_contents($config_file, $conteudo, LOCK_EX) !== false) {
                $_SESSION['mensagemSucesso'] = 'Configurações da impressora salvas! Efeito no próximo acesso.';
            } else {
                $_SESSION['mensagemErro'] = 'Erro ao salvar config.ini.';
            }
        } else {
            $_SESSION['mensagemErro'] = 'Erro ao ler config.ini.';
        }
    } else {
        $_SESSION['mensagemErro'] = 'Senha administrativa incorreta.';
    }

    $_SESSION['focar_apos_acao'] = true;
    header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
    return;
}
