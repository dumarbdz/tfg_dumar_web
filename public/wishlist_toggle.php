<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_login();
$user = current_user();
assert($user !== null);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['_csrf'] ?? null)) {
    header('Location: /index.php');
    exit;
}

$productId = (int) ($_POST['product_id'] ?? 0);
$next = safe_next_url($_POST['next'] ?? null, '/wishlist.php');

if ($productId > 0) {
    $pdo = get_pdo();
    $st = $pdo->prepare('SELECT 1 FROM favoritos WHERE usuario_id = ? AND producto_id = ?');
    $st->execute([$user['id'], $productId]);
    if ($st->fetchColumn()) {
        $pdo->prepare('DELETE FROM favoritos WHERE usuario_id = ? AND producto_id = ?')
            ->execute([$user['id'], $productId]);
        flash_set('Eliminado de favoritos.', 'info');
    } else {
        $pdo->prepare('INSERT INTO favoritos (usuario_id, producto_id) VALUES (?, ?) ON CONFLICT DO NOTHING')
            ->execute([$user['id'], $productId]);
        flash_set('Guardado en favoritos. ❤');
    }
}

header('Location: ' . $next);
exit;
