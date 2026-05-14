<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_login();

$user = current_user();
assert($user !== null);
$pdo = get_pdo();

$perPage = 8;
$page    = max(1, (int) ($_GET['page'] ?? 1));

$stTotal = $pdo->prepare('SELECT COUNT(*) FROM pedidos WHERE usuario_id = ?');
$stTotal->execute([$user['id']]);
$totalOrders = (int) $stTotal->fetchColumn();

$totalPages = max(1, (int) ceil($totalOrders / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$st = $pdo->prepare(
    "SELECT o.id, o.total, o.estado AS status, o.creado_en AS created_at,
            COUNT(l.id) AS items_count
     FROM pedidos o
     LEFT JOIN lineas_pedido l ON l.pedido_id = o.id
     WHERE o.usuario_id = ?
     GROUP BY o.id
     ORDER BY o.creado_en DESC
     LIMIT $perPage OFFSET $offset"
);
$st->execute([$user['id']]);
$orders = $st->fetchAll();

$pageTitle = 'Mis pedidos';
require dirname(__DIR__) . '/includes/header.php';
?>

<nav aria-label="Ruta de navegación">
    <ol class="breadcrumb">
        <li><a href="/index.php">Inicio</a></li>
        <li aria-current="page">Mis pedidos</li>
    </ol>
</nav>

<h1>Mis pedidos</h1>
<p class="lead">Historial de compras de <strong><?= h($user['name']) ?></strong>.</p>

<?php if ($orders === []): ?>
    <p class="msg msg-info">Aún no has realizado ningún pedido. <a href="/catalog.php">Explorar catálogo</a></p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table-cart">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Fecha</th>
                    <th>Artículos</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><strong>#<?= (int) $o['id'] ?></strong></td>
                        <td class="muted"><?= h(date('d/m/Y H:i', strtotime((string) $o['created_at']))) ?></td>
                        <td><?= (int) $o['items_count'] ?> art.</td>
                        <td><strong><?= number_format((float) $o['total'], 2, ',', ' ') ?> €</strong></td>
                        <td><span class="badge badge-green"><?= h((string) $o['status']) ?></span></td>
                        <td><a href="/order_confirm.php?id=<?= (int) $o['id'] ?>" class="btn btn-small btn-outline">Ver detalle</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Páginas de pedidos">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="btn btn-outline btn-small">← Anterior</a>
            <?php endif; ?>
            <span class="pagination-info">Página <?= $page ?> de <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>" class="btn btn-outline btn-small">Siguiente →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<p class="actions-row">
    <a href="/catalog.php" class="btn btn-primary">Seguir comprando</a>
</p>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
