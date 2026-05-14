<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (current_user() === null) {
    header('Location: /login.php?next=' . rawurlencode('/checkout.php'));
    exit;
}

if (isset($_GET['restart'])) {
    checkout_clear_draft();
    header('Location: /checkout.php');
    exit;
}

$pdo = get_pdo();
$user = current_user();
assert($user !== null);

$error = '';

$items = cart_items();
$lines = [];
$subtotal = 0.0;

foreach ($items as $line) {
    $st = $pdo->prepare('SELECT p.id, p.continente AS brand, p.seleccion AS model, p.precio AS price,
        (SELECT cantidad FROM stock WHERE producto_id = p.id AND talla = ?) AS stock_qty
        FROM productos p WHERE p.id = ? AND p.activo = TRUE');
    $st->execute([$line['size'], $line['product_id']]);
    $p = $st->fetch();
    if (!$p) {
        cart_set_qty($line['product_id'], $line['size'], 0);
        continue;
    }
    $stockQty = (int) $p['stock_qty'];
    $qty = min($line['qty'], max(0, $stockQty));
    if ($qty !== $line['qty']) {
        cart_set_qty($line['product_id'], $line['size'], $qty);
    }
    if ($qty <= 0) {
        continue;
    }
    $price = (float) $p['price'];
    $lineTotal = $price * $qty;
    $subtotal += $lineTotal;
    $lines[] = [
        'product_id' => $line['product_id'],
        'size' => $line['size'],
        'qty' => $qty,
        'unit_price' => $price,
        'line_total' => $lineTotal,
        'label' => $p['brand'] . ' ' . $p['model'],
    ];
}

$draft = checkout_draft_get();

$hasSavedAddress = false;
if (!checkout_shipping_complete($draft)) {
    $stAddr = $pdo->prepare('SELECT dir_nombre AS saved_name, dir_linea1 AS saved_line1, dir_postal AS saved_postal, dir_ciudad AS saved_city, dir_pais AS saved_country FROM usuarios WHERE id = ?');
    $stAddr->execute([$user['id']]);
    $savedAddr = $stAddr->fetch();
    if ($savedAddr && $savedAddr['saved_name'] !== null) {
        $hasSavedAddress = true;
        checkout_draft_merge([
            'shipping_name'    => (string) $savedAddr['saved_name'],
            'shipping_line1'   => (string) $savedAddr['saved_line1'],
            'shipping_postal'  => (string) $savedAddr['saved_postal'],
            'shipping_city'    => (string) $savedAddr['saved_city'],
            'shipping_country' => (string) $savedAddr['saved_country'],
        ]);
        $draft = checkout_draft_get();
    }
}

$step = 1;
if (checkout_shipping_complete($draft)) {
    $step = checkout_payment_complete($draft) ? 3 : 2;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $error = 'Sesión inválida.';
    } elseif (isset($_POST['save_shipping'])) {
        checkout_draft_merge([
            'shipping_name'    => (string) ($_POST['shipping_name'] ?? ''),
            'shipping_line1'   => (string) ($_POST['shipping_line1'] ?? ''),
            'shipping_postal'  => (string) ($_POST['shipping_postal'] ?? ''),
            'shipping_city'    => (string) ($_POST['shipping_city'] ?? ''),
            'shipping_country' => (string) ($_POST['shipping_country'] ?? ''),
            'save_address'     => isset($_POST['save_address']) ? '1' : '0',
        ]);
        $draft = checkout_draft_get();
        if (!checkout_shipping_complete($draft)) {
            $error = 'Completa todos los datos de envío.';
        } else {
            header('Location: /checkout.php');
            exit;
        }
    } elseif (isset($_POST['edit_shipping'])) {
        checkout_draft_merge([
            'shipping_name' => '', 'shipping_line1' => '',
            'shipping_postal' => '', 'shipping_city' => '', 'shipping_country' => '',
        ]);
        header('Location: /checkout.php');
        exit;
    } elseif (isset($_POST['edit_payment'])) {
        checkout_draft_merge(['payment_method' => '']);
        header('Location: /checkout.php');
        exit;
    } elseif (isset($_POST['save_payment'])) {
        $method = trim((string) ($_POST['payment_method'] ?? ''));
        $allowed = ['card', 'bizum', 'transfer'];
        if (!in_array($method, $allowed, true)) {
            $error = 'Elige un método de pago.';
        } else {
            checkout_draft_merge(['payment_method' => $method]);
            header('Location: /checkout.php');
            exit;
        }
    } elseif (isset($_POST['confirm_pay'])) {
        $draft = checkout_draft_get();
        if ($lines === []) {
            $error = 'El carrito está vacío.';
        } elseif (!checkout_shipping_complete($draft) || !checkout_payment_complete($draft)) {
            $error = 'Completa envío y método de pago antes de pagar.';
        } elseif (!empty($_POST['simulate_fail'])) {
            $error = 'El pago fue rechazado (simulación). Revisa los datos o prueba otro método.';
        } else {
            try {
                $pdo->beginTransaction();
                $orderTotal = 0.0;
                $preparedLines = [];
                foreach ($lines as $ln) {
                    $st = $pdo->prepare('SELECT cantidad FROM stock WHERE producto_id = ? AND talla = ? FOR UPDATE');
                    $st->execute([$ln['product_id'], $ln['size']]);
                    $row = $st->fetch();
                    $avail = $row ? (int) $row['cantidad'] : 0;
                    if ($avail < $ln['qty']) {
                        throw new RuntimeException('Stock insuficiente para una línea del pedido.');
                    }
                    $preparedLines[] = $ln;
                    $orderTotal += $ln['unit_price'] * $ln['qty'];
                }

                $pmLabel = $draft['payment_method'];
                $stOrder = $pdo->prepare(
                    'INSERT INTO pedidos (usuario_id, total, estado, envio_nombre, envio_linea1, envio_postal, envio_ciudad, envio_pais, metodo_pago)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                     RETURNING id'
                );
                $stOrder->execute([
                    $user['id'],
                    $orderTotal,
                    'completed',
                    $draft['shipping_name'],
                    $draft['shipping_line1'],
                    $draft['shipping_postal'],
                    $draft['shipping_city'],
                    $draft['shipping_country'],
                    $pmLabel,
                ]);
                $orderId = (int) $stOrder->fetchColumn();

                $stItem = $pdo->prepare('INSERT INTO lineas_pedido (pedido_id, producto_id, talla, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?)');
                $stUpd  = $pdo->prepare('UPDATE stock SET cantidad = cantidad - ? WHERE producto_id = ? AND talla = ?');

                foreach ($preparedLines as $ln) {
                    $stItem->execute([
                        $orderId,
                        $ln['product_id'],
                        $ln['size'],
                        $ln['qty'],
                        $ln['unit_price'],
                    ]);
                    $stUpd->execute([$ln['qty'], $ln['product_id'], $ln['size']]);
                }

                if (($draft['save_address'] ?? '1') === '1') {
                    $stSaveAddr = $pdo->prepare(
                        'UPDATE usuarios SET dir_nombre = ?, dir_linea1 = ?, dir_postal = ?, dir_ciudad = ?, dir_pais = ? WHERE id = ?'
                    );
                    $stSaveAddr->execute([
                        $draft['shipping_name'],
                        $draft['shipping_line1'],
                        $draft['shipping_postal'],
                        $draft['shipping_city'],
                        $draft['shipping_country'],
                        $user['id'],
                    ]);
                }

                $pdo->commit();

                $mailLines = [];
                foreach ($preparedLines as $ln) {
                    $mailLines[] = [
                        'label' => $ln['label'],
                        'size' => $ln['size'],
                        'qty' => $ln['qty'],
                        'line' => $ln['unit_price'] * $ln['qty'],
                    ];
                }
                $mailOk = send_order_confirmation_email($user['email'], $orderId, $orderTotal, $mailLines);

                $_SESSION['cart'] = [];
                checkout_clear_draft();
                $_SESSION['flash_order_mail_sent'] = $mailOk;
                header('Location: /order_confirm.php?id=' . $orderId);
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'No se pudo completar el pedido. Inténtalo de nuevo.';
            }
        }
    }
    $draft = checkout_draft_get();
    $step = 1;
    if (checkout_shipping_complete($draft)) {
        $step = checkout_payment_complete($draft) ? 3 : 2;
    }
}

$pageTitle = 'Tramitar compra';
require dirname(__DIR__) . '/includes/header.php';
?>

<h1>Tramitar compra</h1>

<ol class="checkout-steps" aria-label="Pasos del checkout">
    <li class="<?= $step === 1 ? 'is-active' : '' ?><?= $step > 1 ? ' is-done' : '' ?>">Datos de envío</li>
    <li class="<?= $step === 2 ? 'is-active' : '' ?><?= $step > 2 ? ' is-done' : '' ?>">Método de pago</li>
    <li class="<?= $step === 3 ? 'is-active' : '' ?>">Confirmar pago</li>
</ol>

<?php if ($error !== ''): ?>
    <p class="msg msg-error" role="alert"><?= h($error) ?></p>
<?php endif; ?>

<?php if ($lines === []): ?>
    <p class="msg msg-info">No hay artículos para tramitar. <a href="/index.php">Ir al inicio</a> o <a href="/cart.php">ver carrito</a>.</p>
<?php else: ?>

    <p class="actions-row checkout-actions-top">
        <a href="/cart.php" class="btn btn-outline">Volver al carrito</a>
        <a href="/catalog.php" class="btn btn-outline">Seguir comprando</a>
    </p>

    <?php if ($step === 1): ?>
        <h2 class="h3">Datos de envío</h2>
        <?php if ($hasSavedAddress): ?>
            <p class="msg msg-info">Hemos rellenado tu dirección guardada. Modifícala si es necesario.</p>
        <?php endif; ?>
        <form method="post" class="form-card" action="/checkout.php">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="save_shipping" value="1">
            <label for="shipping_name">Nombre completo</label>
            <input id="shipping_name" type="text" name="shipping_name" required maxlength="200" autocomplete="shipping name" value="<?= h($draft['shipping_name']) ?>">
            <label for="shipping_line1">Dirección (calle y número)</label>
            <input id="shipping_line1" type="text" name="shipping_line1" required maxlength="255" autocomplete="shipping street-address" value="<?= h($draft['shipping_line1']) ?>">
            <label for="shipping_postal">Código postal</label>
            <input id="shipping_postal" type="text" name="shipping_postal" required maxlength="32" autocomplete="shipping postal-code" value="<?= h($draft['shipping_postal']) ?>">
            <label for="shipping_city">Ciudad</label>
            <input id="shipping_city" type="text" name="shipping_city" required maxlength="120" autocomplete="shipping address-level2" value="<?= h($draft['shipping_city']) ?>">
            <label for="shipping_country">País</label>
            <input id="shipping_country" type="text" name="shipping_country" required maxlength="120" autocomplete="shipping country-name" value="<?= h($draft['shipping_country']) ?>" placeholder="Ej. España">
            <label class="checkbox-row">
                <input type="checkbox" name="save_address" value="1" <?= ($draft['save_address'] ?? '1') !== '0' ? 'checked' : '' ?>>
                <?= $hasSavedAddress ? 'Actualizar mi dirección guardada' : 'Guardar esta dirección para futuros pedidos' ?>
            </label>
            <button type="submit" class="btn btn-primary">Continuar</button>
        </form>
    <?php elseif ($step === 2): ?>
        <h2 class="h3">Método de pago</h2>
        <form method="post" class="form-card" action="/checkout.php">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="save_payment" value="1">
            <fieldset>
                <legend class="visually-hidden">Elige método</legend>
                <label class="radio-row">
                    <input type="radio" name="payment_method" value="card" <?= $draft['payment_method'] === 'card' ? 'checked' : '' ?> required>
                    Tarjeta (simulado)
                </label>
                <label class="radio-row">
                    <input type="radio" name="payment_method" value="bizum" <?= $draft['payment_method'] === 'bizum' ? 'checked' : '' ?>>
                    Bizum (simulado)
                </label>
                <label class="radio-row">
                    <input type="radio" name="payment_method" value="transfer" <?= $draft['payment_method'] === 'transfer' ? 'checked' : '' ?>>
                    Transferencia (simulado)
                </label>
            </fieldset>
            <button type="submit" class="btn btn-primary">Continuar</button>
        </form>
        <p><a href="/checkout.php?restart=1">Empezar de nuevo el checkout</a></p>
    <?php else: ?>
        <h2 class="h3">Revisión y pago</h2>

        <div class="checkout-review-block">
            <div class="checkout-review-row">
                <div>
                    <span class="checkout-review-label">Envío</span>
                    <span class="muted"><?= h($draft['shipping_name']) ?>, <?= h($draft['shipping_line1']) ?>, <?= h($draft['shipping_postal']) ?> <?= h($draft['shipping_city']) ?> (<?= h($draft['shipping_country']) ?>)</span>
                </div>
                <form method="post" action="/checkout.php">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <button type="submit" name="edit_shipping" value="1" class="btn btn-small btn-outline">Cambiar</button>
                </form>
            </div>
            <div class="checkout-review-row">
                <div>
                    <span class="checkout-review-label">Pago</span>
                    <span class="muted"><?= h(match($draft['payment_method']) {
                        'card'     => 'Tarjeta',
                        'bizum'    => 'Bizum',
                        'transfer' => 'Transferencia',
                        default    => $draft['payment_method'],
                    }) ?></span>
                </div>
                <form method="post" action="/checkout.php">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <button type="submit" name="edit_payment" value="1" class="btn btn-small btn-outline">Cambiar</button>
                </form>
            </div>
        </div>

        <table class="table-cart">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Talla</th>
                    <th>Cant.</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lines as $ln): ?>
                    <tr>
                        <td><?= h($ln['label']) ?></td>
                        <td><?= h($ln['size']) ?></td>
                        <td><?= (int) $ln['qty'] ?></td>
                        <td><?= number_format($ln['unit_price'], 2, ',', ' ') ?> €</td>
                        <td><?= number_format($ln['line_total'], 2, ',', ' ') ?> €</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="cart-total"><strong>Total:</strong> <?= number_format($subtotal, 2, ',', ' ') ?> €</p>

        <form method="post" action="/checkout.php" class="form-card">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="confirm_pay" value="1">
            <p class="muted small">El cobro es <strong>simulado</strong>. Al confirmar, el sistema guarda el pedido en la base de datos.</p>
            <label class="checkbox-row">
                <input type="checkbox" name="simulate_fail" value="1">
                Simular pago rechazado (prueba el flujo "No")
            </label>
            <button type="submit" class="btn btn-primary">Confirmar y pagar</button>
        </form>
    <?php endif; ?>

<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
