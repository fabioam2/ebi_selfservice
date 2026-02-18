<?php
/**
 * Funções de negócio: backup, cadastros, ZPL.
 */

/**
 * Retorna o payload do dispositivo de impressora padrão para chamadas ZPL.
 */
function obterPayloadDispositivo() {
    // Obter nome da impressora do config.ini (fallback para padrão se não configurado)
    $printerName = defined('PRINTER_NAME') ? PRINTER_NAME : 'ZDesigner 105SL';

    return [
        "name" => $printerName,
        "uid" => $printerName,
        "connection" => "driver",
        "deviceType" => "printer",
        "version" => 2,
        "provider" => "com.zebra.ds.webdriver.desktop.provider.DefaultDeviceProvider",
        "manufacturer" => "Zebra Technologies"
    ];
}

/**
 * Gera variações similares de uma palavra para busca tolerante a erros de digitação.
 *
 * Exemplos de variações geradas:
 * - Troca m por n e vice-versa (comum em erros de digitação)
 * - Troca terminações im por in e vice-versa
 * - Adiciona espaços em posições estratégicas
 *
 * @param string $palavra Palavra base (ex: "bonfim")
 * @return array Lista de variações similares
 *
 * Exemplo: gerarVariacoesPalavra("bonfim") retorna:
 * ["bonfim", "bofim", "bonfin", "bomfim", "bon fim", "bom fin", "bom fim", "bon fin"]
 */
function gerarVariacoesPalavra($palavra) {
    if (empty($palavra)) {
        return [];
    }

    $palavra = strtolower(trim($palavra));
    $variacoes = [$palavra];

    // Substituições de caracteres comuns em erros de digitação
    $substituicoes = [
        ['m', 'n'],  // m ↔ n
        ['im', 'in'], // im ↔ in
    ];

    // Gerar variações com substituições
    $palavrasParaProcessar = [$palavra];

    foreach ($substituicoes as $par) {
        $novasPalavras = [];
        foreach ($palavrasParaProcessar as $p) {
            // Substituir par[0] por par[1]
            if (strpos($p, $par[0]) !== false) {
                $nova = str_replace($par[0], $par[1], $p);
                if (!in_array($nova, $variacoes)) {
                    $variacoes[] = $nova;
                    $novasPalavras[] = $nova;
                }
            }
            // Substituir par[1] por par[0]
            if (strpos($p, $par[1]) !== false) {
                $nova = str_replace($par[1], $par[0], $p);
                if (!in_array($nova, $variacoes)) {
                    $variacoes[] = $nova;
                    $novasPalavras[] = $nova;
                }
            }
        }
        $palavrasParaProcessar = array_merge($palavrasParaProcessar, $novasPalavras);
    }

    // Gerar variações com espaços (para palavras compostas)
    // Procura por padrões como "bonfim" → "bon fim"
    $todasVariacoes = array_unique($variacoes);
    foreach ($todasVariacoes as $v) {
        // Se a palavra tem 6+ caracteres, tenta adicionar espaço no meio
        if (strlen($v) >= 6) {
            $meio = floor(strlen($v) / 2);

            // Tenta posições próximas ao meio
            for ($offset = -1; $offset <= 1; $offset++) {
                $pos = $meio + $offset;
                if ($pos > 0 && $pos < strlen($v)) {
                    $comEspaco = substr($v, 0, $pos) . ' ' . substr($v, $pos);
                    if (!in_array($comEspaco, $variacoes)) {
                        $variacoes[] = $comEspaco;
                    }
                }
            }
        }
    }

    return array_unique($variacoes);
}

function gerenciarBackups($caminhoArquivoBase) {
    if (!file_exists($caminhoArquivoBase) || filesize($caminhoArquivoBase) === 0) {
        return;
    }
    for ($i = MAX_BACKUPS; $i >= 1; $i--) {
        $backupAtual = $caminhoArquivoBase . '.bkp.' . $i;
        $backupProximo = $caminhoArquivoBase . '.bkp.' . ($i + 1);
        if (file_exists($backupAtual)) {
            if ($i == MAX_BACKUPS) {
                @unlink($backupAtual);
            } else {
                @rename($backupAtual, $backupProximo);
            }
        }
    }
    @copy($caminhoArquivoBase, $caminhoArquivoBase . '.bkp.1');
}

function listarBackups($caminhoArquivoBase) {
    $backups = [];
    for ($i = 1; $i <= MAX_BACKUPS; $i++) {
        $bkpFile = $caminhoArquivoBase . '.bkp.' . $i;
        if (file_exists($bkpFile)) {
            $backups[] = basename($bkpFile);
        }
    }
    return $backups;
}

function lerTodosCadastros($caminhoArquivo) {
    $cadastros = [];
    if (file_exists($caminhoArquivo) && filesize($caminhoArquivo) > 0) {
        $linhasFile = file($caminhoArquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($linhasFile === false) return [];
        foreach ($linhasFile as $linha) {
            // Ignorar linhas de comentário
            if (isset($linha[0]) && $linha[0] === '#') continue;
            $dados = explode(DELIMITADOR, $linha);
            if (count($dados) >= (NUM_CAMPOS_POR_LINHA_NO_ARQUIVO + 1)) {
                $id = intval(trim($dados[0]));
                $cadastros[$id] = [
                    'id' => $id,
                    'nomeCrianca'     => $dados[1] ?? '',
                    'nomeResponsavel' => $dados[2] ?? '',
                    'telefone'        => $dados[3] ?? '',
                    'idade'           => $dados[4] ?? '',
                    'comum'           => $dados[5] ?? '',
                    'statusImpresso'  => $dados[6] ?? 'N',
                    'portaria'        => strtoupper(trim($dados[7] ?? '')),
                    'cod_resp'        => $dados[8] ?? ''
                ];
            }
        }
    }
    return $cadastros;
}

function gerarCodigoSequencialBase($caminhoArquivo) {
    $cadastrosAtuais = lerTodosCadastros($caminhoArquivo);
    $ultimo_id = empty($cadastrosAtuais) ? 0 : max(array_keys($cadastrosAtuais));
    return $ultimo_id + 1;
}

function gerarProximoCodResp($caminhoArquivo) {
    $cadastros = lerTodosCadastros($caminhoArquivo);
    $ultimoCodResp = 0;
    foreach ($cadastros as $cadastro) {
        if (isset($cadastro['cod_resp']) && is_numeric($cadastro['cod_resp'])) {
            $ultimoCodResp = max($ultimoCodResp, intval($cadastro['cod_resp']));
        }
    }
    return $ultimoCodResp + 1;
}

function processarNomeParaZPL($nomeCompleto, $maxLength = 0) {
    $nomeCompleto = trim((string)$nomeCompleto);
    if (empty($nomeCompleto) && $maxLength > 0 && $maxLength <= 1) return '';

    $palavras = explode(' ', $nomeCompleto);
    $numPalavras = count($palavras);
    $nomeProcessado = $nomeCompleto;

    if ($numPalavras > 3) {
        $nomeProcessado = $palavras[0] . ' ' . $palavras[1] . ' ' . $palavras[$numPalavras - 1];
    }

    if ($maxLength > 0) {
        if (mb_strlen($nomeProcessado, 'UTF-8') > $maxLength) {
            $nomeProcessado = $maxLength <= 1
                ? mb_substr($nomeProcessado, 0, $maxLength, 'UTF-8')
                : mb_substr($nomeProcessado, 0, $maxLength - 1, 'UTF-8');
            if (mb_strlen($nomeProcessado, 'UTF-8') > $maxLength) {
                $nomeProcessado = mb_substr($nomeProcessado, 0, $maxLength, 'UTF-8');
            }
        }
    }
    return $nomeProcessado;
}

function gerarCodigoZPL($nomeCrianca, $nomeResponsavel, $idade, $codigo, $telefone) {
    $ini_pos = PULSEIRAUTIL - (70 * DOTS);

    $nomeCriancaProcessado = processarNomeParaZPL($nomeCrianca, 22);
    $nomeResponsavelProcessado = processarNomeParaZPL($nomeResponsavel, 25);

    $nomeCriancaLimpo = mb_strtoupper(str_replace(['^', '~', '\\'], '', $nomeCriancaProcessado), 'UTF-8');
    $nomeResponsavelLimpo = mb_strtoupper(str_replace(['^', '~', '\\'], '', $nomeResponsavelProcessado), 'UTF-8');
    $idadeLimpa = str_replace(['^', '~', '\\'], '', $idade);
    $codigoLimpo = str_replace(['^', '~', '\\'], '', $codigo);

    $zpl = "^XA" . PHP_EOL;
    $zpl .= "^CI28" . PHP_EOL;
    $zpl .= "^PW192" . PHP_EOL;
    $zpl .= "^LL" . (TAMPULSEIRA * DOTS) . PHP_EOL;
    $zpl .= "^FO80," . $ini_pos . "^A0R,60,50^FD" . $nomeCriancaLimpo . "^FS" . PHP_EOL;
    $zpl .= "^FO50," . $ini_pos . "^A0R,30,40^FDIdade: " . $idadeLimpa . " anos      Cod.:" . $codigoLimpo . "^FS" . PHP_EOL;
    $zpl .= "^FO10," . $ini_pos . "^A0R,30,35^FDRsp: " . $nomeResponsavelLimpo . "^FS" . PHP_EOL;
    $zpl .= "^FO140," . (FECHOINI*DOTS) . "^A0R,30,35^FD|^FS" . PHP_EOL;
    $zpl .= "^FO140," . (PULSEIRAUTIL - 35) . "^A0R,30,35^FD|^FS" . PHP_EOL;

    $zpl .= "^PQ1,0,1,Y" . PHP_EOL;
    $zpl .= "^XZ" . PHP_EOL;
    return $zpl;
}

function gerarCodigoZPLResponsavel($nomeResponsavel, $nomesCriancasDoGrupo, $codigo) {
    $ini_pos = PULSEIRAUTIL - (95 * DOTS);
    $id_pos = $ini_pos + (55 * DOTS);
    $yPosCriancas = $ini_pos;

    $nomeResponsavelProcessado = processarNomeParaZPL($nomeResponsavel, 22);
    $nomeResponsavelLimpo = mb_strtoupper(str_replace(['^', '~', '\\'], '', $nomeResponsavelProcessado), 'UTF-8');
    $codigoLimpo = str_replace(['^', '~', '\\'], '', $codigo);

    $nomesCriancasLimpasEProcessadas = [];
    foreach ($nomesCriancasDoGrupo as $nomeCrianca) {
        $nomeCriancaProcessado = processarNomeParaZPL($nomeCrianca, 25);
        $nomesCriancasLimpasEProcessadas[] = mb_strtoupper(str_replace(['^', '~', '\\'], '', $nomeCriancaProcessado), 'UTF-8');
    }

    $zpl = "^XA" . PHP_EOL;
    $zpl .= "^CI28" . PHP_EOL;
    $zpl .= "^PW192" . PHP_EOL;
    $zpl .= "^LL" . (TAMPULSEIRA * DOTS) . PHP_EOL;
    $zpl .= "^FH" . PHP_EOL;
    $zpl .= "^FO70," . $id_pos . "^A0R,40,45^FDID:" . $codigoLimpo . "^FS" . PHP_EOL;
    $zpl .= "^FO10," . $id_pos . "^A0R,20,25^FDRsp:" . $nomeResponsavelLimpo . "^FS" . PHP_EOL;

    $posicoesX = [70, 35, 105, 0, 140];
    for ($k = 0; $k < 5; $k++) {
        $nomeParaExibir = $nomesCriancasLimpasEProcessadas[$k] ?? '';
        $zpl .= "^FO" . $posicoesX[$k] . "," . $yPosCriancas . "^A0R,30,35^FD" . $nomeParaExibir . "^FS" . PHP_EOL;
    }
    $zpl .= "^FO140," . (FECHOINI*DOTS) . "^A0R,30,35^FD|^FS" . PHP_EOL;
    $zpl .= "^FO140," . (PULSEIRAUTIL-35) . "^A0R,30,35^FD|^FS" . PHP_EOL;
    $zpl .= "^PQ1,0,1,Y" . PHP_EOL;
    $zpl .= "^XZ" . PHP_EOL;
    return $zpl;
}

function salvarTodosCadastros($caminhoArquivo, $cadastros) {
    $linhasParaSalvar = [];
    foreach ($cadastros as $cadastro) {
        $linhasParaSalvar[] = implode(DELIMITADOR, [
            $cadastro['id'],
            $cadastro['nomeCrianca'],
            $cadastro['nomeResponsavel'],
            $cadastro['telefone'],
            $cadastro['idade'],
            $cadastro['comum'],
            $cadastro['statusImpresso'] ?? 'N',
            strtoupper(trim($cadastro['portaria'] ?? '')),
            $cadastro['cod_resp'] ?? ''
        ]);
    }
    $conteudo = implode(PHP_EOL, $linhasParaSalvar) . (count($linhasParaSalvar) > 0 ? PHP_EOL : '');
    return file_put_contents($caminhoArquivo, $conteudo, LOCK_EX) !== false;
}
