<?php
/**
 * Autocomplete para la barra de búsqueda.
 * GET /api/search_suggest.php?q=texto
 * Devuelve JSON: [{"id":1,"seleccion":"España","continente":"Europa","slug":"espana"}, ...]
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$pdo  = get_pdo();
$like = '%' . $q . '%';

$st = $pdo->prepare(
    'SELECT id, seleccion, continente, slug
     FROM productos
     WHERE activo = 1 AND (seleccion LIKE ? OR continente LIKE ?)
     ORDER BY
         CASE WHEN seleccion LIKE ? THEN 0 ELSE 1 END,
         seleccion
     LIMIT 6'
);
$startLike = $q . '%';
$st->execute([$like, $like, $startLike]);

echo json_encode($st->fetchAll(\PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
