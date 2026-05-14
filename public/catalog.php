<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$q          = isset($_GET['q'])         ? trim((string) $_GET['q'])     : '';
$brand      = isset($_GET['brand'])     ? trim((string) $_GET['brand']) : '';
$model      = isset($_GET['model'])     ? trim((string) $_GET['model']) : '';
$size       = isset($_GET['size'])      ? trim((string) $_GET['size'])  : '';
$price_min  = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? (float) $_GET['price_min'] : 0.0;
$price_max  = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (float) $_GET['price_max'] : 200.0;
$page       = max(1, (int) ($_GET['page'] ?? 1));

$allowedBrands = ['', 'Europa', 'Sudamérica', 'África', 'Asia'];
if (!in_array($brand, $allowedBrands, true)) $brand = '';

$pdo = get_pdo();

// ── Construir condiciones WHERE ──
$conditions = ['p.activo = TRUE'];
$params     = [];

if ($q !== '') {
    $conditions[] = '(p.seleccion LIKE ? OR p.slug LIKE ? OR p.continente LIKE ? OR p.descripcion LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($brand !== '') {
    $conditions[] = 'p.continente = ?';
    $params[]      = $brand;
}
if ($model !== '') {
    $conditions[] = '(p.seleccion LIKE ? OR p.slug LIKE ?)';
    $like = '%' . $model . '%';
    array_push($params, $like, $like);
}
if ($size !== '') {
    $conditions[] = 'EXISTS (SELECT 1 FROM stock s WHERE s.producto_id = p.id AND s.talla = ? AND s.cantidad > 0)';
    $params[]      = $size;
}
if ($price_min > 0) {
    $conditions[] = 'p.precio >= ?';
    $params[]      = $price_min;
}
if ($price_max < 200) {
    $conditions[] = 'p.precio <= ?';
    $params[]      = $price_max;
}

$where = 'WHERE ' . implode(' AND ', $conditions);

$stCount = $pdo->prepare("SELECT COUNT(DISTINCT p.id) FROM productos p $where");
$stCount->execute($params);
$totalProducts = (int) $stCount->fetchColumn();

$perPage    = 12;
$totalPages = max(1, (int) ceil($totalProducts / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$st = $pdo->prepare(
    "SELECT DISTINCT p.id, p.continente AS brand, p.seleccion AS model, p.slug,
            p.descripcion AS description, p.precio AS price, p.imagen AS image_path,
            (SELECT SUM(cantidad) FROM stock WHERE producto_id = p.id) AS total_stock
     FROM productos p $where ORDER BY p.continente, p.seleccion LIMIT $perPage OFFSET $offset"
);
$st->execute($params);
$products = $st->fetchAll();

$urlParams = [];
if ($q !== '')         $urlParams['q']         = $q;
if ($brand !== '')     $urlParams['brand']     = $brand;
if ($model !== '')     $urlParams['model']     = $model;
if ($size !== '')      $urlParams['size']      = $size;
if ($price_min > 0)    $urlParams['price_min'] = $price_min;
if ($price_max < 200)  $urlParams['price_max'] = $price_max;

$user = current_user();
$wishlistIds = [];
if ($user !== null && $products !== []) {
    $ids = array_column($products, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    try {
        $stW = $pdo->prepare("SELECT producto_id FROM favoritos WHERE usuario_id = ? AND producto_id IN ($ph)");
        $stW->execute(array_merge([$user['id']], $ids));
        foreach ($stW->fetchAll() as $w) {
            $wishlistIds[(int) $w['producto_id']] = true;
        }
    } catch (\PDOException) {}
}

$pageTitle = $q !== '' ? 'Resultados de búsqueda' : 'Catálogo';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="catalog-page" aria-labelledby="catalog-title">
    <nav aria-label="Ruta de navegación">
        <ol class="breadcrumb">
            <li><a href="/index.php">Inicio</a></li>
            <li aria-current="page"><?= $q !== '' ? 'Resultados de búsqueda' : 'Catálogo' ?></li>
        </ol>
    </nav>

    <h1 id="catalog-title"><?= $q !== '' ? 'Resultados de búsqueda' : 'Catálogo de camisetas' ?></h1>
    <?php if ($q !== ''): ?>
        <p class="muted">Búsqueda: «<?= h($q) ?>» — <a href="/catalog.php">Ver todo el catálogo</a></p>
    <?php else: ?>
        <p class="lead catalog-lead">Filtra por continente, selección, talla, precio o color.</p>
    <?php endif; ?>

    <section id="resultados" class="catalog-listing" aria-labelledby="listing-heading">
        <div class="catalog-listing-header">
            <h2 id="listing-heading" class="h3">Camisetas</h2>
            <p class="catalog-count muted small">
                <?php if ($totalProducts === 0): ?>
                    Sin resultados
                <?php elseif ($totalPages > 1): ?>
                    <?= $totalProducts ?> camisetas · página <?= $page ?> de <?= $totalPages ?>
                <?php else: ?>
                    <?= $totalProducts ?> camiseta<?= $totalProducts !== 1 ? 's' : '' ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="catalog-toolbar">

        <!-- Buscador -->
        <form class="catalog-search-form" method="get" action="/catalog.php" role="search">
            <label class="visually-hidden" for="catalog-q">Buscar en el catálogo</label>
            <input id="catalog-q" type="search" name="q" placeholder="Buscar selección, continente…"
                   value="<?= h($q) ?>" autocomplete="off">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>

        <!-- Filtros -->
        <form class="filters" method="get" action="/catalog.php">

            <label for="filter-brand">
                Continente
                <select id="filter-brand" name="brand">
                    <option value="">Todos</option>
                    <option value="Europa"     <?= $brand === 'Europa'     ? 'selected' : '' ?>>Europa</option>
                    <option value="Sudamérica" <?= $brand === 'Sudamérica' ? 'selected' : '' ?>>Sudamérica</option>
                    <option value="África"     <?= $brand === 'África'     ? 'selected' : '' ?>>África</option>
                    <option value="Asia"       <?= $brand === 'Asia'       ? 'selected' : '' ?>>Asia</option>
                </select>
            </label>

            <label for="filter-model">
                Selección
                <input id="filter-model" type="search" name="model" placeholder="Ej. Brasil, Japón…" value="<?= h($model) ?>">
            </label>

            <label for="filter-size">
                Talla
                <select id="filter-size" name="size">
                    <option value="">Todas</option>
                    <?php foreach (['XS','S','M','L','XL','XXL'] as $sz): ?>
                        <option value="<?= $sz ?>" <?= $size === $sz ? 'selected' : '' ?>><?= $sz ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="filter-price-row">
                <label for="filter-price-min">
                    Precio mín.
                    <input id="filter-price-min" type="number" name="price_min" min="0" step="1"
                           value="<?= (int)$price_min ?>">
                </label>
                <label for="filter-price-max">
                    Precio máx.
                    <input id="filter-price-max" type="number" name="price_max" min="0" step="1"
                           value="<?= (int)$price_max ?>">
                </label>
            </div>

<button type="submit" class="btn btn-primary">Aplicar</button>
        </form>

        </div><!-- /.catalog-toolbar -->

        <?php if ($products === []): ?>
            <p class="msg msg-info">No hay resultados con esos criterios.
                <a href="/catalog.php">Ver catálogo completo</a>
            </p>
        <?php else: ?>
            <ul class="product-grid">
                <?php foreach ($products as $p): ?>
                    <?php $stockTotal = (int) ($p['total_stock'] ?? 0); ?>
                    <li class="product-card">
                        <a href="/product.php?id=<?= (int) $p['id'] ?>">
                            <div class="product-card-img-wrap">
                                <img src="<?= h((string) $p['image_path']) ?>"
                                     alt="Camiseta <?= h($p['brand'] . ' · ' . $p['model']) ?>"
                                     width="400" height="300" loading="lazy">
                                <?php if ($stockTotal > 0 && $stockTotal <= 5): ?>
                                    <span class="badge-stock-low">¡Últimas unidades!</span>
                                <?php endif; ?>
                            </div>
                            <h3><?= h($p['brand'] . ' · ' . $p['model']) ?></h3>
                            <p class="price"><?= number_format((float) $p['price'], 2, ',', ' ') ?> €</p>
                        </a>
                        <?php if ($user !== null): ?>
                        <form method="post" action="/wishlist_toggle.php" style="margin-top:.35rem">
                            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                            <input type="hidden" name="next" value="<?= h($_SERVER['REQUEST_URI']) ?>">
                            <button type="submit" class="btn btn-small btn-outline" style="width:100%">
                                <?= isset($wishlistIds[(int) $p['id']]) ? 'En favoritos' : 'Guardar' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Páginas del catálogo">
                    <?php if ($page > 1): ?>
                        <a href="/catalog.php?<?= h(http_build_query(array_merge($urlParams, ['page' => $page - 1]))) ?>"
                           class="btn btn-outline btn-small">Anterior</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="/catalog.php?<?= h(http_build_query(array_merge($urlParams, ['page' => $i]))) ?>"
                           class="btn btn-small <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>"
                           <?= $i === $page ? 'aria-current="page"' : '' ?>>
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="/catalog.php?<?= h(http_build_query(array_merge($urlParams, ['page' => $page + 1]))) ?>"
                           class="btn btn-outline btn-small">Siguiente</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
