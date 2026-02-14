<?php
/**
 * API JSON para Processamento de Consulta e Registro de Saídas
 * Reutiliza autenticação e configuração do EBI via bootstrap.php
 */

require __DIR__ . '/inc/bootstrap.php';

// --- BLOCO DE SEGURANÇA ---
if (!isset($_SESSION['logado_saida']) || $_SESSION['logado_saida'] !== true) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$json_data = file_get_contents('php://input');
$data = json_decode($json_data);

if (!isset($data->type)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tipo de requisição inválida.']);
    exit;
}

// --- FLUXO 1: APENAS CONSULTA DE DADOS ---
if ($data->type === 'consultar' && isset($data->codigo)) {
    $cod_resp_procurado = trim((string)$data->codigo);

    // Validar entrada
    if (!is_numeric($cod_resp_procurado) || $cod_resp_procurado < 1) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Código de responsável inválido.']);
        exit;
    }

    if (!file_exists(ARQUIVO_DADOS)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Arquivo de cadastro não encontrado.']);
        exit;
    }

    $responsavel_nome = '';
    $criancas_nomes = [];
    $handle = fopen(ARQUIVO_DADOS, "r");

    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            // Ignorar linhas de comentário
            if (isset($line[0]) && $line[0] === '#') {
                continue;
            }

            $line_data = explode(DELIMITADOR, trim($line));

            // Validar formato da linha (min 9 campos)
            if (count($line_data) >= 9 && trim($line_data[8]) === $cod_resp_procurado) {
                if (empty($responsavel_nome)) {
                    $responsavel_nome = trim($line_data[2]);
                }

                $nome_crianca = trim($line_data[1]);
                $idade = trim($line_data[4]);

                // Formatar nome com idade
                if (!empty($nome_crianca)) {
                    $criancas_nomes[] = $nome_crianca . ' [' . $idade . ' anos]';
                }
            }
        }
        fclose($handle);
    }

    if (empty($criancas_nomes)) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Código de responsável (' . sanitize_for_html($cod_resp_procurado) . ') não encontrado.']);
        exit;
    }

    echo json_encode([
        'status' => 'success_lookup',
        'responsavel' => sanitize_for_html($responsavel_nome),
        'criancas' => array_map('sanitize_for_html', $criancas_nomes),
        'codResp' => sanitize_for_html($cod_resp_procurado)
    ]);
    exit;
}

// --- FLUXO 2: REGISTRO DOS DADOS APÓS CONFIRMAÇÃO ---
if ($data->type === 'registrar' && isset($data->registroData) && isset($data->portaria)) {
    $registroData = trim((string)$data->registroData);
    $portaria = trim((string)$data->portaria);

    // Validar formato
    if (empty($registroData) || empty($portaria)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Dados incompletos para registro.']);
        exit;
    }

    // Validar portaria (deve ser uma única letra)
    if (!preg_match('/^[A-Z]$/', $portaria)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Portaria inválida.']);
        exit;
    }

    $partes = explode(';', $registroData);

    if (count($partes) < 3) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Formato dos dados para registro inválido.']);
        exit;
    }

    $codigo_qr = trim($partes[0]);
    $responsavel = trim($partes[1]);
    $criancas = array_slice($partes, 2);

    // Validar código
    if (!is_numeric($codigo_qr) || $codigo_qr < 1) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Código de responsável inválido.']);
        exit;
    }

    // SALVA OS DADOS
    $timestamp = time();
    $linhas_para_salvar = '';
    $criancas_registradas = 0;

    foreach ($criancas as $crianca) {
        $crianca_limpa = trim($crianca);
        if (!empty($crianca_limpa)) {
            // Sanitizar dados antes de salvar
            $linhas_para_salvar .= $timestamp . ';' . sanitize_for_file($codigo_qr) . ';' . sanitize_for_file($responsavel) . ';' . sanitize_for_file($crianca_limpa) . ';' . sanitize_for_file($portaria) . PHP_EOL;
            $criancas_registradas++;
        }
    }

    if (!empty($linhas_para_salvar)) {
        if (@file_put_contents(ARQUIVO_SAIDAS, $linhas_para_salvar, FILE_APPEND | LOCK_EX) !== false) {
            echo json_encode([
                'status' => 'success_registered',
                'message' => $criancas_registradas . ' criança(s) registrada(s) para ' . sanitize_for_html($responsavel) . '.'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar os dados. Verifique permissões do arquivo.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Nenhuma criança válida para registro.']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Requisição desconhecida.']);
