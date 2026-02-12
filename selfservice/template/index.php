<?php
/**
 * Entry point - Cadastro de Crianças (EBI).
 * Config e sessão em inc/bootstrap; auth em inc/auth; ações POST em inc/actions; view em views/main.
 */

require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/funcoes.php';

// Preview de backup (GET) — apenas logado
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao']) && $_GET['acao'] === 'preview_backup' && isset($_GET['arquivo'])) {
    $nomeArquivoBackup = basename(sanitize_for_file($_GET['arquivo']));
    $diretorioBase = dirname(ARQUIVO_DADOS);
    $caminhoCompletoBackup = ($diretorioBase === '.' ? '' : $diretorioBase . DIRECTORY_SEPARATOR) . $nomeArquivoBackup;

    if (strpos($nomeArquivoBackup, basename(ARQUIVO_DADOS) . '.bkp.') === 0 && file_exists($caminhoCompletoBackup)) {
        $linhas = file($caminhoCompletoBackup, FILE_IGNORE_NEW_LINES);
        if ($linhas !== false) {
            $ultimasLinhas = array_slice($linhas, -3);
            header('Content-Type: text/plain; charset=utf-8');
            echo implode("\n", $ultimasLinhas);
        } else {
            http_response_code(500);
            echo "Erro ao ler o arquivo de backup.";
        }
    } else {
        http_response_code(404);
        echo "Arquivo de backup não encontrado ou inválido: " . sanitize_for_html($nomeArquivoBackup);
    }
    exit;
}

$todosOsCadastros = lerTodosCadastros(ARQUIVO_DADOS);
$totalDeCadastrosGeral = count($todosOsCadastros);

$totalCriancas3Anos = 0;
foreach ($todosOsCadastros as $cadastro) {
    if (isset($cadastro['idade']) && in_array(trim($cadastro['idade']), ['3', '03'], true)) {
        $totalCriancas3Anos++;
    }
}

// Contador "Comum" - fixo, usando o comum do usuário da instância
$palavrasChaveComumDestaque = [];
$comumUsuario = $config['INFO_USUARIO']['COMUM'] ?? '';
if (!empty($comumUsuario)) {
    $palavrasChaveComumDestaque = [strtolower(trim($comumUsuario))];
}
$nomeComumDestaque = 'Comum'; // Nome fixo
$totalComumDestaque = 0;
if (!empty($palavrasChaveComumDestaque)) {
    foreach ($todosOsCadastros as $cadastro) {
        if (isset($cadastro['comum']) && trim($cadastro['comum']) !== '') {
            $comumLower = strtolower(trim($cadastro['comum']));
            foreach ($palavrasChaveComumDestaque as $palavra) {
                if (stripos($comumLower, $palavra) !== false) {
                    $totalComumDestaque++;
                    break;
                }
            }
        }
    }
}

$mensagemSucesso = $_SESSION['mensagemSucesso'] ?? '';
$mensagemErro = $_SESSION['mensagemErro'] ?? '';
$exibirModalRecuperacao = $_SESSION['exibirModalRecuperacao'] ?? false;
$scriptsImpressao = $_SESSION['scripts_impressao'] ?? '';

unset($_SESSION['mensagemSucesso'], $_SESSION['mensagemErro'], $_SESSION['exibirModalRecuperacao'], $_SESSION['scripts_impressao']);

$backupsDisponiveis = [];
if ($exibirModalRecuperacao) {
    $backupsDisponiveis = listarBackups(ARQUIVO_DADOS);
}

$focarPrimeiroCampoAposCadastro = false;
if (isset($_SESSION['cadastro_realizado_sucesso']) && $_SESSION['cadastro_realizado_sucesso']) {
    $focarPrimeiroCampoAposCadastro = true;
    unset($_SESSION['cadastro_realizado_sucesso']);
}

$focarAposAcao = false;
if (isset($_SESSION['focar_apos_acao']) && $_SESSION['focar_apos_acao']) {
    $focarAposAcao = true;
    unset($_SESSION['focar_apos_acao']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    require __DIR__ . '/inc/actions.php';
    exit;
}

if (!empty($mensagemErro) && !isset($_SESSION['focar_apos_acao']) && !isset($_SESSION['cadastro_realizado_sucesso'])) {
    $_SESSION['focar_apos_acao'] = true;
}

require __DIR__ . '/views/main.php';
