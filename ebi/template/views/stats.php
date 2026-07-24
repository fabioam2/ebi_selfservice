<?php
/**
 * View de Estatísticas (BI) — EBI Cadastro de Crianças.
 * Carregado via index.php quando GET ?acao=stats.
 * Todas as funções db_stats_* vêm de inc/db_instance.php.
 * Nenhum nome de criança é armazenado ou exibido.
 */

$periodo = (int)($_GET['periodo'] ?? 30);
if (!in_array($periodo, [1, 7, 30, 90, 365], true)) $periodo = 30;

// Data selecionada (padrão: hoje)
$dataSelecionada = $_GET['data'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataSelecionada)) $dataSelecionada = date('Y-m-d');

$diasRows     = db_stats_listar_diario($periodo);
$totais       = db_stats_totais_instancia();
$mensalRows   = db_stats_mensal_instancia(12);
$hoje         = date('Y-m-d');

// ── Agregações do período ─────────────────────────────────────────────────────
$periodoTotais = ['cadastros' => 0, 'impressoes' => 0, 'saidas' => 0,
    'age_0_3' => 0, 'age_4_7' => 0, 'age_8_11' => 0, 'age_12_14' => 0, 'age_15_17' => 0,
    'total_meninos' => 0, 'total_meninas' => 0];
$portariaAgr = [];
$comumAgr    = [];

foreach ($diasRows as $row) {
    $periodoTotais['cadastros']  += (int)($row['total_cadastros']  ?? 0);
    $periodoTotais['impressoes'] += (int)($row['total_impressoes'] ?? 0);
    $periodoTotais['saidas']     += (int)($row['total_saidas']     ?? 0);
    foreach (['age_0_3','age_4_7','age_8_11','age_12_14','age_15_17'] as $b) {
        $periodoTotais[$b] += (int)($row[$b] ?? 0);
    }
    $periodoTotais['total_meninos'] += (int)($row['total_meninos'] ?? 0);
    $periodoTotais['total_meninas'] += (int)($row['total_meninas'] ?? 0);
    foreach ((json_decode($row['portaria_data'] ?? '{}', true) ?: []) as $p => $cnt) {
        $portariaAgr[$p] = ($portariaAgr[$p] ?? 0) + (int)$cnt;
    }
    foreach ((json_decode($row['comum_data'] ?? '{}', true) ?: []) as $c => $cnt) {
        $comumAgr[$c] = ($comumAgr[$c] ?? 0) + (int)$cnt;
    }
}
arsort($portariaAgr);
arsort($comumAgr);

// Da comum vs fora da comum
$comumBase = defined('INSTANCE_COMUM') ? trim((string)INSTANCE_COMUM) : '';
$palavrasComumBI = montarPalavrasChaveComum($comumBase, defined('LISTA_PALAVRAS_CONTADOR_COMUM') ? LISTA_PALAVRAS_CONTADOR_COMUM : '');
$nomeComum       = $comumBase !== '' ? ucfirst($comumBase) : 'Comum';
$totalDaComum    = 0;
foreach ($comumAgr as $c => $cnt) {
    if (textoCorrespondePalavrasChave((string)$c, $palavrasComumBI)) {
        $totalDaComum += $cnt;
    }
}
$totalForaComum  = max(0, $periodoTotais['cadastros'] - $totalDaComum);
$totalIdades     = array_sum(array_intersect_key($periodoTotais, array_flip(['age_0_3','age_4_7','age_8_11','age_12_14','age_15_17']))) ?: 1;

// Sexo (M/F) — agregado do período selecionado
$totalMeninos = $periodoTotais['total_meninos'];
$totalMeninas = $periodoTotais['total_meninas'];
$cadastrosHoje = 0;
$meninosHoje = 0;
$meninasHoje = 0;
$portariaHoje = [];
$idadesHoje = ['0-3'=>0,'4-7'=>0,'8-11'=>0,'12-14'=>0,'15-17'=>0];
foreach ($todosOsCadastros as $c) {
    // Dados do dia (em tempo real) — usa $dataSelecionada
    $criadoEm = $c['created_at'] ?? '';
    if (strpos($criadoEm, $dataSelecionada) === 0) {
        $cadastrosHoje++;
        $sexo = strtoupper(trim($c['sexo'] ?? ''));
        if ($sexo === 'M') $meninosHoje++;
        elseif ($sexo === 'F') $meninasHoje++;
        $port = strtoupper(trim($c['portaria'] ?? ''));
        if ($port) $portariaHoje[$port] = ($portariaHoje[$port] ?? 0) + 1;
        $idade = (int)($c['idade'] ?? 0);
        if ($idade <= 3) $idadesHoje['0-3']++;
        elseif ($idade <= 7) $idadesHoje['4-7']++;
        elseif ($idade <= 11) $idadesHoje['8-11']++;
        elseif ($idade <= 14) $idadesHoje['12-14']++;
        else $idadesHoje['15-17']++;
    }
}
$totalSexo = max(1, $totalMeninos + $totalMeninas);
arsort($portariaHoje);

// Hoje
$todayRow = null;
foreach ($diasRows as $r) { if ($r['date'] === $hoje) { $todayRow = $r; break; } }

// Auxiliar: largura de barra
function _bar(int $val, int $max): int { return $max > 0 ? (int)round($val / $max * 100) : 0; }
$maxPortaria = $portariaAgr ? max($portariaAgr) : 1;

$instNome = (defined('INSTANCE_COMUM') && INSTANCE_COMUM ? ucfirst(INSTANCE_COMUM) : '')
          . (defined('INSTANCE_CIDADE') && INSTANCE_CIDADE ? ' / ' . INSTANCE_CIDADE : '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatísticas — <?php echo sanitize_for_html($instNome ?: 'EBI'); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background:#f4f6f9; font-family:'Inter',sans-serif; padding:20px; }
        .card-stat { border:none; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.08); padding:16px 20px; }
        .card-stat .num { font-size:2rem; font-weight:700; line-height:1; }
        .card-stat .lbl { font-size:.78rem; color:#6c757d; margin-top:4px; }
        .age-badge { display:inline-block; width:100%; border-radius:8px; padding:8px 12px; margin-bottom:6px; color:#fff; font-size:.85rem; }
        .bar-wrap { background:#e9ecef; border-radius:4px; height:18px; overflow:hidden; }
        .bar-fill  { height:100%; border-radius:4px; transition:width .4s; }
        .periodo-btn.active { font-weight:700; }
        .back-btn { color:#495057; text-decoration:none; font-size:.9rem; }
        .back-btn:hover { color:#212529; }
        table thead th { font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; color:#6c757d; border-top:0; }
        .section-title { font-size:.9rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#495057; margin-bottom:12px; }
    </style>
</head>
<body>
<div class="container-fluid" style="max-width:960px">

    <!-- Cabeçalho -->
    <div class="d-flex align-items-center mb-3">
        <a href="<?php echo sanitize_for_html($_SERVER['PHP_SELF']); ?>" class="back-btn mr-3">
            <i class="fas fa-arrow-left mr-1"></i>Voltar
        </a>
        <div>
            <h4 class="mb-0"><i class="fas fa-chart-bar mr-2 text-primary"></i>Estatísticas</h4>
            <?php if ($instNome): ?>
                <small class="text-muted"><?php echo sanitize_for_html($instNome); ?></small>
            <?php endif; ?>
        </div>
        <div class="ml-auto">
            <?php foreach ([1=>'1D',7=>'7d',30=>'30d',90=>'90d',365=>'1 ano'] as $v=>$l): ?>
                <a href="?acao=stats&periodo=<?php echo $v; ?>"
                   class="btn btn-sm mr-1 periodo-btn <?php echo $periodo===$v ? 'btn-primary active' : 'btn-outline-secondary'; ?>">
                    <?php echo $l; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cards resumo período -->
    <div class="row mb-3">
        <div class="col-6 col-md-3 mb-2">
            <div class="card-stat bg-white">
                <div class="num text-primary"><?php echo number_format($periodoTotais['cadastros']); ?></div>
                <div class="lbl"><i class="fas fa-baby mr-1"></i>Cadastros (<?php echo $periodo; ?>d)</div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="card-stat bg-white">
                <div class="num text-success"><?php echo number_format($periodoTotais['impressoes']); ?></div>
                <div class="lbl"><i class="fas fa-print mr-1"></i>Pulseiras (<?php echo $periodo; ?>d)</div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="card-stat bg-white">
                <div class="num text-warning"><?php echo number_format($periodoTotais['saidas']); ?></div>
                <div class="lbl"><i class="fas fa-sign-out-alt mr-1"></i>Saídas (<?php echo $periodo; ?>d)</div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="card-stat bg-white">
                <div class="num text-secondary"><?php echo number_format((int)($totais['total_cadastros'] ?? 0)); ?></div>
                <div class="lbl"><i class="fas fa-database mr-1"></i>Total histórico</div>
            </div>
        </div>
    </div>

    <!-- Dados do dia (tempo real) com seletor de data -->
    <div class="card border-0 shadow-sm mb-3 p-3" style="border-radius:12px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="section-title mb-0" style="color:rgba(255,255,255,.7)">
                <?php echo $dataSelecionada === $hoje ? 'Hoje' : date('d/m/Y', strtotime($dataSelecionada)); ?> (tempo real)
            </div>
            <form class="d-inline-flex align-items-center" method="get" style="gap:6px;">
                <input type="hidden" name="acao" value="stats">
                <input type="hidden" name="periodo" value="<?php echo $periodo; ?>">
                <input type="date" name="data" value="<?php echo $dataSelecionada; ?>" max="<?php echo $hoje; ?>"
                       style="border:none;border-radius:6px;padding:3px 8px;font-size:.78rem;background:rgba(255,255,255,.9);color:#333;"
                       onchange="this.form.submit()">
                <?php if ($dataSelecionada !== $hoje): ?>
                    <a href="?acao=stats&periodo=<?php echo $periodo; ?>" style="color:#fff;font-size:.75rem;text-decoration:underline;">Hoje</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="row text-center mb-2">
            <div class="col-3"><div style="font-size:1.8rem;font-weight:700"><?php echo $cadastrosHoje; ?></div><div style="font-size:.72rem;opacity:.8">Cadastros</div></div>
            <div class="col-3"><div style="font-size:1.8rem;font-weight:700"><?php echo $meninosHoje; ?></div><div style="font-size:.72rem;opacity:.8">👦 Meninos</div></div>
            <div class="col-3"><div style="font-size:1.8rem;font-weight:700"><?php echo $meninasHoje; ?></div><div style="font-size:.72rem;opacity:.8">👧 Meninas</div></div>
            <div class="col-3"><div style="font-size:1.8rem;font-weight:700"><?php echo $todayRow ? (int)$todayRow['total_saidas'] : 0; ?></div><div style="font-size:.72rem;opacity:.8">Saídas</div></div>
        </div>
        <?php if (!empty($portariaHoje)): ?>
        <div style="font-size:.75rem;opacity:.85;border-top:1px solid rgba(255,255,255,.2);padding-top:8px;">
            <strong>Portarias:</strong>
            <?php foreach ($portariaHoje as $p => $cnt): ?>
                <span class="mr-2"><?php echo sanitize_for_html($p); ?>: <?php echo $cnt; ?></span>
            <?php endforeach; ?>
            &nbsp;|&nbsp;
            <strong>Idades:</strong>
            <?php foreach ($idadesHoje as $faixa => $cnt): if ($cnt > 0): ?>
                <span class="mr-2"><?php echo $faixa; ?>: <?php echo $cnt; ?></span>
            <?php endif; endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($cadastrosHoje === 0): ?>
        <div style="font-size:.8rem;opacity:.7;text-align:center;padding:8px 0;">Nenhum cadastro nesta data.</div>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Faixas etárias -->
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm p-3" style="border-radius:12px">
                <div class="section-title">Faixa Etária (<?php echo $periodo; ?>d)</div>
                <?php
                $ageGroups = [
                    'age_0_3'   => ['0–3 anos',  '#4e73df'],
                    'age_4_7'   => ['4–7 anos',  '#1cc88a'],
                    'age_8_11'  => ['8–11 anos', '#36b9cc'],
                    'age_12_14' => ['12–14 anos','#f6c23e'],
                    'age_15_17' => ['15–17 anos','#e74a3b'],
                ];
                foreach ($ageGroups as $key => [$label, $color]):
                    $val = (int)($periodoTotais[$key] ?? 0);
                    $pct = round($val / $totalIdades * 100);
                ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <small><?php echo $label; ?></small>
                        <small class="font-weight-bold"><?php echo $val; ?> <span class="text-muted">(<?php echo $pct; ?>%)</span></small>
                    </div>
                    <div class="bar-wrap">
                        <div class="bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Da comum vs fora -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius:12px">
                <div class="section-title">Origem (<?php echo $periodo; ?>d)</div>
                <?php
                $totalOrig = max(1, $totalDaComum + $totalForaComum);
                $pctComum  = round($totalDaComum  / $totalOrig * 100);
                $pctFora   = round($totalForaComum / $totalOrig * 100);
                ?>
                <div class="text-center mb-3">
                    <div style="font-size:2rem;font-weight:700;color:#1cc88a"><?php echo $periodoTotais['cadastros']; ?></div>
                    <div class="text-muted" style="font-size:.8rem">total cadastros</div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <small><i class="fas fa-home mr-1 text-success"></i><?php echo sanitize_for_html($nomeComum); ?></small>
                        <small class="font-weight-bold"><?php echo $totalDaComum; ?></small>
                    </div>
                    <div class="bar-wrap">
                        <div class="bar-fill" style="width:<?php echo $pctComum; ?>%;background:#1cc88a"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <small><i class="fas fa-map-marker-alt mr-1 text-secondary"></i>Fora da Comum</small>
                        <small class="font-weight-bold"><?php echo $totalForaComum; ?></small>
                    </div>
                    <div class="bar-wrap">
                        <div class="bar-fill" style="width:<?php echo $pctFora; ?>%;background:#858796"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Por portaria -->
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius:12px">
                <div class="section-title">Por Portaria (<?php echo $periodo; ?>d)</div>
                <?php if (empty($portariaAgr)): ?>
                    <p class="text-muted small">Nenhum dado de portaria ainda.</p>
                <?php else: ?>
                    <?php foreach ($portariaAgr as $port => $cnt): ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="font-weight-bold"><?php echo sanitize_for_html(strtoupper($port)); ?></small>
                            <small><?php echo $cnt; ?></small>
                        </div>
                        <div class="bar-wrap">
                            <div class="bar-fill" style="width:<?php echo _bar($cnt, $maxPortaria); ?>%;background:#4e73df"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sexo (Meninos/Meninas) -->
        <div class="col-md-2 mb-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius:12px">
                <div class="section-title">Sexo (<?php echo $periodo; ?>d)</div>
                <div class="text-center mb-3">
                    <div style="font-size:2rem;font-weight:700;color:#17a2b8"><?php echo $totalMeninos + $totalMeninas; ?></div>
                    <div class="text-muted" style="font-size:.75rem">com sexo informado</div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <small>👦 Meninos</small>
                        <small class="font-weight-bold"><?php echo $totalMeninos; ?></small>
                    </div>
                    <div class="bar-wrap">
                        <div class="bar-fill" style="width:<?php echo round($totalMeninos / $totalSexo * 100); ?>%;background:#4e73df"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <small>👧 Meninas</small>
                        <small class="font-weight-bold"><?php echo $totalMeninas; ?></small>
                    </div>
                    <div class="bar-wrap">
                        <div class="bar-fill" style="width:<?php echo round($totalMeninas / $totalSexo * 100); ?>%;background:#e74a9a"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Histórico diário -->
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
        <div class="card-body p-3">
            <div class="section-title">Histórico Diário</div>
            <?php if (empty($diasRows)): ?>
                <p class="text-muted small">Sem dados para o período selecionado.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Data</th><th>Cadastros</th><th>Pulseiras</th><th>Saídas</th>
                            <th>0–3</th><th>4–7</th><th>8–11</th><th>12–14</th><th>15–17</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($diasRows) as $row): ?>
                        <tr <?php echo $row['date'] === $hoje ? 'class="table-primary"' : ''; ?>>
                            <td><?php echo date('d/m/y', strtotime($row['date'])); ?></td>
                            <td><?php echo (int)$row['total_cadastros']; ?></td>
                            <td><?php echo (int)$row['total_impressoes']; ?></td>
                            <td><?php echo (int)$row['total_saidas']; ?></td>
                            <td><?php echo (int)$row['age_0_3']; ?></td>
                            <td><?php echo (int)$row['age_4_7']; ?></td>
                            <td><?php echo (int)$row['age_8_11']; ?></td>
                            <td><?php echo (int)$row['age_12_14']; ?></td>
                            <td><?php echo (int)$row['age_15_17']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resumo mensal -->
    <?php if (!empty($mensalRows)): ?>
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
        <div class="card-body p-3">
            <div class="section-title">Resumo Mensal</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Mês</th><th>Cadastros</th><th>Pulseiras</th><th>Saídas</th></tr></thead>
                    <tbody>
                        <?php foreach (array_reverse($mensalRows) as $row):
                            [$ano, $mes] = explode('-', $row['mes']);
                            $nomesMes = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                        ?>
                        <tr>
                            <td><?php echo ($nomesMes[(int)$mes] ?? $mes) . '/' . $ano; ?></td>
                            <td><?php echo (int)$row['total_cadastros']; ?></td>
                            <td><?php echo (int)$row['total_impressoes']; ?></td>
                            <td><?php echo (int)$row['total_saidas']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-center mt-3 mb-2" style="font-size:9px;color:#b0b0b0;opacity:0.6">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
