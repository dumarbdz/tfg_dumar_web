<?php
declare(strict_types=1);

// ── Exportar a CSV/Excel ─────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
    require_admin();
    $pdo = get_pdo();

    $rows = $pdo->query(
        "SELECT o.id, o.creado_en, u.nombre AS cliente, u.email, o.total,
                o.estado, o.metodo_pago,
                o.envio_nombre, o.envio_linea1, o.envio_postal, o.envio_ciudad, o.envio_pais
         FROM pedidos o JOIN usuarios u ON u.id = o.usuario_id
         ORDER BY o.creado_en DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="pedidos_' . date('Ymd_His') . '.csv"');
    header('Cache-Control: no-cache');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
    fputcsv($out, ['#', 'Fecha', 'Cliente', 'Email', 'Total (€)', 'Estado', 'Pago',
                   'Nombre envío', 'Dirección', 'CP', 'Ciudad', 'País'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['creado_en'],
            $r['cliente'],
            $r['email'],
            number_format((float) $r['total'], 2, ',', '.'),
            $r['estado'],
            $r['metodo_pago'],
            $r['envio_nombre'],
            $r['envio_linea1'],
            $r['envio_postal'],
            $r['envio_ciudad'],
            $r['envio_pais'],
        ], ';');
    }
    fclose($out);
    exit;
}

$pageTitle    = 'Pedidos';
$adminSection = 'orders';
require __DIR__ . '/admin_layout.php';

$pdo = get_pdo();

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$search       = trim((string) ($_GET['q'] ?? ''));

$validStatuses = ['', 'pending', 'completed', 'shipped', 'cancelled'];
if (!in_array($statusFilter, $validStatuses, true)) $statusFilter = '';

$perPage = 15;
$page    = max(1, (int) ($_GET['page'] ?? 1));

$conditions = [];
$params     = [];
if ($statusFilter !== '') {
    $conditions[] = 'o.estado = ?';
    $params[]     = $statusFilter;
}
if ($search !== '') {
    if (ctype_digit($search)) {
        $conditions[] = 'o.id = ?';
        $params[]     = (int) $search;
    } else {
        $conditions[] = 'u.email LIKE ?';
        $params[]     = '%' . $search . '%';
    }
}
$where = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stCount = $pdo->prepare("SELECT COUNT(*) FROM pedidos o JOIN usuarios u ON u.id = o.usuario_id $where");
$stCount->execute($params);
$total = (int) $stCount->fetchColumn();

$pages  = max(1, (int) ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stOrders = $pdo->prepare(
    "SELECT o.id, o.total, o.estado AS status, o.creado_en AS created_at, o.metodo_pago AS payment_method,
            u.nombre AS user_name, u.email AS user_email
     FROM pedidos o JOIN usuarios u ON u.id = o.usuario_id
     $where
     ORDER BY o.creado_en DESC
     LIMIT $perPage OFFSET $offset"
);
$stOrders->execute($params);
$orders = $stOrders->fetchAll();

$statusLabels = [
    ''          => 'Todos',
    'pending'   => 'Pendiente',
    'completed' => 'Completado',
    'shipped'   => 'Enviado',
    'cancelled' => 'Cancelado',
];

$paginationBase = '?status=' . urlencode($statusFilter) . ($search !== '' ? '&q=' . urlencode($search) : '');
?>

<h1 class="adm-page-title">Pedidos</h1>

<div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;margin-bottom:1.25rem">

    <!-- Búsqueda -->
    <form method="get" style="display:flex;gap:.4rem;align-items:center">
        <input type="hidden" name="status" value="<?= h($statusFilter) ?>">
        <input type="search" name="q" value="<?= h($search) ?>"
               placeholder="Email o nº pedido…"
               style="padding:.35rem .7rem;border:1.5px solid #c3e0cf;border-radius:4px;font:inherit;font-size:.85rem;width:220px">
        <button type="submit" class="adm-btn adm-btn-outline adm-btn-sm">Buscar</button>
        <?php if ($search !== ''): ?>
            <a href="?status=<?= urlencode($statusFilter) ?>" class="adm-btn adm-btn-outline adm-btn-sm">✕</a>
        <?php endif; ?>
    </form>

    <!-- Filtros estado -->
    <div style="display:flex;gap:.4rem;flex-wrap:wrap">
        <?php foreach ($statusLabels as $val => $lbl): ?>
            <a href="?status=<?= urlencode($val) ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>"
               class="adm-btn adm-btn-sm <?= $val === $statusFilter ? 'adm-btn-green' : 'adm-btn-outline' ?>">
                <?= h($lbl) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <a href="?export=csv" class="adm-btn adm-btn-sm adm-btn-outline" style="margin-left:auto">
        ⬇ Exportar Excel
    </a>
    <span style="color:#7a9282;font-size:.82rem"><?= $total ?> pedido<?= $total !== 1 ? 's' : '' ?></span>
</div>

<div class="adm-table-wrap">
    <table class="adm-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Pago</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <?php
            $badgeClass = match($o['status']) {
                'completed' => 'adm-badge-green',
                'shipped'   => 'adm-badge-blue',
                'pending'   => 'adm-badge-yellow',
                'cancelled' => 'adm-badge-red',
                default     => 'adm-badge-gray',
            };
            $statusLbl = $statusLabels[$o['status']] ?? h($o['status']);
            ?>
            <tr>
                <td><strong>#<?= (int) $o['id'] ?></strong></td>
                <td><?= h($o['user_name']) ?><br><small style="color:#7a9282"><?= h($o['user_email']) ?></small></td>
                <td><strong><?= number_format((float) $o['total'], 2, ',', ' ') ?> €</strong></td>
                <td style="text-transform:capitalize"><?= h($o['payment_method']) ?></td>
                <td><span class="adm-badge <?= $badgeClass ?>"><?= $statusLbl ?></span></td>
                <td style="color:#7a9282;font-size:.82rem"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                <td><a href="/admin/order_detail.php?id=<?= (int) $o['id'] ?>" class="adm-btn adm-btn-outline adm-btn-sm">Ver / Editar</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($orders === []): ?>
            <tr><td colspan="7" style="text-align:center;color:#7a9282;padding:2rem">No hay pedidos.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($pages > 1): ?>
<div class="adm-pagination">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="<?= h($paginationBase) ?>&page=<?= $i ?>"
           class="adm-btn <?= $i === $page ? 'adm-btn-green' : 'adm-btn-outline' ?> adm-btn-sm"><?= $i ?></a>
    <?php endfor; ?>
    <span class="adm-pag-info">Página <?= $page ?> de <?= $pages ?></span>
</div>
<?php endif; ?>

<?php require __DIR__ . '/admin_layout_end.php'; ?>
