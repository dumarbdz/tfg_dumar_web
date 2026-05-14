<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
    exit;
}

if (!csrf_verify($_POST['_csrf'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sesión inválida.']);
    exit;
}

$productId = (int) ($_POST['product_id'] ?? 0);
$selSize   = trim((string) ($_POST['size'] ?? ''));
$qty       = max(1, (int) ($_POST['qty'] ?? 1));

if ($productId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Producto no válido.']);
    exit;
}
if ($selSize === '') {
    echo json_encode(['success' => false, 'error' => 'Elige una talla.']);
    exit;
}

$pdo = get_pdo();
$st  = $pdo->prepare('SELECT cantidad FROM stock WHERE producto_id = ? AND talla = ?');
$st->execute([$productId, $selSize]);
$row   = $st->fetch();
$avail = $row ? (int) $row['cantidad'] : 0;

if ($avail <= 0) {
    echo json_encode(['success' => false, 'error' => 'Esa talla no está disponible.']);
    exit;
}
if ($qty > $avail) {
    echo json_encode(['success' => false, 'error' => "Cantidad superior al stock ({$avail})."]);
    exit;
}

$key     = cart_key($productId, $selSize);
$current = 0;
if (!empty($_SESSION['cart'][$key]) && is_array($_SESSION['cart'][$key])) {
    $current = (int) ($_SESSION['cart'][$key]['qty'] ?? 0);
}
cart_set_qty($productId, $selSize, min($avail, $current + $qty));

$cartCount = (int) array_sum(array_column(cart_items(), 'qty'));

echo json_encode(['success' => true, 'message' => 'Añadido al carrito.', 'cart_count' => $cartCount]);
exit;
