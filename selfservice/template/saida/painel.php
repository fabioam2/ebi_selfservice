<?php
/**
 * Painel de Saídas de Crianças
 * Reutiliza autenticação e configuração do EBI via bootstrap.php
 */

require __DIR__ . '/inc/bootstrap.php';

// Verificar autenticação
if (!isset($_SESSION['logado_saida']) || $_SESSION['logado_saida'] !== true) {
    header('Location: index.php');
    exit;
}

$erro_zerar = '';

// Processar ação de zerar arquivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'zerar_arquivo') {
    csrf_validate();

    if (isset($_POST['admin_senha']) && $_POST['admin_senha'] === SENHA_PAINEL) {
        if (file_exists(ARQUIVO_SAIDAS)) {
            @unlink(ARQUIVO_SAIDAS);
        }
        header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
        exit;
    } else {
        $erro_zerar = 'Senha incorreta!';
    }
}

// Lógica para configurar refresh e paginação
$refresh_rate = 5;
if (isset($_GET['refresh']) && is_numeric($_GET['refresh'])) {
    $rate = intval($_GET['refresh']);
    if ($rate >= 1 && $rate <= 5) {
        $refresh_rate = $rate;
    }
}
$mostrar_todos = isset($_GET['ver']) && $_GET['ver'] === 'todos';

// Lógica para ler e processar os dados
$entradas_agrupadas = [];
if (file_exists(ARQUIVO_SAIDAS) && is_readable(ARQUIVO_SAIDAS)) {
    $linhas = file(ARQUIVO_SAIDAS, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $linhas_recentes = array_reverse($linhas);
    $registros_para_exibir = $mostrar_todos ? $linhas_recentes : array_slice($linhas_recentes, 0, 10);

    foreach ($registros_para_exibir as $linha) {
        $dados = explode(';', $linha, 5);
        if (count($dados) === 5) {
            list($timestamp, $codigo_qr, $responsavel, $crianca, $portaria) = $dados;

            // Inicializa o grupo se for a primeira vez que o código QR aparece
            if (!isset($entradas_agrupadas[$codigo_qr])) {
                $entradas_agrupadas[$codigo_qr] = [
                    'timestamp' => $timestamp,
                    'portaria' => sanitize_for_html($portaria),
                    'responsavel' => sanitize_for_html($responsavel),
                    'criancas' => []
                ];
            }

            $crianca_sanitizada = sanitize_for_html($crianca);

            // Adiciona a criança SOMENTE se ela ainda não estiver na lista
            if (!in_array($crianca_sanitizada, $entradas_agrupadas[$codigo_qr]['criancas'])) {
                $entradas_agrupadas[$codigo_qr]['criancas'][] = $crianca_sanitizada;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="<?php echo $refresh_rate; ?>">
    <title>Painel de Saídas</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 1.8rem;
            color: #333;
        }
        .logout-link {
            background-color: #dc3545;
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .logout-link:hover {
            background-color: #b22222;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        th, td {
            padding: 15px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #4a5568;
            color: white;
        }
        .footer-controls {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }
        .refresh-selector label, .view-all-link, .link-inserir {
            font-weight: bold;
        }
        .refresh-selector select {
            padding: 5px 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        #zerar-btn {
            padding: 8px 12px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        #zerar-btn:hover {
            background-color: #b22222;
        }
        .error-message {
            color: #dc3545;
            font-weight: bold;
            margin-top: 10px;
        }
        .portaria-m {
            background-color: #e3f2fd;
        }
        .portaria-f {
            background-color: #fce4ec;
        }
        .portaria-default {
            background-color: #f7fafc;
        }
        .link-inserir a {
            color: #007bff;
            text-decoration: none;
        }
        .link-inserir a:hover {
            text-decoration: underline;
        }
        .view-all-link a {
            color: #007bff;
            text-decoration: none;
        }
        .view-all-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Painel de Saídas</h1>
            <a href="index.php?acao=logout" class="logout-link">Sair</a>
        </div>

        <?php if (empty($entradas_agrupadas) && !$mostrar_todos): ?>
            <p style="text-align:center; font-weight:bold;">Nenhuma saída registrada ainda.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Saída (Crianças / Responsável)</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($entradas_agrupadas as $codigo_qr => $entrada): ?>
                        <?php
                            $cor_classe = 'portaria-default';
                            if ($entrada['portaria'] === 'M') {
                                $cor_classe = 'portaria-m';
                            } elseif ($entrada['portaria'] === 'F') {
                                $cor_classe = 'portaria-f';
                            }
                        ?>
                        <tr class="<?php echo $cor_classe; ?>">
                            <td>
                                <?php echo date('H:i', $entrada['timestamp']); ?> -
                                <?php echo implode('; ', $entrada['criancas']); ?> -
                                <strong><?php echo $entrada['responsavel']; ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="footer-controls">
            <div class="view-all-link">
                <?php if ($mostrar_todos): ?>
                    <a href="?refresh=<?php echo $refresh_rate; ?>">Ver os 10 últimos</a>
                <?php else: ?>
                    <a href="?ver=todos&refresh=<?php echo $refresh_rate; ?>">Ver Todos</a>
                <?php endif; ?>
            </div>
            <div class="refresh-selector">
                <label for="refresh-rate">Atualizar a cada:</label>
                <select id="refresh-rate">
                    <option value="1" <?php if($refresh_rate == 1) echo 'selected'; ?>>1s</option>
                    <option value="2" <?php if($refresh_rate == 2) echo 'selected'; ?>>2s</option>
                    <option value="3" <?php if($refresh_rate == 3) echo 'selected'; ?>>3s</option>
                    <option value="4" <?php if($refresh_rate == 4) echo 'selected'; ?>>4s</option>
                    <option value="5" <?php if($refresh_rate == 5) echo 'selected'; ?>>5s</option>
                </select>
            </div>
            <div class="clear-file-section">
                <button id="zerar-btn">Zerar Arquivo</button>
                <?php if (!empty($erro_zerar)): ?>
                    <p class="error-message"><?php echo sanitize_for_html($erro_zerar); ?></p>
                <?php endif; ?>
            </div>
            <div class="link-inserir"><a href="index.php">Registrar Nova Saída</a></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const refreshSelect = document.getElementById('refresh-rate');
            const zerarBtn = document.getElementById('zerar-btn');
            const urlParams = new URLSearchParams(window.location.search);
            const currentRate = urlParams.get('refresh') || '5';

            refreshSelect.value = currentRate;

            refreshSelect.addEventListener('change', function() {
                urlParams.set('refresh', this.value);
                window.location.href = window.location.pathname + '?' + urlParams.toString();
            });

            zerarBtn.addEventListener('click', function() {
                const senha = prompt("Para zerar o arquivo de registros, digite a senha do painel:");
                if (senha !== null) {
                    const form = document.createElement('form');
                    form.method = 'post';
                    form.action = '<?php echo sanitize_for_html($_SERVER['PHP_SELF']); ?>';

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    csrfInput.value = '<?php echo csrf_token(); ?>';
                    form.appendChild(csrfInput);

                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'acao';
                    actionInput.value = 'zerar_arquivo';
                    form.appendChild(actionInput);

                    const passwordInput = document.createElement('input');
                    passwordInput.type = 'hidden';
                    passwordInput.name = 'admin_senha';
                    passwordInput.value = senha;
                    form.appendChild(passwordInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>
