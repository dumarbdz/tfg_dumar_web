<?php
/**
 * Corrige imagen, descripción y slug de cualquier producto a partir de su selección y continente.
 * Uso: php tools/fix_producto.php <seleccion> <continente> <imagen> <descripcion> <slug>
 *
 * Ejemplo:
 *   php tools/fix_producto.php "Rusia" "Europa" "/images/europa-rusia.svg" "Descripción..." "europa-rusia"
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/bootstrap.php';

$args = array_slice($argv ?? [], 1);
if (count($args) < 5) {
    fwrite(STDERR, "Uso: php tools/fix_producto.php <seleccion> <continente> <imagen> <descripcion> <slug>\n");
    exit(1);
}

[$seleccion, $continente, $imagen, $descripcion, $slug] = $args;

$pdo = get_pdo();

$st = $pdo->prepare(
    'UPDATE productos
     SET imagen = ?, descripcion = ?, slug = ?
     WHERE seleccion = ? AND continente = ?'
);
$st->execute([$imagen, $descripcion, $slug, $seleccion, $continente]);

if ($st->rowCount() > 0) {
    echo "Producto '{$seleccion}' ({$continente}) actualizado correctamente.\n";
} else {
    echo "No se encontró el producto '{$seleccion}' en '{$continente}' (o ya estaba actualizado).\n";
    exit(1);
}

$row = $pdo->prepare('SELECT id, seleccion, imagen, slug, descripcion FROM productos WHERE seleccion = ? AND continente = ?');
$row->execute([$seleccion, $continente]);
$r = $row->fetch();
if ($r) {
    echo "ID:          {$r['id']}\n";
    echo "Slug:        {$r['slug']}\n";
    echo "Imagen:      {$r['imagen']}\n";
    echo "Descripción: {$r['descripcion']}\n";
}
