<?php
/**
 * Entry point — Cadastro de Crianças (EBI).
 * Suporta modo direto (config.ini em __DIR__) e modo thin stub (INSTANCE_DIR pré-definido).
 */

require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/funcoes.php';

// ── Preview de backup (GET) ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['acao']) && $_GET['acao'] === 'preview_backup'
    && isset($_GET['arquivo'])
) {
    $backupBasename = basename((string)$_GET['arquivo']);
    $dbBase         = DB_INSTANCE_PATH;
    $dbDir          = realpath(dirname($dbBase));

    // O nome deve ser instance.db.bkp.N
    if ($dbDir !== false
        && preg_match('/^instance\.db\.bkp\.\d{1,4}$/', $backupBasename)
    ) {
        $backupFull = $dbDir . DIRECTORY_SEPARATOR . $backupBasename;
        $realBackup = realpath($backupFull);

        if ($realBackup !== false && strpos($realBackup, $dbDir . DIRECTORY_SEPARATOR) === 0 && is_file($realBackup)) {
            try {
                $bkPdo = new PDO('sqlite:' . $realBackup, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $rows = $bkPdo->query(
                    'SELECT id, nome_crianca, nome_responsavel, idade, comum FROM cadastros ORDER BY id DESC LIMIT 3'
                )->fetchAll();

                header('Content-Type: text/plain; charset=utf-8');
                foreach (array_reverse($rows) as $r) {
                    echo $r['id'] . '|' . $r['nome_crianca'] . '|' . $r['nome_responsavel']
                         . '|' . $r['idade'] . '|' . $r['comum'] . "\n";
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo 'Erro ao ler backup SQLite.';
            }
            exit;
        }
    }

    http_response_code(404);
    echo 'Arquivo de backup não encontrado ou inválido.';
    exit;
}

// ── Carrega todos os cadastros (SQLite) ───────────────────────────────────────
$todosOsCadastros      = lerTodosCadastros();
$totalDeCadastrosGeral = count($todosOsCadastros);

$totalCriancas3Anos = 0;
foreach ($todosOsCadastros as $c) {
    if (in_array(trim((string)($c['idade'] ?? '')), ['3', '03'], true)) {
        $totalCriancas3Anos++;
    }
}

// ── Contador "Comum destaque" ─────────────────────────────────────────────────
$comumBaseCadastro = defined('INSTANCE_COMUM') ? trim((string)INSTANCE_COMUM) : '';

$listaPalavrasAdicionais = defined('LISTA_PALAVRAS_CONTADOR_COMUM') ? LISTA_PALAVRAS_CONTADOR_COMUM : '';
$palavrasChaveComumDestaque = montarPalavrasChaveComum($comumBaseCadastro, $listaPalavrasAdicionais);
$nomeComumDestaque  = $comumBaseCadastro !== '' ? $comumBaseCadastro : 'Comum';
$totalComumDestaque = 0;
foreach ($todosOsCadastros as $c) {
    $comumTexto = (string)($c['comum'] ?? '');
    if ($comumTexto !== '' && textoCorrespondePalavrasChave($comumTexto, $palavrasChaveComumDestaque)) {
        $totalComumDestaque++;
    }
}

// ── Mensagens e flags de sessão ───────────────────────────────────────────────
$mensagemSucesso      = $_SESSION['mensagemSucesso']      ?? '';
$mensagemErro         = $_SESSION['mensagemErro']         ?? '';
$exibirModalRecuperacao = $_SESSION['exibirModalRecuperacao'] ?? false;
$scriptsImpressao     = $_SESSION['scripts_impressao']    ?? '';

unset(
    $_SESSION['mensagemSucesso'],
    $_SESSION['mensagemErro'],
    $_SESSION['exibirModalRecuperacao'],
    $_SESSION['scripts_impressao']
);

$backupsDisponiveis = [];
if ($exibirModalRecuperacao) {
    $backupsDisponiveis = listarBackups();
}

$focarPrimeiroCampoAposCadastro = false;
if (!empty($_SESSION['cadastro_realizado_sucesso'])) {
    $focarPrimeiroCampoAposCadastro = true;
    unset($_SESSION['cadastro_realizado_sucesso']);
}

$focarAposAcao = false;
if (!empty($_SESSION['focar_apos_acao'])) {
    $focarAposAcao = true;
    unset($_SESSION['focar_apos_acao']);
}

// ── Processamento de POST ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    require __DIR__ . '/inc/actions.php';
    exit;
}

// ── View de estatísticas ──────────────────────────────────────────────────────
if (($_GET['acao'] ?? '') === 'stats') {
    require __DIR__ . '/views/stats.php';
    exit;
}

require __DIR__ . '/views/main.php';
