<?php
define('INSTANCE_DIR', __DIR__);
define('EBI_REUNIAO_PRESENCIAL', true);

require __DIR__ . '/../ebi.reuniao/inc/bootstrap.php';
require __DIR__ . '/../ebi.reuniao/inc/auth.php';
require __DIR__ . '/../ebi.reuniao/inc/funcoes.php';

$databaseWasMissing = !file_exists(DB_INSTANCE_PATH);
$pdo = ebi_db();
if ($databaseWasMissing && file_exists(DB_INSTANCE_PATH)) {
    @chmod(DB_INSTANCE_PATH, 0600);
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS reuniao_presencial_registros (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        funcao TEXT NOT NULL,
        nome TEXT NOT NULL,
        cidade TEXT NOT NULL DEFAULT \'\',
        comum TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (strftime(\'%Y-%m-%d %H:%M:%S\', \'now\', \'localtime\'))
    )'
);
$colunasPresencial = array_column(
    $pdo->query('PRAGMA table_info(reuniao_presencial_registros)')->fetchAll(),
    'name'
);
if (!in_array('cidade', $colunasPresencial, true)) {
    $pdo->exec('ALTER TABLE reuniao_presencial_registros ADD COLUMN cidade TEXT NOT NULL DEFAULT \'\'');
}
$pdo->exec('CREATE INDEX IF NOT EXISTS idx_reuniao_presencial_data ON reuniao_presencial_registros(date(created_at))');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_presenca'])) {
    csrf_validate();

    $funcao = trim((string)($_POST['funcao'] ?? ''));
    $nome = trim((string)($_POST['nome'] ?? ''));
    $cidade = trim((string)($_POST['cidade'] ?? ''));
    $comum = trim((string)($_POST['comum'] ?? ''));

    if ($funcao === '' || $nome === '' || $cidade === '' || $comum === '') {
        $_SESSION['mensagemErro'] = 'Preencha Função ou Ministério, Nome Completo, Cidade e Comum.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO reuniao_presencial_registros (funcao, nome, cidade, comum) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            sanitize_for_file($funcao),
            sanitize_for_file($nome),
            sanitize_for_file($cidade),
            sanitize_for_file($comum),
        ]);
        $_SESSION['mensagemSucesso'] = 'Presença registrada com sucesso.';
    }

    header('Location: ' . sanitize_for_html($_SERVER['PHP_SELF']));
    exit;
}

$mensagemSucesso = (string)($_SESSION['mensagemSucesso'] ?? '');
$mensagemErro = (string)($_SESSION['mensagemErro'] ?? '');
unset($_SESSION['mensagemSucesso'], $_SESSION['mensagemErro']);

$hoje = date('Y-m-d');
$stmt = $pdo->prepare(
    'SELECT id, funcao, nome, cidade, comum, created_at
     FROM reuniao_presencial_registros
     WHERE date(created_at) = ?
     ORDER BY id DESC'
);
$stmt->execute([$hoje]);
$registrosHoje = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <title>Reunião Regional - EBI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #0f766e; --bg-2: #0b4f8a;
            --text-main: #10273b; --text-soft: #4b647c;
            --brand: #0e7490; --brand-strong: #0b5f76;
            --brand-soft: rgba(14,116,144,0.14);
            --success-bg: #dff8ea; --success-border: #1f9d61;
            --danger: #b91c1c; --danger-bg: #fee2e2;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: linear-gradient(130deg, var(--bg-1) 0%, var(--bg-2) 58%, #083358 100%); min-height: 100vh; font-family: 'Manrope', sans-serif; padding: 14px 10px 30px; display: flex; flex-direction: column; align-items: center; }
        .page { width: 100%; max-width: 420px; }
        .back-link { display: inline-flex; align-items: center; gap: 4px; color: rgba(255,255,255,0.7); font-size: 0.75rem; text-decoration: none; margin-bottom: 10px; }
        .header { text-align: center; color: #fff; margin-bottom: 14px; }
        .header h1 { font-size: 1.3rem; font-weight: 800; }
        .header p { font-size: 0.75rem; opacity: 0.72; margin-top: 2px; }
        .card { background: rgba(255,255,255,0.98); padding: 18px 16px; border-radius: 16px; box-shadow: 0 10px 30px rgba(1,27,49,0.3); margin-bottom: 12px; }
        .card h2 { font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 12px; display: flex; align-items: center; gap: 7px; }
        .card h2 i { color: var(--brand); }
        .total-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; padding: 10px 12px; background: var(--brand-soft); border-radius: 10px; color: var(--text-main); font-size: 0.8rem; font-weight: 700; }
        .total-row span { color: #fff; background: var(--brand); border-radius: 10px; padding: 3px 9px; font-size: 0.72rem; }
        .field { margin-bottom: 13px; }
        .field label { display: block; margin-bottom: 5px; font-size: 0.78rem; font-weight: 700; color: var(--text-main); }
        .field input, .field select { width: 100%; min-height: 44px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; color: var(--text-main); font: inherit; font-size: 0.88rem; background: #fff; }
        .field input:focus, .field select:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-soft); }
        .btn-register { width: 100%; padding: 15px; border: 0; border-radius: 12px; background: linear-gradient(135deg, #16a34a, #15803d); color: #fff; font-weight: 800; font-size: 1rem; cursor: pointer; }
        .lista-card { max-height: 330px; overflow-y: auto; }
        .lista-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 3px 10px; padding: 9px 0; border-bottom: 1px solid #e5e7eb; }
        .lista-item:last-child { border-bottom: 0; }
        .lista-item .nome { color: var(--text-main); font-size: 0.82rem; font-weight: 700; }
        .lista-item .funcao, .lista-item .local, .lista-item .horario { color: var(--text-soft); font-size: 0.7rem; }
        .lista-item .local { grid-column: 1; }
        .lista-item .horario { grid-column: 2; grid-row: 1 / span 2; align-self: center; text-align: right; }
        .empty { text-align: center; color: var(--text-soft); font-size: 0.8rem; padding: 16px 0; }
        .toast { margin: 0 0 12px; padding: 10px 16px; border-radius: 10px; font-size: 0.8rem; font-weight: 700; text-align: center; }
        .toast.ok { color: #166534; background: var(--success-bg); border: 1px solid var(--success-border); }
        .toast.err { color: var(--danger); background: var(--danger-bg); border: 1px solid var(--danger); }
        .footer { text-align: center; margin-top: 12px; font-size: 9px; color: rgba(255,255,255,0.35); }
    </style>
</head>
<body>
<main class="page">
    <a href="../selfservice/selfservice.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar</a>

    <header class="header">
        <h1><i class="fas fa-users"></i> Reunião Regional - EBI</h1>
        <p>Registro de presença</p>
    </header>

    <?php if ($mensagemSucesso !== ''): ?>
        <div class="toast ok"><?php echo sanitize_for_html($mensagemSucesso); ?></div>
    <?php endif; ?>
    <?php if ($mensagemErro !== ''): ?>
        <div class="toast err"><?php echo sanitize_for_html($mensagemErro); ?></div>
    <?php endif; ?>

    <section class="card" aria-labelledby="cadastro-title">
        <div class="total-row"><span>Hoje</span> <?php echo count($registrosHoje); ?> presença(s)</div>
        <h2 id="cadastro-title"><i class="fas fa-user-check"></i> Registrar presença</h2>
        <form method="post" autocomplete="on">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="cadastrar_presenca" value="1">
            <div class="field">
                <label for="funcao">Função ou Ministério</label>
                <select id="funcao" name="funcao" required>
                    <option value="">Selecione uma opção</option>
                    <optgroup label="Função">
                    <option value="Coordenadora">Coordenadora</option>
                    <option value="Colaboradora">Colaboradora</option>
                    </optgroup>
                    <optgroup label="Ministério">
                    <option value="Ancião">Ancião</option>
                    <option value="Cooperador do Ofício">Cooperador do Ofício</option>
                    <option value="Cooperador de Jovens">Cooperador de Jovens</option>
                    <option value="Diácono">Diácono</option>
                    <option value="Administração">Administração</option>
                    <option value="Outros">Outros</option>
                    </optgroup>
                </select>
            </div>
            <div class="field">
                <label for="nome">Nome Completo</label>
                <input id="nome" name="nome" type="text" autocomplete="name" placeholder="Nome e sobrenome" required>
            </div>
            <div class="field">
                <label for="cidade">Cidade</label>
                <input id="cidade" name="cidade" type="text" autocomplete="address-level2" placeholder="Informe a cidade" required>
            </div>
            <div class="field">
                <label for="comum">Comum</label>
                <input id="comum" name="comum" type="text" autocomplete="organization" placeholder="Informe a comum" required>
            </div>
            <button class="btn-register" type="submit"><i class="fas fa-check-circle mr-1"></i> Registrar</button>
        </form>
    </section>

    <section class="card" aria-labelledby="registros-title">
        <h2 id="registros-title"><i class="fas fa-list"></i> Presenças de hoje (<?php echo count($registrosHoje); ?>)</h2>
        <div class="lista-card">
            <?php if ($registrosHoje === []): ?>
                <div class="empty"><i class="fas fa-inbox" style="font-size:1.5rem;opacity:0.3;display:block;margin-bottom:6px;"></i>Nenhuma presença registrada hoje</div>
            <?php else: ?>
                <?php foreach ($registrosHoje as $registro): ?>
                    <div class="lista-item">
                        <span class="nome"><?php echo sanitize_for_html($registro['nome']); ?></span>
                        <span class="funcao"><?php echo sanitize_for_html($registro['funcao']); ?></span>
                        <span class="local"><?php echo sanitize_for_html($registro['cidade']) . ' · ' . sanitize_for_html($registro['comum']); ?></span>
                        <span class="horario"><?php echo sanitize_for_html(substr((string)$registro['created_at'], 11, 5)); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="footer">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</main>
</body>
</html>