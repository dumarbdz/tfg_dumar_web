<?php
declare(strict_types=1);

function handle_orders(?int $id): never {
    $pdo = get_pdo();

    if ($id !== null) {
        $st = $pdo->prepare(
            'SELECT o.id, o.total, o.estado AS status, o.metodo_pago AS payment_method, o.creado_en AS created_at,
                    o.envio_nombre AS shipping_name, o.envio_linea1 AS shipping_line1,
                    o.envio_postal AS shipping_postal, o.envio_ciudad AS shipping_city, o.envio_pais AS shipping_country,
                    u.nombre AS user_name, u.email AS user_email
             FROM pedidos o JOIN usuarios u ON u.id = o.usuario_id
             WHERE o.id = ?'
        );
        $st->execute([$id]);
        $order = $st->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            json_out(['error' => 'Order not found'], 404);
        }
        $order['total'] = (float) $order['total'];
        $order['items'] = fetch_order_items($pdo, $id);
        json_out(['data' => $order]);
    }

    $st = $pdo->query(
        'SELECT o.id, o.total, o.estado AS status, o.metodo_pago AS payment_method, o.creado_en AS created_at,
                u.nombre AS user_name, u.email AS user_email
         FROM pedidos o JOIN usuarios u ON u.id = o.usuario_id
         ORDER BY o.creado_en DESC'
    );
    $orders = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($orders as &$o) {
        $o['total'] = (float) $o['total'];
    }
    unset($o);
    json_out(['data' => $orders, 'total' => count($orders)]);
}

function fetch_order_items(PDO $pdo, int $orderId): array {
    $st = $pdo->prepare(
        'SELECT l.producto_id, p.continente AS brand, p.seleccion AS model,
                l.talla AS size, l.cantidad AS quantity, l.precio_unitario AS unit_price
         FROM lineas_pedido l LEFT JOIN productos p ON p.id = l.producto_id
         WHERE l.pedido_id = ?'
    );
    $st->execute([$orderId]);
    $items = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as &$item) {
        $item['quantity']   = (int)   $item['quantity'];
        $item['unit_price'] = (float) $item['unit_price'];
    }
    return $items;
}
