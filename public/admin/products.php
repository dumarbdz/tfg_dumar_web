<?php
declare(strict_types=1);
$pageTitle    = 'Productos';
$adminSection = 'products';
require __DIR__ . '/admin_layout.php';

$pdo = get_pdo();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_active'])) {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $err = 'Sesión inválida.';
    } else {
        $pid    = (int) $_POST['product_id'];
        $active = (bool) (int) $_POST['new_active'];
        $pdo->prepare('UPDATE productos SET activo = ? WHERE id = ?')->execute([$active ? 'TRUE' : 'FALSE', $pid]);
        $msg = $active ? 'Producto activado.' : 'Producto desactivado.';
    }
}

$perPage = 15;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$total   = (int) $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();
$pages   = max(1, (int) ceil($total / $perPage));
$page    = min($page, $pages);
$offset  = ($page - 1) * $perPage;

$products = $pdo->query(
    "SELECT p.id, p.continente AS brand, p.seleccion AS model, p.precio AS price, p.activo AS active,
            COALESCE(SUM(s.cantidad),0) AS total_stock
     FROM productos p
     LEFT JOIN stock s ON s.producto_id = p.id
     GROUP BY p.id
     ORDER BY p.continente, p.seleccion
     LIMIT $perPage OFFSET $offset"
)->fetchAll();
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
    <h1 class="adm-page-title" style="margin:0">Productos</h1>
    <a href="/admin/product_edit.php" class="adm-btn adm-btn-green">+ Nuevo producto</a>
</div>

<?php if ($msg !== ''): ?><p class="adm-msg adm-msg-ok"><?= h($msg) ?></p><?php endif; ?>
<?php if ($err !== ''): ?><p class="adm-msg adm-msg-err"><?= h($err) ?></p><?php endif; ?>

<div class="adm-table-wrap">
    <table class="adm-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Continente</th>
                <th>Selección</th>
                <th>Precio</th>
                <th>Stock total</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= (int)$p['id'] ?></td>
                <td><?= h($p['brand']) ?></td>
                <td><?= h($p['model']) ?></td>
                <td><?= number_format((float)$p['price'], 2, ',', ' ') ?> €</td>
                <td><?= (int)$p['total_stock'] ?> uds.</td>
                <td>
                    <?php if ($p['active']): ?>
                        <span class="adm-badge adm-badge-green">Activo</span>
                    <?php else: ?>
                        <span class="adm-badge adm-badge-gray">Inactivo</span>
                    <?php endif; ?>
                </td>
                <td style="display:flex;gap:.4rem;flex-wrap:wrap">
                    <a href="/admin/product_edit.php?id=<?= (int)$p['id'] ?>" class="adm-btn adm-btn-outline adm-btn-sm">Editar</a>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                        <input type="hidden" name="new_active" value="<?= $p['active'] ? '0' : '1' ?>">
                        <button type="submit" name="toggle_active" value="1"
                                class="adm-btn adm-btn-sm <?= $p['active'] ? 'adm-btn-danger' : 'adm-btn-outline' ?>">
                            <?= $p['active'] ? 'Desactivar' : 'Activar' ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($pages > 1): ?>
<div class="adm-pagination">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?page=<?= $i ?>" class="adm-btn <?= $i === $page ? 'adm-btn-green' : 'adm-btn-outline' ?> adm-btn-sm"><?= $i ?></a>
    <?php endfor; ?>
    <span class="adm-pag-info">Página <?= $page ?> de <?= $pages ?></span>
</div>
<?php endif; ?>

<?php require __DIR__ . '/admin_layout_end.php'; ?>
