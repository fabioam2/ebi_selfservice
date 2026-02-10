<?php

// 1. VERIFICAR SE ESTÁ EXECUTANDO VIA LINHA DE COMANDO
if (php_sapi_name() !== 'cli') {
    die("Este script deve ser executado apenas pela linha de comando (CLI).\n");
}

// 2. CONSTANTES
// O caminho absoluto foi mantido.
define('ARQUIVO_DADOS', '/home/u154867272/domains/ebi.ccbcampinas.org.br/public_html/sumare/ebi/cadastro_criancas.txt'); 
define('MAX_BACKUPS', 10); 
// Valores de tempo (mantido 60 segundos para testes)
define('TEMPO_MINIMO_SEGUNDOS', 24 * 60 * 60); 
//define('TEMPO_MINIMO_SEGUNDOS', 60); 

/**
 * Cria um backup do arquivo principal com data e hora completas no mesmo diretório.
 * @param string $caminhoArquivoBase O caminho absoluto para o arquivo principal.
 * @return string Mensagem de status da operação.
 */
function criarBackupComDataHora($caminhoArquivoBase) {
    if (!file_exists($caminhoArquivoBase) || filesize($caminhoArquivoBase) === 0) {
//        return "Aviso: Arquivo principal não existe ou está vazio. Backup não criado antes da exclusão.";
        return "Bkp não criado.";
    }

    // 1. Obtém o diretório do arquivo 
    $diretorio = dirname($caminhoArquivoBase);
    // 2. Obtém apenas o nome do arquivo 
    $nomeBase = basename($caminhoArquivoBase);
    
    // Formato: YYYYMMDD_HHMMSS
    $dataHora = date('Ymd_His');
    
    // Constrói o novo nome do arquivo de backup
    $nomeBackupDetalhado = $nomeBase . '.backup.' . $dataHora . '.bkp';
    $caminhoBackup = $diretorio . DIRECTORY_SEPARATOR . $nomeBackupDetalhado;

    if (@copy($caminhoArquivoBase, $caminhoBackup)) {
        // Exibe apenas o nome do arquivo de backup, não o caminho completo para a saída CLI
        return "Backup criado: " . $nomeBackupDetalhado;
    } else {
        return "ERRO GRAVE: Falha ao criar o backup detalhado no diretório: " . $diretorio;
    }
}


/**
 * Retorna a data e hora de última modificação do arquivo ou uma string vazia se não existir.
 * @param string $caminhoArquivo O caminho para o arquivo.
 * @return string A data e hora formatada.
 */
function obterDataModificacaoFormatada($caminhoArquivo) {
    if (!file_exists($caminhoArquivo)) {
        return "";
    }
    $timestampModificacao = filemtime($caminhoArquivo);
    // Formato dia/mês/ano hora:minuto:segundo
    return date('d/m/Y H:i:s', $timestampModificacao);
}

/**
 * Verifica se um arquivo existe e se sua última modificação (ou criação) 
 * é mais antiga do que o TEMPO_MINIMO_SEGUNDOS.
 * @param string $caminhoArquivo O caminho para o arquivo.
 * @return bool Retorna true se o arquivo for mais antigo que o limite, false caso contrário ou se não existir.
 */
function arquivoTemMaisDe24Horas($caminhoArquivo) {
    if (!file_exists($caminhoArquivo)) {
        return false;
    }
    
    // Obtém o timestamp da última modificação (mtime)
    $timestampModificacao = filemtime($caminhoArquivo);
    
    // Calcula o limite de tempo (agora menos o tempo mínimo)
    $limiteTempo = time() - TEMPO_MINIMO_SEGUNDOS;
    
    // Retorna true se o timestamp de modificação for menor que o limite (ou seja, mais antigo)
    return $timestampModificacao < $limiteTempo;
}


// 3. FUNÇÃO DE EXCLUSÃO DE ARQUIVOS AJUSTADA
function limparArquivosDeDados() {
    // A chamada de backup detalhado FOI REMOVIDA daqui. Ela agora é condicional.

    $arquivosRemovidos = [];
    $arquivosNaoRemovidos = [];
    $arquivosRecentesIgnorados = [];
    
    // Alerta sobre o tempo de teste
    $msgTempo = TEMPO_MINIMO_SEGUNDOS === (24 * 60 * 60) 
        ? "mais de 24 horas" 
        : "mais de " . TEMPO_MINIMO_SEGUNDOS . " segundos (Modo Teste)";
    
//    echo "Iniciando a limpeza de arquivos de dados com {$msgTempo}...\n";
    echo "Limpando arquivos com {$msgTempo}...\n";
    
    // a) Lista de arquivos a serem considerados para exclusão/zeramento
    $nomeBaseArquivo = basename(ARQUIVO_DADOS);
    $diretorioArquivo = dirname(ARQUIVO_DADOS);

    $arquivosParaVerificar = [];
    
    // O próprio arquivo de dados
    $arquivosParaVerificar[] = ARQUIVO_DADOS; 
    
    // Backups numerados e sem número
    for ($i = 1; $i <= MAX_BACKUPS + 1; $i++) {
        $arquivosParaVerificar[] = $diretorioArquivo . DIRECTORY_SEPARATOR . $nomeBaseArquivo . '.bkp.' . $i;
    }
    $arquivosParaVerificar[] = $diretorioArquivo . DIRECTORY_SEPARATOR . $nomeBaseArquivo . '.bkp'; 

    $bkpRemovidos = 0;
    
    foreach ($arquivosParaVerificar as $caminhoCompletoArquivo) {
        if (!file_exists($caminhoCompletoArquivo)) {
            continue;
        }

        $dataModificacao = obterDataModificacaoFormatada($caminhoCompletoArquivo);
        $nomeArquivoCurto = basename($caminhoCompletoArquivo); // Nome curto para a saída

        if (arquivoTemMaisDe24Horas($caminhoCompletoArquivo)) {
            
            // Se for o arquivo principal, executa o zeramento e remoção
            if ($caminhoCompletoArquivo === ARQUIVO_DADOS) {
                
                // ------------------------------------------------------------------
                // PASSO CONDICIONAL: CRIA O BACKUP DETALHADO ANTES DE EXCLUIR/ZERAR
                // ------------------------------------------------------------------
                $statusBackup = criarBackupComDataHora(ARQUIVO_DADOS);
                echo $statusBackup . "\n";
                // ------------------------------------------------------------------
                
 //               // Tenta zerar o arquivo antes de remover (boas práticas)
 //               if (file_put_contents($caminhoCompletoArquivo, "") !== false) {
 //                   echo "Aviso: Arquivo principal (" . $nomeArquivoCurto . ") foi zerado antes de tentar a remoção.\n";
//                }
                
                if (@unlink($caminhoCompletoArquivo)) {
                    $arquivosRemovidos[] = $nomeArquivoCurto . " (removido, Data: " . $dataModificacao . ")";
                } else {
                    $arquivosNaoRemovidos[] = $nomeArquivoCurto . " (ERRO ao remover)";
                }
            } 
            // Para todos os backups (incluindo .bkp.N e .bkp sem número)
            elseif (@unlink($caminhoCompletoArquivo)) {
                $arquivosRemovidos[] = $nomeArquivoCurto . " (removido, Data: " . $dataModificacao . ")";
                $bkpRemovidos++;
            } else {
                $arquivosNaoRemovidos[] = $nomeArquivoCurto . " (ERRO ao remover)";
            }
        } else {
            // Ignora arquivos mais recentes que o limite de tempo
            $arquivosRecentesIgnorados[] = $nomeArquivoCurto . " (Data: " . $dataModificacao . ")";
        }
    }

    echo "\n--- Resumo da Operação ---\n";
    
    if (!empty($arquivosRemovidos)) {
        echo "Arquivos processados e removidos:\n";
        echo implode("\n", array_map(function($f) { return " - " . $f; }, $arquivosRemovidos)) . "\n";
//      echo "Total de arquivos de dados/backups removidos: " . count($arquivosRemovidos) . "\n";
    } else {
//        echo "Nenhum arquivo de dados ou backup com mais de {$msgTempo} foi encontrado para remoção.\n";
    }

    if (!empty($arquivosRecentesIgnorados)) {
        echo "\nArquivos ignorados (menos de {$msgTempo}):\n";
        echo implode("\n", array_map(function($f) { return " - " . $f; }, $arquivosRecentesIgnorados)) . "\n";
    }

    if (!empty($arquivosNaoRemovidos)) {
        echo "\nATENÇÃO: Arquivos com erro na remoção/zeramento:\n";
        echo implode("\n", array_map(function($f) { return " - " . $f; }, $arquivosNaoRemovidos)) . "\n";
        exit(1);
    }

    echo "\nLimpeza concluída com sucesso.\n";
}

// 4. EXECUTAR A FUNÇÃO
limparArquivosDeDados();

?>