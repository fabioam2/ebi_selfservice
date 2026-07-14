<?php
/**
 * Funções de negócio: cadastros (SQLite), backup, ZPL.
 * Depende de db_instance.php (carregado pelo bootstrap via actions/index).
 */

require_once __DIR__ . '/db_instance.php';

// ── Payload de impressora ZPL ─────────────────────────────────────────────────

function obterPayloadDispositivo(): array {
    $printerName = defined('PRINTER_NAME') ? PRINTER_NAME : 'ZDesigner 105SL';
    return [
        'name'         => $printerName,
        'uid'          => $printerName,
        'connection'   => 'driver',
        'deviceType'   => 'printer',
        'version'      => 2,
        'provider'     => 'com.zebra.ds.webdriver.desktop.provider.DefaultDeviceProvider',
        'manufacturer' => 'Zebra Technologies',
    ];
}

// ── Variações de palavra para busca tolerante a erros ────────────────────────

function gerarVariacoesPalavra(string $palavra): array {
    if (empty($palavra)) return [];
    $palavra   = normalizarTextoBusca($palavra);
    if ($palavra === '') return [];
    $variacoes = [$palavra];

    $substituicoes = [['m', 'n'], ['im', 'in']];
    $para_processar = [$palavra];

    foreach ($substituicoes as $par) {
        $novas = [];
        foreach ($para_processar as $p) {
            foreach ([[$par[0], $par[1]], [$par[1], $par[0]]] as [$de, $para]) {
                if (strpos($p, $de) !== false) {
                    $nova = str_replace($de, $para, $p);
                    if (!in_array($nova, $variacoes)) {
                        $variacoes[] = $nova;
                        $novas[]     = $nova;
                    }
                }
            }
        }
        $para_processar = array_merge($para_processar, $novas);
    }

    foreach (array_unique($variacoes) as $v) {
        if (strlen($v) >= 6) {
            $meio = (int)floor(strlen($v) / 2);
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

function normalizarTextoBusca(string $texto): string {
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    if ($texto === '') return '';

    $semAcento = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    if ($semAcento !== false && $semAcento !== '') {
        $texto = $semAcento;
    }

    $texto = preg_replace('/[^a-z0-9]+/i', ' ', $texto);
    $texto = preg_replace('/\s+/', ' ', (string)$texto);
    return trim((string)$texto);
}

function montarPalavrasChaveComum(string $comumBase, string $extrasCsv = ''): array {
    $palavras = [];

    $baseNormalizada = normalizarTextoBusca($comumBase);
    if ($baseNormalizada !== '') {
        foreach (gerarVariacoesPalavra($baseNormalizada) as $v) {
            $vn = normalizarTextoBusca($v);
            if ($vn !== '') $palavras[] = $vn;
        }
    }

    if ($extrasCsv !== '') {
        foreach (array_map('trim', explode(',', $extrasCsv)) as $extra) {
            $en = normalizarTextoBusca($extra);
            if ($en !== '') $palavras[] = $en;
        }
    }

    return array_values(array_unique($palavras));
}

function textoCorrespondePalavrasChave(string $texto, array $palavrasChave): bool {
    $textoNormalizado = normalizarTextoBusca($texto);
    if ($textoNormalizado === '' || empty($palavrasChave)) return false;

    foreach ($palavrasChave as $palavra) {
        $p = normalizarTextoBusca((string)$palavra);
        if ($p !== '' && stripos($textoNormalizado, $p) !== false) {
            return true;
        }
    }
    return false;
}

// ── Backup (arquivo SQLite) ───────────────────────────────────────────────────

function gerenciarBackups(): void {
    db_backup_create();
}

function listarBackups(): array {
    return db_backup_list();
}

// ── Leitura/escrita de cadastros (SQLite) ────────────────────────────────────

function lerTodosCadastros(): array {
    return db_listar_cadastros();
}

function salvarTodosCadastros(array $cadastros): bool {
    // Usada apenas para persistir atualizações em memória (ex: marcar impresso).
    // Com SQLite não há "salvar tudo" — as operações são individuais.
    // Aqui sincronizamos apenas o campo status_impresso para manter compatibilidade.
    try {
        $pdo = ebi_db();
        $pdo->beginTransaction();
        $upd = $pdo->prepare('UPDATE cadastros SET status_impresso = ? WHERE id = ?');
        foreach ($cadastros as $id => $c) {
            $upd->execute([$c['statusImpresso'] ?? 'N', $id]);
        }
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        error_log('[EBI] salvarTodosCadastros: ' . $e->getMessage());
        try { ebi_db()->rollBack(); } catch (Throwable $_) {}
        return false;
    }
}

function gerarCodigoSequencialBase(): int {
    return db_total_cadastros() + 1;
}

function gerarProximoCodResp(): int {
    return db_proximo_cod_resp();
}

// ── Processamento de nomes para ZPL ──────────────────────────────────────────

function processarNomeParaZPL(string $nomeCompleto, int $maxLength = 0): string {
    $nomeCompleto = trim($nomeCompleto);
    if ($nomeCompleto === '' && $maxLength > 0 && $maxLength <= 1) return '';

    $palavras = explode(' ', $nomeCompleto);
    $n        = count($palavras);
    $nome     = $n > 3
        ? $palavras[0] . ' ' . $palavras[1] . ' ' . $palavras[$n - 1]
        : $nomeCompleto;

    if ($maxLength > 0 && mb_strlen($nome, 'UTF-8') > $maxLength) {
        $nome = mb_substr($nome, 0, $maxLength <= 1 ? $maxLength : $maxLength - 1, 'UTF-8');
        if (mb_strlen($nome, 'UTF-8') > $maxLength) {
            $nome = mb_substr($nome, 0, $maxLength, 'UTF-8');
        }
    }
    return $nome;
}

// ── Geração de ZPL ───────────────────────────────────────────────────────────

function gerarCodigoZPL(string $nomeCrianca, string $nomeResponsavel, $idade, $codigo, string $telefone): string {
    $ini_pos = PULSEIRAUTIL - (70 * DOTS);

    $nomeCriancaLimpo     = mb_strtoupper(str_replace(['^','~','\\'], '', processarNomeParaZPL($nomeCrianca,     22)), 'UTF-8');
    $nomeResponsavelLimpo = mb_strtoupper(str_replace(['^','~','\\'], '', processarNomeParaZPL($nomeResponsavel, 25)), 'UTF-8');
    $idadeLimpa           = str_replace(['^','~','\\'], '', (string)$idade);
    $codigoLimpo          = str_replace(['^','~','\\'], '', (string)$codigo);

    $zpl  = "^XA\n^CI28\n^PW192\n";
    $zpl .= "^LL" . (TAMPULSEIRA * DOTS) . "\n";
    $zpl .= "^FO80,{$ini_pos}^A0R,60,50^FD{$nomeCriancaLimpo}^FS\n";
    $zpl .= "^FO50,{$ini_pos}^A0R,30,40^FDIdade: {$idadeLimpa} anos      Cod.:{$codigoLimpo}^FS\n";
    $zpl .= "^FO10,{$ini_pos}^A0R,30,35^FDRsp: {$nomeResponsavelLimpo}^FS\n";
    $fi   = FECHOINI * DOTS;
    $zpl .= "^FO140,{$fi}^A0R,30,35^FD|^FS\n";
    $pu   = PULSEIRAUTIL - 35;
    $zpl .= "^FO140,{$pu}^A0R,30,35^FD|^FS\n";
    $zpl .= "^PQ1,0,1,Y\n^XZ\n";
    return $zpl;
}

function gerarCodigoZPLResponsavel(string $nomeResponsavel, array $nomesCriancas, $codigo): string {
    $ini_pos = PULSEIRAUTIL - (95 * DOTS);
    $id_pos  = $ini_pos + (55 * DOTS);

    $nomeRespLimpo  = mb_strtoupper(str_replace(['^','~','\\'], '', processarNomeParaZPL($nomeResponsavel, 22)), 'UTF-8');
    $codigoLimpo    = str_replace(['^','~','\\'], '', (string)$codigo);

    $criancasZPL = [];
    foreach ($nomesCriancas as $nome) {
        $criancasZPL[] = mb_strtoupper(str_replace(['^','~','\\'], '', processarNomeParaZPL($nome, 25)), 'UTF-8');
    }

    $zpl  = "^XA\n^CI28\n^PW192\n";
    $zpl .= "^LL" . (TAMPULSEIRA * DOTS) . "\n";
    $zpl .= "^FO70,{$id_pos}^A0R,40,45^FDID:{$codigoLimpo}^FS\n";
    $zpl .= "^FO10,{$id_pos}^A0R,20,25^FDRsp:{$nomeRespLimpo}^FS\n";

    $posicoesX = [70, 35, 105, 0, 140];
    for ($k = 0; $k < 5; $k++) {
        $n    = $criancasZPL[$k] ?? '';
        $xPos = $posicoesX[$k];
        $zpl .= "^FO{$xPos},{$ini_pos}^A0R,30,35^FD{$n}^FS\n";
    }
    $fi  = FECHOINI * DOTS;
    $pu  = PULSEIRAUTIL - 35;
    $zpl .= "^FO140,{$fi}^A0R,30,35^FD|^FS\n";
    $zpl .= "^FO140,{$pu}^A0R,30,35^FD|^FS\n";
    $zpl .= "^PQ1,0,1,Y\n^XZ\n";
    return $zpl;
}
