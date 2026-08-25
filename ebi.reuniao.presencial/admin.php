<?php
define('INSTANCE_DIR', __DIR__);
define('EBI_REUNIAO_PRESENCIAL', true);
define('EBI_REUNIAO_PRESENCIAL_ADMIN', true);

require __DIR__ . '/../ebi.reuniao/inc/bootstrap.php';
require __DIR__ . '/../ebi.reuniao/inc/auth.php';
require __DIR__ . '/../ebi.reuniao/inc/funcoes.php';
require __DIR__ . '/inc/registros.php';

$databaseWasMissing = !file_exists(DB_INSTANCE_PATH);
$pdo = ebi_db();
if ($databaseWasMissing && file_exists(DB_INSTANCE_PATH)) {
    @chmod(DB_INSTANCE_PATH, 0600);
}
reuniao_presencial_preparar_banco($pdo);

$dataSelecionada = (string)($_GET['data'] ?? date('Y-m-d'));
$dataValida = DateTime::createFromFormat('!Y-m-d', $dataSelecionada);
if ($dataValida === false || $dataValida->format('Y-m-d') !== $dataSelecionada) {
    $dataSelecionada = date('Y-m-d');
}

$stmt = $pdo->prepare(
    'SELECT id, funcao, nome, cidade, comum, created_at
     FROM reuniao_presencial_registros
     WHERE date(created_at) = ?
     ORDER BY id DESC'
);
$stmt->execute([$dataSelecionada]);
$registros = $stmt->fetchAll();
$dataFormatada = DateTime::createFromFormat('!Y-m-d', $dataSelecionada)->format('d/m/Y');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração Regional - EBI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg-1: #0f766e; --bg-2: #0b4f8a; --text-main: #10273b; --text-soft: #4b647c; --brand: #0e7490; --brand-soft: rgba(14,116,144,0.14); }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { min-height: 100vh; padding: 22px 14px 30px; background: linear-gradient(130deg, var(--bg-1) 0%, var(--bg-2) 58%, #083358 100%); color: var(--text-main); font-family: 'Manrope', sans-serif; }
        .page { width: 100%; max-width: 820px; margin: 0 auto; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 16px; color: #fff; }
        .topbar h1 { font-size: 1.25rem; font-weight: 800; }
        .topbar p { margin-top: 2px; font-size: 0.75rem; opacity: 0.75; }
        .logout { color: rgba(255,255,255,0.86); font-size: 0.78rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
        .card { margin-bottom: 12px; padding: 18px; border-radius: 10px; background: rgba(255,255,255,0.98); box-shadow: 0 10px 30px rgba(1,27,49,0.3); }
        .summary { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border-radius: 8px; background: var(--brand-soft); font-size: 0.85rem; font-weight: 800; }
        .summary strong { color: #fff; background: var(--brand); padding: 4px 10px; border-radius: 8px; }
        .filter { display: flex; align-items: end; gap: 8px; margin-top: 14px; }
        .filter label { display: grid; gap: 5px; color: var(--text-main); font-size: 0.75rem; font-weight: 700; }
        input, button { min-height: 42px; border-radius: 7px; font: inherit; }
        input { padding: 8px 10px; border: 1px solid #cbd5e1; color: var(--text-main); }
        button { padding: 8px 14px; border: 0; color: #fff; background: var(--brand); font-size: 0.8rem; font-weight: 800; cursor: pointer; }
        h2 { margin-bottom: 12px; font-size: 0.95rem; }
        .record { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 3px 12px; padding: 11px 0; border-bottom: 1px solid #e5e7eb; }
        .record:last-child { border-bottom: 0; }
        .name { font-size: 0.86rem; font-weight: 800; }
        .role, .place, .time { color: var(--text-soft); font-size: 0.73rem; }
        .place { grid-column: 1; }
        .time { grid-column: 2; grid-row: 1 / span 2; align-self: center; text-align: right; }
        .empty { padding: 18px 0; color: var(--text-soft); font-size: 0.82rem; text-align: center; }
        .footer { margin-top: 12px; color: rgba(255,255,255,0.38); font-size: 9px; text-align: center; }
        @media (max-width: 480px) { .topbar h1 { font-size: 1.05rem; } .card { padding: 15px; } }
    </style>
</head>
<body>
<main class="page">
    <header class="topbar">
        <div>
            <h1><i class="fas fa-user-shield"></i> Administração Regional</h1>
            <p>Reunião Regional - EBI</p>
        </div>
        <a class="logout" href="admin.php?acao=logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </header>

    <section class="card">
        <div class="summary"><span>Presenças em <?php echo sanitize_for_html($dataFormatada); ?></span><strong><?php echo count($registros); ?></strong></div>
        <form class="filter" method="get">
            <label for="data">Consultar data
                <input id="data" name="data" type="date" value="<?php echo sanitize_for_html($dataSelecionada); ?>">
            </label>
            <button type="submit"><i class="fas fa-filter"></i> Consultar</button>
        </form>
    </section>

    <section class="card" aria-labelledby="registros-title">
        <h2 id="registros-title"><i class="fas fa-list"></i> Registros</h2>
        <?php if ($registros === []): ?>
            <div class="empty">Nenhuma presença registrada nesta data.</div>
        <?php else: ?>
            <?php foreach ($registros as $registro): ?>
                <article class="record">
                    <span class="name"><?php echo sanitize_for_html($registro['nome']); ?></span>
                    <span class="role"><?php echo sanitize_for_html($registro['funcao']); ?></span>
                    <span class="place"><?php echo sanitize_for_html($registro['cidade']) . ' - ' . sanitize_for_html($registro['comum']); ?></span>
                    <time class="time"><?php echo sanitize_for_html(substr((string)$registro['created_at'], 11, 5)); ?></time>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <div class="footer">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</main>
</body>
</html>