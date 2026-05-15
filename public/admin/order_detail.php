<?php
declare(strict_types=1);
$pageTitle    = 'Detalle de pedido';
$adminSection = 'orders';
require __DIR__ . '/admin_layout.php';

$pdo = get_pdo();
$id  = (int)($_GET['id'] ?? 0);
$msg = '';
$err = '';

$stOrder = $pdo->prepare(
    'SELECT o.*, u.nombre AS user_name, u.email AS user_email
     FROM pedidos o JOIN usuarios u ON u.id = o.usuario_id
     WHERE o.id = ?'
);
$stOrder->execute([$id]);
$order = $stOrder->fetch();

if (!$order) {
    echo '<p class="adm-msg adm-msg-err">Pedido no encontrado. <a href="/admin/orders.php">Volver</a></p>';
    require __DIR__ . '/admin_layout_end.php';
    exit;
}

$items = $pdo->prepare(
    'SELECT l.*, p.continente AS brand, p.seleccion AS model FROM lineas_pedido l
     LEFT JOIN productos p ON p.id = l.producto_id
     WHERE l.pedido_id = ?'
);
$items->execute([$id]);
$items = $items->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $err = 'Sesión inválida.';
    } else {
        $newStatus = trim((string)($_POST['status'] ?? ''));
        $validStatuses = ['pendiente', 'completado', 'enviado', 'cancelado'];
        if (!in_array($newStatus, $validStatuses, true)) {
            $err = 'Estado no válido.';
        } else {
            $pdo->prepare('UPDATE pedidos SET estado = ? WHERE id = ?')->execute([$newStatus, $id]);
            $order['estado'] = $newStatus;
            $msg = 'Estado actualizado a "' . $newStatus . '".';
        }
    }
}

$statusLabels = [
    'pendiente'  => 'Pendiente',
    'completado' => 'Completado',
    'enviado'    => 'Enviado',
    'cancelado'  => 'Cancelado',
];
$currentStatus = $order['estado'];
$badgeClass = match($currentStatus) {
    'completado' => 'adm-badge-green',
    'enviado'    => 'adm-badge-blue',
    'pendiente'  => 'adm-badge-yellow',
    'cancelado'  => 'adm-badge-red',
    default     => 'adm-badge-gray',
};
?>

<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
    <a href="/admin/orders.php" style="color:#7a9282;font-size:.85rem">← Volver a pedidos</a>
    <h1 class="adm-page-title" style="margin:0">Pedido #<?= $id ?></h1>
    <span class="adm-badge <?= $badgeClass ?>"><?= $statusLabels[$currentStatus] ?? h($currentStatus) ?></span>
</div>

<?php if ($msg !== ''): ?><p class="adm-msg adm-msg-ok"><?= h($msg) ?></p><?php endif; ?>
<?php if ($err !== ''): ?><p class="adm-msg adm-msg-err"><?= h($err) ?></p><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem">
    <div style="background:#fff;border:1px solid #c3e0cf;border-radius:8px;padding:1.25rem">
        <p style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#7a9282;margin:0 0 .75rem">Cliente</p>
        <p style="margin:.25rem 0"><strong><?= h($order['user_name']) ?></strong></p>
        <p style="margin:.25rem 0;color:#7a9282"><?= h($order['user_email']) ?></p>
    </div>
    <div style="background:#fff;border:1px solid #c3e0cf;border-radius:8px;padding:1.25rem">
        <p style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#7a9282;margin:0 0 .75rem">Envío</p>
        <p style="margin:.25rem 0"><?= h($order['envio_nombre']) ?></p>
        <p style="margin:.25rem 0;color:#7a9282"><?= h($order['envio_linea1']) ?>, <?= h($order['envio_postal']) ?> <?= h($order['envio_ciudad']) ?> (<?= h($order['envio_pais']) ?>)</p>
        <p style="margin:.25rem 0;color:#7a9282">Pago: <?= h($order['metodo_pago']) ?> · <?= date('d/m/Y H:i', strtotime($order['creado_en'])) ?></p>
    </div>
</div>

<h2 style="font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#3a4f3e;margin:0 0 .75rem">Artículos</h2>
<div class="adm-table-wrap" style="margin-bottom:1.5rem">
    <table class="adm-table">
        <thead>
            <tr><th>Producto</th><th>Talla</th><th>Cantidad</th><th>Precio u.</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= h($item['brand'] . ' ' . $item['model']) ?></td>
                <td><?= h($item['talla']) ?></td>
                <td><?= (int)$item['cantidad'] ?></td>
                <td><?= number_format((float)$item['precio_unitario'], 2, ',', ' ') ?> €</td>
                <td><strong><?= number_format((float)$item['precio_unitario'] * (int)$item['cantidad'], 2, ',', ' ') ?> €</strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p style="font-size:1.2rem;font-weight:800;margin-bottom:1.5rem">
    Total: <?= number_format((float)$order['total'], 2, ',', ' ') ?> €
</p>

<div style="background:#fff;border:1px solid #c3e0cf;border-top:3px solid #138a4a;border-radius:8px;padding:1.25rem;max-width:400px">
    <p style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#7a9282;margin:0 0 .75rem">Cambiar estado del pedido</p>
    <form method="post" style="display:flex;gap:.75rem;align-items:flex-end">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <label style="flex:1;display:flex;flex-direction:column;gap:.4rem;font-size:.8rem;font-weight:700;color:#3a4f3e;text-transform:uppercase;letter-spacing:.04em">
            Estado
            <select name="status">
                <?php foreach ($statusLabels as $val => $lbl): ?>
                    <option value="<?= h($val) ?>" <?= $currentStatus === $val ? 'selected' : '' ?>><?= h($lbl) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" name="update_status" value="1" class="adm-btn adm-btn-green">Guardar</button>
    </form>
</div>

<?php require __DIR__ . '/admin_layout_end.php'; ?>
