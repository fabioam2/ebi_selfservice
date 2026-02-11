<?php
/**
 * Entry point - Cadastro de Crianças (EBI).
 * Config e sessão em inc/bootstrap; auth em inc/auth; ações POST em inc/actions; view em views/main.
 */

require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/funcoes.php';

require __DIR__ . '/inc/preview_backup.php';

$todosOsCadastros = lerTodosCadastros(ARQUIVO_DADOS);
$totais = calcular_totais_especiais($todosOsCadastros);
$totalDeCadastrosGeral = $totais['total_geral'];
$totalCriancas3Anos = $totais['total_3_anos'];
$totalBonfim = $totais['total_bonfim'];

$mensagemSucesso = $_SESSION['mensagemSucesso'] ?? '';
$mensagemErro = $_SESSION['mensagemErro'] ?? '';
$exibirModalRecuperacao = $_SESSION['exibirModalRecuperacao'] ?? false;

unset($_SESSION['mensagemSucesso'], $_SESSION['mensagemErro'], $_SESSION['exibirModalRecuperacao']);

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
