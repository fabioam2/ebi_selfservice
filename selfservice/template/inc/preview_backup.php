<?php
/**
 * Trata a ação GET preview_backup (últimas linhas do arquivo de backup).
 * Se for a ação de preview, envia a resposta e encerra; caso contrário não faz nada.
 */
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
