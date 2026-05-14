<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

// Redirigir búsquedas al catálogo
$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$brand = isset($_GET['brand']) ? trim((string) $_GET['brand']) : '';
if ($q !== '' || $brand !== '') {
    $redir = array_filter(['q' => $q, 'brand' => $brand]);
    header('Location: /catalog.php?' . http_build_query($redir));
    exit;
}

$pdo = get_pdo();

// Destacados: equipos icónicos
$stFeat = $pdo->query(
    "SELECT id, continente AS brand, seleccion AS model, slug,
            descripcion AS description, precio AS price, imagen AS image_path
     FROM productos
     WHERE activo = 1
     ORDER BY FIELD(seleccion,'España','Brasil','Argentina','Francia','Alemania','Japón','Marruecos','Nigeria','Colombia','Italia','Portugal','Rusia')
     LIMIT 8"
);
$featured = $stFeat ? $stFeat->fetchAll() : [];

$pageTitle = 'Inicio';
$metaDescription = 'Tienda oficial de camisetas del Mundial 2026. Encuentra la camiseta de tu selección favorita.';
require dirname(__DIR__) . '/includes/header.php';
?>

<!-- HERO COMPACTO -->
<section class="home-hero" aria-labelledby="home-title">
    <div class="home-hero-inner">
        <div class="home-hero-text">
            <p class="home-hero-eyebrow">FIFA World Cup 2026™</p>
            <h1 id="home-title">Camisetas del Mundial 2026</h1>
            <p class="lead home-lead">La camiseta oficial de tu selección favorita, con todas las tallas disponibles.</p>
            <div class="home-hero-ctas">
                <a href="/catalog.php" class="btn btn-hero-primary">Ver catálogo</a>
                <a href="/catalog.php?brand=Europa" class="btn btn-hero-outline">Europa</a>
                <a href="/catalog.php?brand=Sudam%C3%A9rica" class="btn btn-hero-outline">Sudamérica</a>
            </div>
        </div>
        <div class="home-hero-image" aria-hidden="true">
            <img src="/images/trofeo-mundial.svg" alt="" width="220" height="330">
        </div>
    </div>
</section>

<!-- PRODUCTOS DESTACADOS -->
<section class="home-featured" aria-labelledby="featured-heading">
    <div class="home-featured-header">
        <h2 id="featured-heading">Selecciones destacadas</h2>
        <a href="/catalog.php" class="home-featured-link">Ver todas →</a>
    </div>

    <?php if ($featured !== []): ?>
    <ul class="featured-grid">
        <?php foreach ($featured as $i => $p): ?>
        <li class="featured-card <?= $i < 2 ? 'featured-card--large' : '' ?>">
            <a href="/product.php?id=<?= (int) $p['id'] ?>">
                <div class="featured-card-img">
                    <img src="<?= h((string) $p['image_path']) ?>"
                         alt="Camiseta <?= h($p['model']) ?>"
                         width="400" height="300" loading="<?= $i < 4 ? 'eager' : 'lazy' ?>">
                </div>
                <div class="featured-card-body">
                    <span class="featured-card-continent"><?= h($p['brand']) ?></span>
                    <strong class="featured-card-name"><?= h($p['model']) ?></strong>
                    <span class="featured-card-price"><?= number_format((float) $p['price'], 2, ',', ' ') ?> €</span>
                </div>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</section>

<!-- EXPLORAR POR CONTINENTE -->
<section class="continents" aria-labelledby="continents-heading">
    <h2 id="continents-heading" class="h3">Explorar por continente</h2>
    <nav class="continent-grid" aria-label="Filtrar por continente">
        <a href="/catalog.php?brand=Europa" class="continent-card continent-europa">
            <strong class="continent-name">Europa</strong>
            <span class="continent-sub">España · Francia · Alemania · Italia</span>
        </a>
        <a href="/catalog.php?brand=Sudam%C3%A9rica" class="continent-card continent-sudamerica">
            <strong class="continent-name">Sudamérica</strong>
            <span class="continent-sub">Brasil · Argentina · Colombia · Uruguay</span>
        </a>
        <a href="/catalog.php?brand=%C3%81frica" class="continent-card continent-africa">
            <strong class="continent-name">África</strong>
            <span class="continent-sub">Marruecos · Nigeria · Senegal · Camerún</span>
        </a>
        <a href="/catalog.php?brand=Asia" class="continent-card continent-asia">
            <strong class="continent-name">Asia</strong>
            <span class="continent-sub">Japón · Corea del Sur · Australia · Qatar</span>
        </a>
    </nav>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
