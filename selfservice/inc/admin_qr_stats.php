<?php
/**
 * Estatísticas agregadas dos geradores públicos de QR Code.
 * Nenhum dado pessoal é lido ou exibido nesta página.
 */

$source = $_GET['origem'] ?? 'infantil';
if (!in_array($source, ['infantil', 'reuniao'], true)) {
    $source = 'infantil';
}

$period = (int)($_GET['periodo'] ?? 30);
if (!in_array($period, [7, 30], true)) {
    $period = 30;
}

$since = date('Y-m-d', strtotime('-' . ($period - 1) . ' days'));
$isInfantil = $source === 'infantil';
$sourceLabel = $isInfantil ? 'QR Infantil' : 'QR Reunião / Região';
$totals = db_qr_stats_totais($source, $since);
$dailyRows = db_qr_stats_por_dia($source, $since);
$byCommon = db_qr_stats_por_dimensao($source, $since, 'comum');
$byCity = db_qr_stats_por_dimensao($source, $since, 'cidade');
$byFunction = $isInfantil ? [] : db_qr_stats_por_dimensao($source, $since, 'funcao');
$bySex = $isInfantil ? db_qr_stats_por_dimensao($source, $since, 'sexo') : [];
$byAge = $isInfantil ? db_qr_stats_por_dimensao($source, $since, 'idade') : [];

$dailyByDate = [];
foreach ($dailyRows as $dailyRow) {
    $dailyByDate[$dailyRow['date']] = $dailyRow;
}

$dailyHistory = [];
for ($daysAgo = $period - 1; $daysAgo >= 0; $daysAgo--) {
    $date = date('Y-m-d', strtotime("-{$daysAgo} days"));
    $dailyHistory[] = [
        'date' => $date,
        'qrcodes' => (int)($dailyByDate[$date]['qrcodes'] ?? 0),
        'criancas' => (int)($dailyByDate[$date]['criancas'] ?? 0),
    ];
}

$activeDays = count(array_filter($dailyHistory, static fn(array $day): bool => $day['qrcodes'] > 0));

function qr_stats_escape(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function qr_stats_dimension_table(string $title, string $icon, string $column, array $rows, ?callable $format = null): void {
    ?>
    <div class="content-header mt-4 mb-3">
        <h4><i class="fas <?php echo qr_stats_escape($icon); ?> mr-2"></i><?php echo qr_stats_escape($title); ?></h4>
    </div>
    <div class="table-custom mb-4">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th><?php echo qr_stats_escape($column); ?></th>
                    <th class="text-right">QR Codes</th>
                    <th class="text-right">Dias ativos</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">Nenhum dado no período.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <?php $label = $format ? $format($row['dimension_value']) : $row['dimension_value']; ?>
                    <tr>
                        <td><?php echo qr_stats_escape($label); ?></td>
                        <td class="text-right"><?php echo number_format((int)$row['total']); ?></td>
                        <td class="text-right"><?php echo number_format((int)$row['dias_ativos']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h2><i class="fas fa-qrcode mr-2"></i>Estatísticas de QR Code</h2>
            <p class="text-muted mb-0"><?php echo qr_stats_escape($sourceLabel); ?></p>
        </div>
        <div class="mt-2 mt-md-0">
            <?php foreach ([7 => '7 dias', 30 => '30 dias'] as $value => $label): ?>
                <a href="?page=qr-stats&amp;origem=<?php echo qr_stats_escape($source); ?>&amp;periodo=<?php echo $value; ?>"
                   class="btn btn-sm mr-1 <?php echo $period === $value ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                    <?php echo qr_stats_escape($label); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="mt-3">
        <a href="?page=qr-stats&amp;origem=infantil&amp;periodo=<?php echo $period; ?>"
           class="btn btn-sm mr-1 <?php echo $isInfantil ? 'btn-info' : 'btn-outline-info'; ?>">
            <i class="fas fa-child mr-1"></i>QR Infantil
        </a>
        <a href="?page=qr-stats&amp;origem=reuniao&amp;periodo=<?php echo $period; ?>"
           class="btn btn-sm <?php echo !$isInfantil ? 'btn-info' : 'btn-outline-info'; ?>">
            <i class="fas fa-users mr-1"></i>QR Reunião / Região
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card stats-card primary">
            <div class="text-center">
                <i class="fas fa-qrcode icon"></i>
                <h3 class="mb-0"><?php echo number_format((int)$totals['qrcodes']); ?></h3>
                <p class="mb-0">QR Codes gerados</p>
            </div>
        </div>
    </div>
    <?php if ($isInfantil): ?>
        <div class="col-md-4">
            <div class="card stats-card success">
                <div class="text-center">
                    <i class="fas fa-child icon"></i>
                    <h3 class="mb-0"><?php echo number_format((int)$totals['criancas']); ?></h3>
                    <p class="mb-0">Crianças incluídas</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="col-md-4">
        <div class="card stats-card info">
            <div class="text-center">
                <i class="fas fa-calendar-check icon"></i>
                <h3 class="mb-0"><?php echo number_format($activeDays); ?></h3>
                <p class="mb-0">Dias com geração</p>
            </div>
        </div>
    </div>
</div>

<div class="content-header mt-4 mb-3">
    <h4><i class="fas fa-calendar-alt mr-2"></i>Por Dia <small class="text-muted">(últimos <?php echo $period; ?> dias)</small></h4>
</div>
<div class="table-custom mb-4">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Data</th>
                <th class="text-right">QR Codes</th>
                <?php if ($isInfantil): ?><th class="text-right">Crianças</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach (array_reverse($dailyHistory) as $day): ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($day['date'])); ?></td>
                <td class="text-right"><?php echo number_format($day['qrcodes']); ?></td>
                <?php if ($isInfantil): ?><td class="text-right"><?php echo number_format($day['criancas']); ?></td><?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="row">
    <div class="col-lg-6">
        <?php qr_stats_dimension_table('Por Comum', 'fa-church', 'Comum', $byCommon); ?>
    </div>
    <div class="col-lg-6">
        <?php qr_stats_dimension_table('Por Cidade', 'fa-map-marker-alt', 'Cidade', $byCity); ?>
    </div>
</div>

<?php if ($isInfantil): ?>
    <div class="row">
        <div class="col-lg-6">
            <?php qr_stats_dimension_table('Por Idade', 'fa-birthday-cake', 'Idade', $byAge, static fn($age): string => $age . ' anos'); ?>
        </div>
        <div class="col-lg-6">
            <?php qr_stats_dimension_table('Por Sexo', 'fa-venus-mars', 'Sexo', $bySex, static fn($sex): string => $sex === 'F' ? 'Meninas' : 'Meninos'); ?>
        </div>
    </div>
<?php else: ?>
    <?php qr_stats_dimension_table('Por Função', 'fa-user-tag', 'Função', $byFunction); ?>
<?php endif; ?>