<?php
declare(strict_types=1);

function handle_products(?int $id): never {
    $pdo = get_pdo();

    if ($id !== null) {
        $st = $pdo->prepare(
            'SELECT p.id, p.continente AS brand, p.seleccion AS model, p.slug,
                    p.descripcion AS description, p.precio AS price, p.imagen AS image_path, p.activo AS active
             FROM productos p WHERE p.id = ? AND p.activo = 1'
        );
        $st->execute([$id]);
        $product = $st->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            json_out(['error' => 'Product not found'], 404);
        }
        $product['price']  = (float) $product['price'];
        $product['active'] = (bool)  $product['active'];
        $product['stock']  = fetch_stock($pdo, $id);
        json_out(['data' => $product]);
    }

    $st = $pdo->query(
        'SELECT p.id, p.continente AS brand, p.seleccion AS model, p.slug,
                p.descripcion AS description, p.precio AS price, p.imagen AS image_path, p.activo AS active
         FROM productos p WHERE p.activo = 1 ORDER BY p.continente, p.seleccion'
    );
    $products = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as &$p) {
        $p['price']  = (float) $p['price'];
        $p['active'] = (bool)  $p['active'];
        $p['stock']  = fetch_stock($pdo, (int)$p['id']);
    }
    unset($p);
    json_out(['data' => $products, 'total' => count($products)]);
}

function fetch_stock(PDO $pdo, int $productId): array {
    $st = $pdo->prepare("SELECT talla AS size, cantidad AS quantity FROM stock WHERE producto_id = ? ORDER BY FIELD(talla,'XS','S','M','L','XL','XXL')");
    $st->execute([$productId]);
    $result = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $result[$r['size']] = (int) $r['quantity'];
    }
    return $result;
}
