<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = get_pdo();
$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $error = 'Sesión inválida.';
    } elseif (isset($_POST['remove_line'])) {
        $key = (string) $_POST['remove_line'];
        if ($key !== '' && isset($_SESSION['cart'][$key])) {
            unset($_SESSION['cart'][$key]);
            flash_set('Línea eliminada.');
        header('Location: /cart.php');
        exit;
        }
    } elseif (isset($_POST['update'])) {
        foreach ($_POST['qty'] ?? [] as $key => $rawQty) {
            if (!is_string($key)) continue;
            $parts = explode('|', $key, 2);
            if (count($parts) !== 2) continue;
            $productId = (int) $parts[0];
            $size = $parts[1];
            $qty = max(0, (int) $rawQty);
            if ($productId <= 0 || $size === '') continue;
            $st = $pdo->prepare('SELECT cantidad FROM stock WHERE producto_id = ? AND talla = ?');
            $st->execute([$productId, $size]);
            $row = $st->fetch();
            $max = $row ? (int) $row['cantidad'] : 0;
            if ($qty > $max) $qty = $max;
            cart_set_qty($productId, $size, $qty);
        }
        flash_set('Carrito actualizado.');
        header('Location: /cart.php');
        exit;
    }
}

$items    = cart_items();
$lines    = [];
$subtotal = 0.0;

if ($items !== []) {
    $ids          = array_values(array_unique(array_map(static fn($i) => $i['product_id'], $items)));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stProds = $pdo->prepare(
        "SELECT id, continente AS brand, seleccion AS model, precio AS price, imagen AS image_path
         FROM productos WHERE id IN ($placeholders) AND activo = 1"
    );
    $stProds->execute($ids);
    $productsById = [];
    foreach ($stProds->fetchAll() as $row) {
        $productsById[(int) $row['id']] = $row;
    }

    $stStock = $pdo->prepare(
        "SELECT producto_id, talla AS size, cantidad AS quantity FROM stock WHERE producto_id IN ($placeholders)"
    );
    $stStock->execute($ids);
    $stockMap = [];
    foreach ($stStock->fetchAll() as $row) {
        $stockMap[(int) $row['producto_id']][$row['size']] = (int) $row['quantity'];
    }

    foreach ($items as $line) {
        $p = $productsById[$line['product_id']] ?? null;
        if (!$p) {
            cart_set_qty($line['product_id'], $line['size'], 0);
            continue;
        }
        $stockQty = $stockMap[$line['product_id']][$line['size']] ?? 0;
        $qty = min($line['qty'], max(0, $stockQty));
        if ($qty !== $line['qty']) {
            cart_set_qty($line['product_id'], $line['size'], $qty);
        }
        if ($qty <= 0) continue;
        $price     = (float) $p['price'];
        $lineTotal = $price * $qty;
        $subtotal += $lineTotal;
        $lines[] = [
            'key'        => cart_key($line['product_id'], $line['size']),
            'product_id' => $line['product_id'],
            'size'       => $line['size'],
            'qty'        => $qty,
            'brand'      => $p['brand'],
            'model'      => $p['model'],
            'image_path' => $p['image_path'],
            'unit_price' => $price,
            'line_total' => $lineTotal,
            'stock_qty'  => $stockQty,
        ];
    }
}

$pageTitle = 'Carrito';
require dirname(__DIR__) . '/includes/header.php';
?>
<h1>Carrito</h1>

<?php if ($error !== ''): ?>
    <script>showToast(<?= json_encode($error) ?>, 'error');</script>
<?php endif; ?>

<?php if ($lines === []): ?>
    <p class="msg msg-info">Tu carrito está vacío. <a href="/catalog.php">Explorar catálogo</a></p>
<?php else: ?>
    <form method="post" action="/cart.php" class="cart-update-form">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="update" value="1">
        <table class="table-cart">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Talla</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lines as $ln): ?>
                    <tr>
                        <td class="cart-product">
                            <img src="<?= h((string) $ln['image_path']) ?>"
                                 alt="Camiseta <?= h($ln['brand'] . ' ' . $ln['model']) ?>"
                                 width="80" height="60">
                            <?= h($ln['brand'] . ' ' . $ln['model']) ?>
                        </td>
                        <td><?= h($ln['size']) ?></td>
                        <td><?= number_format($ln['unit_price'], 2, ',', ' ') ?> €</td>
                        <td>
                            <input type="number" name="qty[<?= h($ln['key']) ?>]" min="0" max="<?= (int) $ln['stock_qty'] ?>" value="<?= (int) $ln['qty'] ?>">
                            <span class="muted small">Máx. <?= (int) $ln['stock_qty'] ?></span>
                        </td>
                        <td><?= number_format($ln['line_total'], 2, ',', ' ') ?> €</td>
                        <td>
                            <button type="submit" name="remove_line" value="<?= h($ln['key']) ?>" class="btn btn-small btn-outline">Quitar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="cart-total"><strong>Total estimado:</strong> <?= number_format($subtotal, 2, ',', ' ') ?> €</p>
        <div class="actions-row">
            <button type="submit" class="btn btn-primary">Actualizar carrito</button>
            <a href="/catalog.php" class="btn btn-outline">Seguir comprando</a>
            <a href="/checkout.php" class="btn btn-secondary">Tramitar compra</a>
        </div>
    </form>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
