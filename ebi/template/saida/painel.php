<?php
/**
 * Painel de Saídas de Crianças
 * Reutiliza autenticação e configuração do EBI via bootstrap.php
 */

require __DIR__ . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/db_instance.php';

// Verificar autenticação
if (!isset($_SESSION['logado_saida']) || $_SESSION['logado_saida'] !== true) {
    header('Location: index.php');
    exit;
}

$erro_zerar = '';

// Processar ação de zerar registros
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'zerar_arquivo') {
    csrf_validate();

    if (verificar_senha_painel($_POST['admin_senha'] ?? '')) {
        try {
            ebi_db()->exec('DELETE FROM saidas');
        } catch (Throwable $e) {
            error_log('[EBI Painel] zerar saidas: ' . $e->getMessage());
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

// Lê saídas do SQLite e junta com nomes das crianças
$entradas_agrupadas = [];
try {
    $limite = $mostrar_todos ? 500 : 50;
    $rows = ebi_db()->prepare(
        'SELECT s.id, s.cod_resp, s.nome_responsavel, s.portaria, s.registered_at,
                (SELECT GROUP_CONCAT(nome_crianca, \'; \')
                 FROM cadastros WHERE cod_resp = s.cod_resp) as criancas_nomes
         FROM saidas s
         ORDER BY s.registered_at DESC LIMIT ?'
    );
    $rows->execute([$limite]);
    $saidas = $rows->fetchAll();

    foreach ($saidas as $s) {
        $key = $s['id'];
        $criancas = array_filter(array_map('trim', explode(';', $s['criancas_nomes'] ?? '')));
        $entradas_agrupadas[$key] = [
            'timestamp'  => $s['registered_at'],
            'portaria'   => sanitize_for_html(strtoupper($s['portaria'])),
            'responsavel'=> sanitize_for_html($s['nome_responsavel']),
            'criancas'   => array_values(array_map('sanitize_for_html', array_filter($criancas))),
        ];
    }
} catch (Throwable $e) {
    error_log('[EBI Painel] listar saidas: ' . $e->getMessage());
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
                                <?php echo date('H:i', strtotime($entrada['timestamp'])); ?> -
                                <?php echo !empty($entrada['criancas']) ? implode('; ', $entrada['criancas']) : '—'; ?> -
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
    <div class="text-center mt-3 mb-2" style="font-size:9px;color:#b0b0b0;opacity:0.6">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</body>
</html>
