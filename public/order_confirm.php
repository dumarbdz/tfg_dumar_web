<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_login();

$user = current_user();
assert($user !== null);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /index.php');
    exit;
}

$pdo = get_pdo();
$st = $pdo->prepare(
    'SELECT id, total, estado AS status, creado_en AS created_at,
            envio_nombre AS shipping_name, envio_linea1 AS shipping_line1,
            envio_postal AS shipping_postal, envio_ciudad AS shipping_city,
            envio_pais AS shipping_country, metodo_pago AS payment_method
     FROM pedidos WHERE id = ? AND usuario_id = ?'
);
$st->execute([$id, $user['id']]);
$order = $st->fetch();
if (!$order) {
    http_response_code(404);
    $pageTitle = 'Pedido no encontrado';
    require dirname(__DIR__) . '/includes/header.php';
    echo '<h1>Pedido no encontrado</h1><p><a href="/index.php">Inicio</a></p>';
    require dirname(__DIR__) . '/includes/footer.php';
    exit;
}

$st2 = $pdo->prepare(
    'SELECT l.producto_id, l.talla AS size, l.cantidad AS quantity, l.precio_unitario AS unit_price,
            p.continente AS brand, p.seleccion AS model
     FROM lineas_pedido l
     JOIN productos p ON p.id = l.producto_id
     WHERE l.pedido_id = ?'
);
$st2->execute([$id]);
$items = $st2->fetchAll();

// Si el pedido está completado, comprobar qué productos ya tienen valoración
$reviewed = [];
if ($order['status'] === 'completed' && $items !== []) {
    $productIds = array_unique(array_column($items, 'producto_id'));
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stRev = $pdo->prepare(
        "SELECT producto_id FROM valoraciones WHERE usuario_id = ? AND producto_id IN ($placeholders)"
    );
    $stRev->execute(array_merge([$user['id']], $productIds));
    foreach ($stRev->fetchAll() as $r) {
        $reviewed[(int)$r['producto_id']] = true;
    }
}

$mailSent = null;
if (isset($_SESSION['flash_order_mail_sent'])) {
    $mailSent = (bool) $_SESSION['flash_order_mail_sent'];
    unset($_SESSION['flash_order_mail_sent']);
}

$pageTitle = 'Pedido confirmado';
require dirname(__DIR__) . '/includes/header.php';
?>

<h1>Pedido confirmado</h1>
<p class="lead">Tu pedido <strong>#<?= (int) $order['id'] ?></strong> se ha registrado con éxito.</p>

<?php if ($mailSent === true): ?>
    <p class="msg msg-success">Te hemos enviado un <strong>correo electrónico</strong> a <strong><?= h($user['email']) ?></strong> con el resumen del pedido. Revisa también la carpeta de spam si no lo ves.</p>
<?php elseif ($mailSent === false): ?>
    <p class="msg msg-info">El pedido está registrado, pero <strong>no se pudo enviar el correo</strong> desde este servidor (configuración de email). Conserva esta pantalla como comprobante.</p>
<?php endif; ?>

<p>Total: <strong><?= number_format((float) $order['total'], 2, ',', ' ') ?> €</strong> · Fecha: <?= h((string) $order['created_at']) ?></p>
<?php if (!empty($order['payment_method'])): ?>
    <p class="muted">Método de pago: <?= h((string) $order['payment_method']) ?></p>
<?php endif; ?>

<h2 class="h3">Detalle</h2>
<table class="table-cart">
    <thead>
        <tr>
            <th>Producto</th>
            <th>Talla</th>
            <th>Cant.</th>
            <th>Precio</th>
            <?php if ($order['status'] === 'completed'): ?><th></th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><a href="/product.php?id=<?= (int)$it['producto_id'] ?>"><?= h($it['brand'] . ' ' . $it['model']) ?></a></td>
                <td><?= h((string) $it['size']) ?></td>
                <td><?= (int) $it['quantity'] ?></td>
                <td><?= number_format((float) $it['unit_price'], 2, ',', ' ') ?> €</td>
                <?php if ($order['status'] === 'completed'): ?>
                    <td>
                        <?php if (isset($reviewed[(int)$it['producto_id']])): ?>
                            <span class="badge badge-green">✓ Valorado</span>
                        <?php else: ?>
                            <a href="/product.php?id=<?= (int)$it['producto_id'] ?>#valoraciones" class="btn btn-small btn-outline">⭐ Valorar</a>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p class="actions-row">
    <a href="/index.php" class="btn btn-primary">Volver al inicio</a>
</p>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
