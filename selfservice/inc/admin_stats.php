<?php
/**
 * Aba de Estatísticas EBI — painel administrativo.
 * Todos os dados vêm de admin_daily_stats via db_manager.php (já carregado).
 */

$periodo = (int)($_GET['periodo'] ?? 30);
if (!in_array($periodo, [7, 30, 90, 365], true)) {
    $periodo = 30;
}
$desde = date('Y-m-d', strtotime("-{$periodo} days"));

$statsPorDia       = db_stats_por_dia($desde);
$statsPorComum     = db_stats_por_comum($desde);
$statsPorCidade    = db_stats_por_cidade($desde);
$statsPorInstancia = db_stats_por_instancia($desde);
$statsHoje         = db_stats_totais_hoje();
$statsGeral        = db_stats_totais_geral();
$statsMensal       = db_stats_mensal(12);

// Agrega faixas etárias do período
$ageTotals = ['age_0_3' => 0, 'age_4_7' => 0, 'age_8_11' => 0, 'age_12_14' => 0, 'age_15_17' => 0];
foreach ($statsPorDia as $row) {
    foreach ($ageTotals as $k => $_) {
        $ageTotals[$k] += (int)($row[$k] ?? 0);
    }
}
$totalCriancasPeriodo = array_sum($ageTotals) ?: 1; // evita divisão por zero
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h2><i class="fas fa-chart-bar mr-2"></i>Estatísticas EBI</h2>
            <p class="text-muted mb-0">Agregado de todas as instâncias ativas</p>
        </div>
        <div>
            <form method="get" action="admin.php" class="form-inline">
                <input type="hidden" name="page" value="stats">
                <label class="mr-2 font-weight-bold">Período:</label>
                <?php foreach ([7 => '7 dias', 30 => '30 dias', 90 => '90 dias', 365 => '12 meses'] as $v => $l): ?>
                    <a href="?page=stats&periodo=<?php echo $v; ?>"
                       class="btn btn-sm mr-1 <?php echo $periodo === $v ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        <?php echo $l; ?>
                    </a>
                <?php endforeach; ?>
            </form>
        </div>
    </div>
</div>

<!-- Cards de resumo geral -->
<div class="row">
    <div class="col-md-3">
        <div class="card stats-card primary">
            <div class="text-center">
                <i class="fas fa-baby icon"></i>
                <h3 class="mb-0"><?php echo number_format((int)($statsGeral['total_cadastros'] ?? 0)); ?></h3>
                <p class="mb-0">Cadastros (total)</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card success">
            <div class="text-center">
                <i class="fas fa-print icon"></i>
                <h3 class="mb-0"><?php echo number_format((int)($statsGeral['total_impressoes'] ?? 0)); ?></h3>
                <p class="mb-0">Impressões (total)</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card warning">
            <div class="text-center">
                <i class="fas fa-sign-out-alt icon"></i>
                <h3 class="mb-0"><?php echo number_format((int)($statsGeral['total_saidas'] ?? 0)); ?></h3>
                <p class="mb-0">Saídas (total)</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card info">
            <div class="text-center">
                <i class="fas fa-calendar-day icon"></i>
                <h3 class="mb-0"><?php echo number_format((int)($statsHoje['cadastros_hoje'] ?? 0)); ?></h3>
                <p class="mb-0">Cadastros Hoje</p>
            </div>
        </div>
    </div>
</div>

<!-- Cards de hoje (detalhado) -->
<div class="row mt-2">
    <div class="col-md-4">
        <div class="card border-left-success" style="border-left: 4px solid #28a745;">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div><small class="text-muted">Impressões hoje</small><br>
                        <strong class="h5"><?php echo (int)($statsHoje['impressoes_hoje'] ?? 0); ?></strong></div>
                    <i class="fas fa-print fa-2x text-success"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-left-warning" style="border-left: 4px solid #ffc107;">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div><small class="text-muted">Saídas hoje</small><br>
                        <strong class="h5"><?php echo (int)($statsHoje['saidas_hoje'] ?? 0); ?></strong></div>
                    <i class="fas fa-sign-out-alt fa-2x text-warning"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-left-primary" style="border-left: 4px solid #667eea;">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div><small class="text-muted">Instâncias com dados</small><br>
                        <strong class="h5"><?php echo (int)($statsGeral['instancias_com_dados'] ?? 0); ?></strong></div>
                    <i class="fas fa-server fa-2x text-primary"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Distribuição por faixa etária -->
<div class="content-header mt-4">
    <h4><i class="fas fa-child mr-2"></i>Distribuição por Faixa Etária
        <small class="text-muted">(últimos <?php echo $periodo; ?> dias)</small>
    </h4>
</div>
<div class="row">
    <?php
    $faixas = [
        'age_0_3'   => ['label' => '0–3 anos',   'color' => '#667eea'],
        'age_4_7'   => ['label' => '4–7 anos',   'color' => '#56CCF2'],
        'age_8_11'  => ['label' => '8–11 anos',  'color' => '#F2994A'],
        'age_12_14' => ['label' => '12–14 anos', 'color' => '#EB3349'],
        'age_15_17' => ['label' => '15–17 anos', 'color' => '#6fcf97'],
    ];
    foreach ($faixas as $key => $info):
        $qtd = (int)($ageTotals[$key] ?? 0);
        $pct = round($qtd / $totalCriancasPeriodo * 100, 1);
    ?>
    <div class="col-md-2 col-sm-4 mb-3">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div style="width:70px;height:70px;border-radius:50%;background:<?php echo $info['color']; ?>;
                            display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                    <span style="color:#fff;font-weight:700;font-size:1.1rem"><?php echo $pct; ?>%</span>
                </div>
                <strong><?php echo $qtd; ?></strong><br>
                <small class="text-muted"><?php echo $info['label']; ?></small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Histórico por dia -->
<div class="content-header mt-2">
    <h4><i class="fas fa-calendar-alt mr-2"></i>Histórico Diário
        <small class="text-muted">(últimos <?php echo $periodo; ?> dias)</small>
    </h4>
</div>
<div class="table-custom">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Data</th>
                <th class="text-right">Cadastros</th>
                <th class="text-right">Impressões</th>
                <th class="text-right">Saídas</th>
                <th class="text-right">0–3</th>
                <th class="text-right">4–7</th>
                <th class="text-right">8–11</th>
                <th class="text-right">12–14</th>
                <th class="text-right">15–17</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($statsPorDia)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Nenhum dado no período.</td></tr>
        <?php else: ?>
            <?php foreach (array_reverse($statsPorDia) as $row): ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($row['date'])); ?></td>
                <td class="text-right"><?php echo (int)$row['total_cadastros']; ?></td>
                <td class="text-right"><?php echo (int)$row['total_impressoes']; ?></td>
                <td class="text-right"><?php echo (int)$row['total_saidas']; ?></td>
                <td class="text-right"><?php echo (int)($row['age_0_3']   ?? 0); ?></td>
                <td class="text-right"><?php echo (int)($row['age_4_7']   ?? 0); ?></td>
                <td class="text-right"><?php echo (int)($row['age_8_11']  ?? 0); ?></td>
                <td class="text-right"><?php echo (int)($row['age_12_14'] ?? 0); ?></td>
                <td class="text-right"><?php echo (int)($row['age_15_17'] ?? 0); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Estatísticas mensais -->
<?php if (!empty($statsMensal)): ?>
<div class="content-header mt-4">
    <h4><i class="fas fa-chart-line mr-2"></i>Resumo Mensal (12 meses)</h4>
</div>
<div class="table-custom">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Mês</th>
                <th class="text-right">Cadastros</th>
                <th class="text-right">Impressões</th>
                <th class="text-right">Saídas</th>
                <th>Barra</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $maxMensal = max(array_column($statsMensal, 'total_cadastros') ?: [1]);
        foreach (array_reverse($statsMensal) as $row):
            $pctBar = $maxMensal > 0 ? round($row['total_cadastros'] / $maxMensal * 100) : 0;
        ?>
            <tr>
                <td><?php echo htmlspecialchars($row['mes']); ?></td>
                <td class="text-right"><?php echo number_format((int)$row['total_cadastros']); ?></td>
                <td class="text-right"><?php echo number_format((int)$row['total_impressoes']); ?></td>
                <td class="text-right"><?php echo number_format((int)$row['total_saidas']); ?></td>
                <td style="min-width:120px">
                    <div style="background:#667eea;height:14px;border-radius:4px;width:<?php echo $pctBar; ?>%"></div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Por Comum -->
<?php if (!empty($statsPorComum)): ?>
<div class="content-header mt-4">
    <h4><i class="fas fa-church mr-2"></i>Por Comum
        <small class="text-muted">(últimos <?php echo $periodo; ?> dias)</small>
    </h4>
</div>
<div class="table-custom">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Comum</th>
                <th class="text-right">Cadastros</th>
                <th class="text-right">Impressões</th>
                <th class="text-right">Saídas</th>
                <th class="text-right">Dias ativos</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($statsPorComum as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['comum']); ?></td>
                <td class="text-right"><?php echo number_format((int)$row['total_cadastros']); ?></td>
                <td class="text-right"><?php echo number_format((int)$row['total_impressoes']); ?></td>
                <td class="text-right"><?php echo number_format((int)$row['total_saidas']); ?></td>
                <td class="text-right"><?php echo (int)$row['dias_ativos']; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Por Instância (Comum / Cidade) -->
<?php if (!empty($statsPorInstancia)): ?>
<div class="content-header mt-4">
    <h4><i class="fas fa-sitemap mr-2"></i>Por Instância
        <small class="text-muted">(últimos <?php echo $periodo; ?> dias)</small>
    </h4>
</div>
<div class="table-custom">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Comum</th>
                <th>Cidade</th>
                <th class="text-right">Cadastros</th>
                <th class="text-right">Pulseiras</th>
                <th class="text-right">Saídas</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($statsPorInstancia as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars(ucfirst($row['comum'] ?? '—')); ?></td>
                <td><?php echo htmlspecialchars($row['cidade'] ?? '—'); ?></td>
                <td class="text-right"><?php echo number_format((int)$row['total_cadastros']); ?></td>
                <td class="text-right"><?php echo number_format((int)$row['total_impressoes']); ?></td>
                <td class="text-right"><?php echo number_format((int)$row['total_saidas']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Por Cidade -->
<?php if (!empty($statsPorCidade)): ?>
<div class="content-header mt-4">
    <h4><i class="fas fa-map-marker-alt mr-2"></i>Por Cidade
        <small class="text-muted">(últimos <?php echo $periodo; ?> dias)</small>
    </h4>
</div>
<div class="table-custom">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Cidade</th>
                <th class="text-right">Cadastros</th>
                <th class="text-right">Instâncias</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($statsPorCidade as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['cidade']); ?></td>
                <td class="text-right"><?php echo number_format((int)$row['total_cadastros']); ?></td>
                <td class="text-right"><?php echo (int)$row['instancias']; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
