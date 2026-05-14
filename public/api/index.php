<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$allowed_origin = getenv('APP_URL') ?: 'https://mundial-store.vercel.app';
header('Access-Control-Allow-Origin: ' . $allowed_origin);
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: X-Api-Key, Content-Type');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

function json_out(mixed $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_key_valid(): bool {
    $expected = load_app_config()['api_key'] ?? '';
    $sent = $_SERVER['HTTP_X_API_KEY'] ?? '';
    return $expected !== '' && hash_equals($expected, $sent);
}

function require_api_key(): void {
    if (!api_key_valid()) {
        json_out(['error' => 'Unauthorized. Send a valid X-Api-Key header.'], 401);
    }
}

// Parse path: strip /api prefix
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^/api#', '', $uri);
$uri = rtrim($uri, '/');

// Route: /products or /products/{id}
if (preg_match('#^/products(?:/(\d+))?$#', $uri, $m)) {
    if (api_rate_limit('products', 120)) {
        json_out(['error' => 'Too many requests. Limit: 120 req/min.'], 429);
    }
    require __DIR__ . '/products.php';
    $productId = isset($m[1]) ? (int)$m[1] : null;
    handle_products($productId);
}

// Route: /orders or /orders/{id}
if (preg_match('#^/orders(?:/(\d+))?$#', $uri, $m)) {
    require_api_key();
    if (api_rate_limit('orders', 30)) {
        json_out(['error' => 'Too many requests. Limit: 30 req/min.'], 429);
    }
    require __DIR__ . '/orders.php';
    $orderId = isset($m[1]) ? (int)$m[1] : null;
    handle_orders($orderId);
}

json_out(['error' => 'Not found'], 404);
