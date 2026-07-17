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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <meta http-equiv="refresh" content="<?php echo $refresh_rate; ?>">
    <title>Painel de Saídas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #0f766e;
            --bg-2: #0b4f8a;
            --surface-border: rgba(15, 23, 42, 0.08);
            --text-main: #10273b;
            --text-soft: #4b647c;
            --brand: #0e7490;
            --brand-strong: #0b5f76;
            --brand-soft: rgba(14, 116, 144, 0.14);
            --danger: #b91c1c;
            --portaria-m-bg: #e3f2fd;
            --portaria-m-border: #1565c0;
            --portaria-f-bg: #fce4ec;
            --portaria-f-border: #ad1457;
        }
        * { box-sizing: border-box; }
        body {
            background: radial-gradient(circle at 8% 8%, rgba(245, 158, 11, 0.28) 0%, rgba(245, 158, 11, 0) 35%),
                        radial-gradient(circle at 94% 16%, rgba(20, 184, 166, 0.32) 0%, rgba(20, 184, 166, 0) 40%),
                        linear-gradient(130deg, var(--bg-1) 0%, var(--bg-2) 58%, #083358 100%);
            min-height: 100vh;
            margin: 0;
            font-family: 'Manrope', sans-serif;
            padding: 20px 14px 30px;
        }
        .container {
            max-width: 720px;
            margin: 0 auto;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.97));
            padding: 24px 22px;
            border-radius: 22px;
            border: 1px solid var(--surface-border);
            box-shadow: 0 18px 48px rgba(1, 27, 49, 0.33);
            transition: max-width 0.3s ease, padding 0.3s ease;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 10px;
            flex-wrap: wrap;
        }
        .header h1 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .header h1 i { color: var(--brand); }
        .header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-pill {
            border: none;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: transform 0.2s ease;
        }
        .btn-pill:hover { transform: translateY(-1px); }
        .logout-link { background: var(--danger); color: #fff; }
        .logout-link:hover { color: #fff; }
        #tv-toggle-btn { background: var(--brand-soft); color: var(--brand-strong); }
        .empty-state {
            text-align: center;
            font-weight: 700;
            color: var(--text-soft);
            padding: 30px 10px;
        }
        .saida-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 18px;
        }
        .saida-item {
            display: flex;
            align-items: baseline;
            gap: 10px;
            flex-wrap: wrap;
            padding: 14px 16px;
            border-radius: 14px;
            border-left: 5px solid var(--brand);
            background: #f8fafc;
        }
        .saida-item.portaria-m { background: var(--portaria-m-bg); border-left-color: var(--portaria-m-border); }
        .saida-item.portaria-f { background: var(--portaria-f-bg); border-left-color: var(--portaria-f-border); }
        .saida-time {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-soft);
            white-space: nowrap;
        }
        .saida-criancas {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-main);
            flex: 1 1 auto;
        }
        .saida-responsavel {
            font-size: 0.9rem;
            color: var(--text-soft);
            width: 100%;
        }
        .saida-responsavel strong { color: var(--text-main); }
        .footer-controls {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding-top: 14px;
            border-top: 1px solid var(--surface-border);
        }
        .footer-controls a {
            color: var(--brand-strong);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.88rem;
        }
        .footer-controls a:hover { text-decoration: underline; }
        .refresh-selector { font-size: 0.88rem; font-weight: 700; color: var(--text-soft); }
        .refresh-selector select {
            padding: 5px 8px;
            border-radius: 8px;
            border: 1px solid #ced9e4;
            margin-left: 4px;
        }
        #zerar-btn {
            padding: 7px 13px;
            background: #fff;
            color: var(--danger);
            border: 1px solid var(--danger);
            border-radius: 999px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .error-message { color: var(--danger); font-weight: 700; margin-top: 8px; font-size: 0.85rem; }

        /* Adaptação automática para telas grandes (TV / monitor) */
        @media (min-width: 1100px) {
            .container { max-width: 1100px; padding: 40px 48px; }
            .header h1 { font-size: 2rem; }
            .saida-item { padding: 20px 26px; border-radius: 18px; }
            .saida-time { font-size: 1.1rem; }
            .saida-criancas { font-size: 1.7rem; }
            .saida-responsavel { font-size: 1.15rem; }
        }

        /* Modo TV: acionado manualmente, maximiza legibilidade a distância */
        body.tv-mode { padding: 30px; }
        body.tv-mode .container { max-width: 1500px; padding: 40px 56px; box-shadow: none; }
        body.tv-mode .header h1 { font-size: 2.6rem; }
        body.tv-mode .saida-list { gap: 18px; }
        body.tv-mode .saida-item { padding: 26px 32px; border-radius: 20px; border-left-width: 8px; }
        body.tv-mode .saida-time { font-size: 1.4rem; }
        body.tv-mode .saida-criancas { font-size: 2.6rem; }
        body.tv-mode .saida-responsavel { font-size: 1.5rem; }
        body.tv-mode .footer-controls,
        body.tv-mode .logout-link { display: none; }
        body.tv-mode #tv-toggle-btn { display: inline-flex; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tv"></i> Painel de Saídas</h1>
            <div class="header-actions">
                <button type="button" id="tv-toggle-btn" class="btn-pill"><i class="fas fa-expand"></i> Modo TV</button>
                <a href="index.php?acao=logout" class="btn-pill logout-link">Sair</a>
            </div>
        </div>

        <?php if (empty($entradas_agrupadas) && !$mostrar_todos): ?>
            <p class="empty-state">Nenhuma saída registrada ainda.</p>
        <?php else: ?>
            <div class="saida-list">
                <?php foreach ($entradas_agrupadas as $codigo_qr => $entrada): ?>
                    <?php
                        $cor_classe = '';
                        if ($entrada['portaria'] === 'M') {
                            $cor_classe = 'portaria-m';
                        } elseif ($entrada['portaria'] === 'F') {
                            $cor_classe = 'portaria-f';
                        }
                    ?>
                    <div class="saida-item <?php echo $cor_classe; ?>">
                        <span class="saida-time"><?php echo date('H:i', strtotime($entrada['timestamp'])); ?></span>
                        <span class="saida-criancas"><?php echo !empty($entrada['criancas']) ? implode(', ', $entrada['criancas']) : '—'; ?></span>
                        <span class="saida-responsavel">Responsável: <strong><?php echo $entrada['responsavel']; ?></strong></span>
                    </div>
                <?php endforeach; ?>
            </div>
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
            const tvToggleBtn = document.getElementById('tv-toggle-btn');
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

            // --- Modo TV ---
            // Como a página recarrega via <meta refresh>, a preferência é
            // guardada no localStorage e reaplicada a cada atualização.
            const TV_MODE_KEY = 'painelModoTV';

            function aplicarModoTV(ativo) {
                document.body.classList.toggle('tv-mode', ativo);
                tvToggleBtn.innerHTML = ativo
                    ? '<i class="fas fa-compress"></i> Sair do Modo TV'
                    : '<i class="fas fa-expand"></i> Modo TV';
            }

            aplicarModoTV(localStorage.getItem(TV_MODE_KEY) === '1');

            tvToggleBtn.addEventListener('click', function() {
                const novoEstado = !document.body.classList.contains('tv-mode');
                localStorage.setItem(TV_MODE_KEY, novoEstado ? '1' : '0');
                aplicarModoTV(novoEstado);
            });
        });
    </script>
    <div class="text-center mt-3 mb-2" style="font-size:9px;color:#b0b0b0;opacity:0.6">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</body>
</html>
