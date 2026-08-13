<?php
/**
 * API JSON para consulta e registro de saídas — usa SQLite.
 */

require __DIR__ . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/db_instance.php';
require_once dirname(__DIR__) . '/inc/stats.php';

// ── Autenticação ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['logado_saida']) || $_SESSION['logado_saida'] !== true) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$json_data = file_get_contents('php://input');
$data      = json_decode($json_data);

if (!isset($data->type)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tipo de requisição inválido.']);
    exit;
}

// ── FLUXO 1: Consulta por cod_resp ────────────────────────────────────────────
if ($data->type === 'consultar' && isset($data->codigo)) {
    $cod_resp = (int)trim((string)$data->codigo);

    if ($cod_resp < 1) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Código de responsável inválido.']);
        exit;
    }

    $rows = db_listar_por_cod_resp($cod_resp);

    if (empty($rows)) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => "Código {$cod_resp} não encontrado."]);
        exit;
    }

    $responsavel_nome = $rows[0]['nome_responsavel'] ?? '';
    $criancas_nomes   = [];
    foreach ($rows as $r) {
        $nome = trim($r['nome_crianca'] ?? '');
        if ($nome !== '') {
            $criancas_nomes[] = $nome . ' [' . $r['idade'] . ' anos]';
        }
    }

    echo json_encode([
        'status'      => 'success_lookup',
        'responsavel' => sanitize_for_html($responsavel_nome),
        'criancas'    => array_map('sanitize_for_html', $criancas_nomes),
        'codResp'     => $cod_resp,
    ]);
    exit;
}

// ── FLUXO 2: Registro de saída ────────────────────────────────────────────────
if ($data->type === 'registrar' && isset($data->registroData, $data->portaria)) {
    $registroData = trim((string)$data->registroData);
    $portaria     = strtoupper(trim((string)$data->portaria));

    if (empty($registroData) || !preg_match('/^[A-Z]$/', $portaria)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Dados incompletos ou portaria inválida.']);
        exit;
    }

    $partes = explode(';', $registroData);
    if (count($partes) < 2) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Formato dos dados inválido.']);
        exit;
    }

    $cod_resp   = (int)trim($partes[0]);
    $responsavel = sanitize_for_file(trim($partes[1]));

    if ($cod_resp < 1) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Código de responsável inválido.']);
        exit;
    }

    try {
        db_inserir_saida($cod_resp, $responsavel, $portaria);
        stats_on_saida($portaria);

        $nomes = array_slice($partes, 2);
        $criancasRegistradas = count(array_filter(array_map('trim', $nomes)));

        echo json_encode([
            'status'  => 'success_registered',
            'message' => $criancasRegistradas . ' criança(s) registrada(s) para ' . sanitize_for_html($responsavel) . '.',
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar saída.']);
        error_log('[EBI Saída] ' . $e->getMessage());
    }
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Requisição desconhecida.']);
