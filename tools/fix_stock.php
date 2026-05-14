<?php
/**
 * Asigna stock aleatorio (2-5 uds) a todas las tallas agotadas.
 * Uso: php tools/fix_stock.php
 * O visita /tools/fix_stock.php en el navegador (solo en entorno local).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/bootstrap.php';

$pdo = get_pdo();

// Contar cuántas filas están a 0
$stCount = $pdo->query('SELECT COUNT(*) FROM stock WHERE cantidad = 0');
$total   = $stCount ? (int) $stCount->fetchColumn() : 0;

if ($total === 0) {
    echo "No hay tallas agotadas. Nada que actualizar.\n";
    exit;
}

// Actualizar: número aleatorio entre 2 y 5 para cada fila agotada
$pdo->exec('UPDATE stock SET cantidad = FLOOR(2 + RAND() * 4) WHERE cantidad = 0');

echo "Actualizadas {$total} tallas agotadas con stock aleatorio entre 2 y 5.\n";

// Mostrar resumen
$st = $pdo->query(
    'SELECT p.seleccion AS seleccion, p.continente AS continente, s.talla, s.cantidad
     FROM stock s
     JOIN productos p ON p.id = s.producto_id
     ORDER BY p.continente, p.seleccion, s.talla'
);
if ($st) {
    echo "\nStock actual:\n";
    echo str_pad('Selección', 22) . str_pad('Continente', 14) . str_pad('Talla', 8) . "Stock\n";
    echo str_repeat('-', 54) . "\n";
    foreach ($st->fetchAll() as $row) {
        echo str_pad($row['seleccion'], 22)
           . str_pad($row['continente'], 14)
           . str_pad($row['talla'], 8)
           . $row['cantidad'] . "\n";
    }
}
