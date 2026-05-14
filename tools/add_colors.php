<?php
/**
 * Añade columnas color1/color2 a la tabla productos y las rellena con los
 * colores principales de cada selección nacional.
 * Uso: php tools/add_colors.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/bootstrap.php';

$pdo = get_pdo();

// Añadir columnas si no existen
foreach (['color1', 'color2'] as $col) {
    $check = $pdo->query("SHOW COLUMNS FROM productos LIKE '{$col}'")->fetchColumn();
    if ($check === false) {
        $pdo->exec("ALTER TABLE productos ADD COLUMN {$col} VARCHAR(7) NULL");
        echo "Columna '{$col}' añadida.\n";
    } else {
        echo "Columna '{$col}' ya existe.\n";
    }
}

// Colores exactos extraídos de los SVGs de las camisetas
$colors = [
    // Europa
    'España'          => ['#c60b1e', '#f1bf00'],
    'Francia'         => ['#002395', '#ed2939'],
    'Alemania'        => ['#ffffff', '#000000'],
    'Portugal'        => ['#006600', '#cc0000'],
    'Italia'          => ['#003da5', '#ffffff'],
    'Inglaterra'      => ['#ffffff', '#cf142b'],
    'Rusia'           => ['#d52b1e', '#0039a6'],

    // Sudamérica
    'Brasil'          => ['#009c3b', '#ffdf00'],
    'Argentina'       => ['#74acdf', '#ffffff'],
    'Uruguay'         => ['#5eb6e4', '#ffffff'],
    'Colombia'        => ['#fcd116', '#003087'],
    'Chile'           => ['#d52b1e', '#ffffff'],
    'Ecuador'         => ['#ffd100', '#003da5'],

    // África
    'Marruecos'       => ['#c1272d', '#006233'],
    'Nigeria'         => ['#008751', '#ffffff'],
    'Senegal'         => ['#00853f', '#fdef42'],
    'Costa de Marfil' => ['#f77f00', '#009a44'],
    'Camerún'         => ['#007a5e', '#ce1126'],
    'Ghana'           => ['#ffffff', '#006b3f'],

    // Asia
    'Japón'           => ['#003087', '#bc002d'],
    'Corea del Sur'   => ['#cd2e3a', '#0047a0'],
    'Arabia Saudí'    => ['#006c35', '#ffffff'],
    'Australia'       => ['#ffd700', '#009b3a'],
    'Irán'            => ['#ffffff', '#239f40'],
    'Qatar'           => ['#8d1b3d', '#ffffff'],
];

$st = $pdo->prepare('UPDATE productos SET color1 = ?, color2 = ? WHERE seleccion = ?');
$updated = 0;
$notFound = [];

foreach ($colors as $seleccion => [$c1, $c2]) {
    $st->execute([$c1, $c2, $seleccion]);
    if ($st->rowCount() > 0) {
        $updated++;
    } else {
        // Verificar si existe el producto pero ya tiene ese color (rowCount=0 si no cambia)
        $check = $pdo->prepare('SELECT id FROM productos WHERE seleccion = ?');
        $check->execute([$seleccion]);
        if (!$check->fetchColumn()) {
            $notFound[] = $seleccion;
        } else {
            $updated++;
        }
    }
}

echo "Colores actualizados: {$updated} selecciones.\n";
if ($notFound !== []) {
    echo "No encontradas en BD: " . implode(', ', $notFound) . "\n";
}

// Mostrar resumen
$st = $pdo->query('SELECT seleccion, continente, color1, color2 FROM productos WHERE color1 IS NOT NULL ORDER BY continente, seleccion');
if ($st) {
    echo "\nSelección            Continente     Color1    Color2\n";
    echo str_repeat('-', 62) . "\n";
    foreach ($st->fetchAll() as $row) {
        echo str_pad($row['seleccion'], 22)
           . str_pad($row['continente'], 16)
           . str_pad($row['color1'] ?? '', 10)
           . ($row['color2'] ?? '') . "\n";
    }
}
