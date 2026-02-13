<?php
session_start();

// --- BLOCO DE SEGURANÇA ---
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header('Content-Type: application/json');
    http_response_code(403); // Acesso Proibido
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit;
}

header('Content-Type: application/json');

$json_data = file_get_contents('php://input');
$data = json_decode($json_data);

if (!isset($data->type)) {
    echo json_encode(['status' => 'error', 'message' => 'Tipo de requisição inválida.']);
    exit;
}

// --- FLUXO 1: APENAS CONSULTA DE DADOS ---
if ($data->type === 'consultar' && isset($data->codigo)) {
    $cod_resp_procurado = trim($data->codigo);
    $lookup_file = '../ebi/cadastro_criancas.txt';

    if (!file_exists($lookup_file)) {
        echo json_encode(['status' => 'error', 'message' => 'Arquivo de cadastro de crianças não encontrado.']);
        exit;
    }

    $responsavel_nome = '';
    $criancas_nomes = [];
    $handle = fopen($lookup_file, "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $line_data = explode('|', trim($line));
            if (count($line_data) >= 9 && trim($line_data[8]) == $cod_resp_procurado) {
                if (empty($responsavel_nome)) {
                    $responsavel_nome = trim($line_data[2]);
                }
                //$criancas_nomes[] = trim($line_data[1]);
             
                $nome_crianca = trim($line_data[1]);
                $idade = trim($line_data[4]);
               
                $criancas_nomes[] = $nome_crianca . ' [' . $idade . ']';
                 
            }
        }
        fclose($handle);
    }

    if (empty($criancas_nomes)) {
        echo json_encode(['status' => 'error', 'message' => 'Código de responsável (' . htmlspecialchars($cod_resp_procurado) . ') não encontrado.']);
        exit;
    }

    echo json_encode([
        'status' => 'success_lookup', 
        'responsavel' => $responsavel_nome,
        'criancas' => $criancas_nomes,
        'codResp' => $cod_resp_procurado
    ]);
    exit;
}

// --- FLUXO 2: REGISTRO DOS DADOS APÓS CONFIRMAÇÃO ---
if ($data->type === 'registrar' && isset($data->registroData) && isset($data->portaria)) {
    $registroData = $data->registroData;
    $portaria = $data->portaria;
    $partes = explode(';', $registroData);

    if (count($partes) < 3) {
        echo json_encode(['status' => 'error', 'message' => 'Formato dos dados para registro inválido.']);
        exit;
    }

    $codigo_qr = trim($partes[0]);
    $responsavel = trim($partes[1]);
    $criancas = array_slice($partes, 2);
    $arquivo_dados = 'dados.csv';

    // -----------------------------------------------------------------
    // ALTERAÇÃO: O BLOCO DE VERIFICAÇÃO DE DUPLICIDADE FOI REMOVIDO DAQUI
    // -----------------------------------------------------------------

    // SALVA OS DADOS
    $timestamp = time();
    $linhas_para_salvar = '';
    $criancas_registradas = 0;
    foreach ($criancas as $crianca) {
        $crianca_limpa = trim($crianca);
        if (!empty($crianca_limpa)) {
            $linhas_para_salvar .= $timestamp . ';' . $codigo_qr . ';' . htmlspecialchars($responsavel) . ';' . htmlspecialchars($crianca_limpa) . ';' . htmlspecialchars($portaria) . PHP_EOL;
            $criancas_registradas++;
        }
    }

    if (file_put_contents($arquivo_dados, $linhas_para_salvar, FILE_APPEND | LOCK_EX)) {
        echo json_encode(['status' => 'success_registered', 'message' => $criancas_registradas . ' criança(s) registrada(s) para ' . htmlspecialchars($responsavel) . '.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar os dados.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Requisição desconhecida.']);
