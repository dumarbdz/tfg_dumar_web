<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_login();
$user = current_user();
assert($user !== null);
$pdo = get_pdo();

$st = $pdo->prepare(
    'SELECT p.id, p.continente AS brand, p.seleccion AS model, p.precio AS price, p.imagen AS image_path
     FROM favoritos f
     JOIN productos p ON p.id = f.producto_id
     WHERE f.usuario_id = ? AND p.activo = 1
     ORDER BY f.creado_en DESC'
);
$st->execute([$user['id']]);
$items = $st->fetchAll();

$pageTitle = 'Lista de deseos';
require dirname(__DIR__) . '/includes/header.php';
?>

<nav aria-label="Ruta de navegación">
    <ol class="breadcrumb">
        <li><a href="/index.php">Inicio</a></li>
        <li aria-current="page">Lista de deseos</li>
    </ol>
</nav>

<h1>Lista de deseos</h1>

<?php if ($items === []): ?>
    <p class="msg msg-info">Tu lista de deseos está vacía. <a href="/catalog.php">Explorar catálogo</a></p>
<?php else: ?>
    <p class="muted"><?= count($items) ?> producto<?= count($items) !== 1 ? 's' : '' ?> guardado<?= count($items) !== 1 ? 's' : '' ?>.</p>
    <ul class="product-grid">
        <?php foreach ($items as $p): ?>
            <li class="product-card">
                <a href="/product.php?id=<?= (int) $p['id'] ?>">
                    <img src="<?= h((string) $p['image_path']) ?>"
                         alt="Camiseta <?= h($p['brand'] . ' · ' . $p['model']) ?>"
                         width="400" height="300" loading="lazy">
                    <h3><?= h($p['brand'] . ' · ' . $p['model']) ?></h3>
                    <p class="price"><?= number_format((float) $p['price'], 2, ',', ' ') ?> €</p>
                </a>
                <div style="display:flex;gap:.5rem;padding:.5rem 0 0">
                    <a href="/product.php?id=<?= (int) $p['id'] ?>" class="btn btn-small btn-primary" style="flex:1;text-align:center">Ver producto</a>
                    <form method="post" action="/wishlist_toggle.php">
                        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                        <input type="hidden" name="next" value="/wishlist.php">
                        <button type="submit" class="btn btn-small btn-outline" title="Quitar de favoritos">🗑</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<p class="actions-row" style="margin-top:2rem">
    <a href="/catalog.php" class="btn btn-primary">Seguir comprando</a>
</p>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
