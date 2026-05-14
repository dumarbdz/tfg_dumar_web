<?php
/**
 * Corrige la imagen y descripción del producto Rusia.
 * Uso: php tools/fix_rusia.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/bootstrap.php';

$pdo = get_pdo();

$st = $pdo->prepare(
    "UPDATE productos
     SET imagen      = '/images/europa-rusia.svg',
         descripcion = 'La Sbornaya, orgullo del fútbol ruso. Rojo vibrante con los colores tricolor. Tejido técnico transpirable, escudo bordado en el pecho.',
         slug        = 'europa-rusia'
     WHERE seleccion = 'Rusia' AND continente = 'Europa'"
);
$st->execute();

if ($st->rowCount() > 0) {
    echo "Producto Rusia actualizado correctamente.\n";
} else {
    echo "No se encontró el producto Rusia (o ya estaba actualizado).\n";
}

// Mostrar resultado
$row = $pdo->query("SELECT id, seleccion, imagen, descripcion FROM productos WHERE seleccion = 'Rusia'")->fetch();
if ($row) {
    echo "ID: {$row['id']}\n";
    echo "Imagen: {$row['imagen']}\n";
    echo "Descripción: {$row['descripcion']}\n";
}
