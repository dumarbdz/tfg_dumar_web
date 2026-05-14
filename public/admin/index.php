<?php
declare(strict_types=1);
$pageTitle    = 'Dashboard';
$adminSection = 'dashboard';
require __DIR__ . '/admin_layout.php';

$pdo = get_pdo();

$totalProducts  = (int) $pdo->query('SELECT COUNT(*) FROM productos WHERE activo = 1')->fetchColumn();
$totalOrders    = (int) $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn();
$totalUsers     = (int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE es_admin = 0')->fetchColumn();
$totalRevenue   = (float) ($pdo->query('SELECT COALESCE(SUM(total),0) FROM pedidos')->fetchColumn());

$recentOrders = $pdo->query(
    "SELECT o.id, o.total, o.estado AS status, o.creado_en AS created_at,
            u.nombre AS user_name, u.email AS user_email
     FROM pedidos o JOIN usuarios u ON u.id = o.usuario_id
     ORDER BY o.creado_en DESC LIMIT 8"
)->fetchAll();

// ── Gráfica 1: ventas por día (últimos 30 días) ─────────────────────────────
$salesByDay = $pdo->query(
    "SELECT DATE(creado_en) AS dia, COUNT(*) AS pedidos, SUM(total) AS ingresos
     FROM pedidos
     WHERE creado_en >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY DATE(creado_en)
     ORDER BY dia ASC"
)->fetchAll();

// Fill in missing days with zeros so the chart always has 30 bars
$dayLabels   = [];
$dayOrders   = [];
$dayRevenue  = [];
$dayMap      = [];
foreach ($salesByDay as $row) {
    $dayMap[$row['dia']] = ['pedidos' => (int)$row['pedidos'], 'ingresos' => (float)$row['ingresos']];
}
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $dayLabels[]  = date('d/m', strtotime($date));
    $dayOrders[]  = $dayMap[$date]['pedidos'] ?? 0;
    $dayRevenue[] = $dayMap[$date]['ingresos'] ?? 0.0;
}

// ── Gráfica 2: pedidos por estado ──────────────────────────────────────────
$byStatus = $pdo->query(
    "SELECT estado, COUNT(*) AS total FROM pedidos GROUP BY estado"
)->fetchAll();
$statusMap = ['pending' => 'Pendiente', 'shipped' => 'Enviado', 'completed' => 'Completado', 'cancelled' => 'Cancelado'];
$statusColors = ['pending' => '#fde68a', 'shipped' => '#bfdbfe', 'completed' => '#a7f3cc', 'cancelled' => '#fecaca'];
$stLabels  = array_map(static fn($r) => $statusMap[$r['estado']] ?? $r['estado'], $byStatus);
$stTotals  = array_map(static fn($r) => (int)$r['total'], $byStatus);
$stColors  = array_map(static fn($r) => $statusColors[$r['estado']] ?? '#d1d5db', $byStatus);
?>

<h1 class="adm-page-title">Dashboard</h1>

<div class="adm-stats">
    <div class="adm-stat">
        <div class="adm-stat-value"><?= $totalOrders ?></div>
        <div class="adm-stat-label">Pedidos totales</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-value"><?= number_format($totalRevenue, 0, ',', '.') ?> €</div>
        <div class="adm-stat-label">Ingresos totales</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-value"><?= $totalProducts ?></div>
        <div class="adm-stat-label">Productos activos</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-value"><?= $totalUsers ?></div>
        <div class="adm-stat-label">Clientes registrados</div>
    </div>
</div>

<!-- ── Gráficas ─────────────────────────────────────────────────────────── -->
<div class="adm-charts">

    <div class="adm-chart-card adm-chart-wide">
        <h3>Pedidos e ingresos — últimos 30 días</h3>
        <canvas id="chartDailyOrders" height="90"></canvas>
    </div>

    <div class="adm-chart-card">
        <h3>Pedidos por estado</h3>
        <canvas id="chartStatus" height="180"></canvas>
    </div>

</div>

<!-- ── Últimos pedidos ───────────────────────────────────────────────────── -->
<h2 style="font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#3a4f3e;margin:0 0 .75rem;">Últimos pedidos</h2>
<div class="adm-table-wrap">
    <table class="adm-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recentOrders as $o): ?>
            <?php
            $badge = match($o['status']) {
                'completed' => 'adm-badge-green',
                'shipped'   => 'adm-badge-blue',
                'pending'   => 'adm-badge-yellow',
                'cancelled' => 'adm-badge-red',
                default     => 'adm-badge-gray',
            };
            $label = match($o['status']) {
                'completed' => 'Completado',
                'shipped'   => 'Enviado',
                'pending'   => 'Pendiente',
                'cancelled' => 'Cancelado',
                default     => h($o['status']),
            };
            ?>
            <tr>
                <td><strong>#<?= (int) $o['id'] ?></strong></td>
                <td><?= h($o['user_name']) ?><br><small style="color:#7a9282"><?= h($o['user_email']) ?></small></td>
                <td><strong><?= number_format((float)$o['total'], 2, ',', ' ') ?> €</strong></td>
                <td><span class="adm-badge <?= $badge ?>"><?= $label ?></span></td>
                <td style="color:#7a9282;font-size:.82rem"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                <td><a href="/admin/order_detail.php?id=<?= (int)$o['id'] ?>" class="adm-btn adm-btn-outline adm-btn-sm">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($recentOrders === []): ?>
            <tr><td colspan="6" style="text-align:center;color:#7a9282;padding:2rem">No hay pedidos todavía.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<p style="margin-top:.75rem"><a href="/admin/orders.php" class="adm-btn adm-btn-outline adm-btn-sm">Ver todos los pedidos →</a></p>

<script>
Chart.defaults.font.family = '"Barlow", system-ui, sans-serif';
Chart.defaults.color = '#7a9282';

// Gráfica 1 – línea+barra ventas diarias
(function () {
    const labels  = <?= json_encode($dayLabels) ?>;
    const orders  = <?= json_encode($dayOrders) ?>;
    const revenue = <?= json_encode($dayRevenue) ?>;

    new Chart(document.getElementById('chartDailyOrders'), {
        data: {
            labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Ingresos (€)',
                    data: revenue,
                    backgroundColor: 'rgba(19,138,74,0.25)',
                    borderColor: '#138a4a',
                    borderWidth: 1.5,
                    borderRadius: 3,
                    yAxisID: 'yRev',
                    order: 2,
                },
                {
                    type: 'line',
                    label: 'Pedidos',
                    data: orders,
                    borderColor: '#c47f00',
                    backgroundColor: 'rgba(196,127,0,0.15)',
                    borderWidth: 2,
                    pointRadius: 3,
                    tension: 0.35,
                    fill: true,
                    yAxisID: 'yOrd',
                    order: 1,
                },
            ],
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top' } },
            scales: {
                yRev: {
                    type: 'linear',
                    position: 'left',
                    ticks: { callback: v => v + ' €' },
                    grid: { color: 'rgba(0,0,0,0.05)' },
                },
                yOrd: {
                    type: 'linear',
                    position: 'right',
                    min: 0,
                    ticks: { stepSize: 1 },
                    grid: { drawOnChartArea: false },
                },
            },
        },
    });
})();

// Gráfica 2 – dona por estado
(function () {
    const labels = <?= json_encode($stLabels) ?>;
    const totals = <?= json_encode($stTotals) ?>;
    const colors = <?= json_encode($stColors) ?>;

    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: totals,
                backgroundColor: colors,
                borderWidth: 1.5,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } },
            },
            cutout: '65%',
        },
    });
})();
</script>

<?php require __DIR__ . '/admin_layout_end.php'; ?>
