<?php
declare(strict_types=1);
$user = current_user();
$cartCount = 0;
foreach (cart_items() as $ci) {
    $cartCount += $ci['qty'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' · ' : '' ?>Mundial Store</title>
    <meta name="description" content="<?= isset($metaDescription) ? h($metaDescription) : 'Tienda oficial de camisetas del Mundial 2026. Encuentra la camiseta de tu selección favorita.' ?>">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">

        <a class="logo" href="/index.php">Mundial</a>

        <form class="header-search" method="get" action="/catalog.php" role="search">
            <label class="visually-hidden" for="header-q">Buscar en la tienda</label>
            <input id="header-q" type="search" name="q" placeholder="Buscar selección, continente…" autocomplete="off">
            <button type="submit">Buscar</button>
        </form>

        <nav class="nav-main" aria-label="Principal">

            <!-- Catálogo con dropdown (escritorio) -->
            <div class="nav-item has-dropdown nav-desktop">
                <a href="/catalog.php" class="nav-link">
                    Catálogo <span class="nav-arrow">▾</span>
                </a>
                <div class="nav-dropdown">
                    <a href="/catalog.php">Ver todo</a>
                    <a href="/catalog.php?brand=Europa">Europa</a>
                    <a href="/catalog.php?brand=Sudam%C3%A9rica">Sudamérica</a>
                    <a href="/catalog.php?brand=%C3%81frica">África</a>
                    <a href="/catalog.php?brand=Asia">Asia</a>
                </div>
            </div>

            <!-- Carrito (siempre visible) -->
            <a href="/cart.php" class="nav-link nav-cart" aria-label="Carrito<?= $cartCount > 0 ? ", {$cartCount} artículos" : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <?php if ($cartCount > 0): ?><span class="nav-cart-badge"><?= (int)$cartCount ?></span><?php endif; ?>
            </a>

            <!-- Mi cuenta con dropdown (escritorio) -->
            <div class="nav-item has-dropdown nav-desktop">
                <button class="nav-link nav-account-btn" type="button" aria-haspopup="true">
                    <?php if ($user): ?>
                        <span class="nav-account-avatar"><?= mb_strtoupper(mb_substr($user['name'], 0, 1)) ?></span>
                        <span class="nav-account-name"><?= h($user['name']) ?></span>
                    <?php else: ?>
                        Mi cuenta
                    <?php endif; ?>
                    <span class="nav-arrow">▾</span>
                </button>
                <div class="nav-dropdown nav-dropdown-right">
                    <?php if ($user): ?>
                        <div class="nav-dropdown-header"><?= h($user['email']) ?></div>
                        <a href="/mi-cuenta.php">Mis datos</a>
                        <a href="/orders.php">Mis pedidos</a>
                        <a href="/wishlist.php">Favoritos</a>
                        <?php if ($user['is_admin']): ?>
                            <div class="nav-dropdown-divider"></div>
                            <a href="/admin/" class="nav-dropdown-admin">Admin</a>
                        <?php endif; ?>
                        <div class="nav-dropdown-divider"></div>
                        <a href="/logout.php" class="nav-dropdown-danger">Cerrar sesión</a>
                    <?php else: ?>
                        <a href="/login.php">Iniciar sesión</a>
                        <a href="/register.php">Crear cuenta</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Botón hamburguesa (solo móvil) -->
            <button class="nav-hamburger" id="nav-hamburger" aria-label="Abrir menú" aria-expanded="false" type="button">
                <span></span><span></span><span></span>
            </button>

        </nav>
    </div>

    <!-- Panel menú móvil (DENTRO del header para no afectar al contenido de la página) -->
    <div class="mobile-menu" id="mobile-menu">
        <form class="mobile-search" method="get" action="/catalog.php" role="search">
            <input type="search" name="q" placeholder="Buscar selección, continente…" autocomplete="off">
            <button type="submit">Buscar</button>
        </form>
        <nav class="mobile-nav">
            <p class="mobile-nav-section">Catálogo</p>
            <a href="/catalog.php">Ver todo el catálogo</a>
            <a href="/catalog.php?brand=Europa">Europa</a>
            <a href="/catalog.php?brand=Sudam%C3%A9rica">Sudamérica</a>
            <a href="/catalog.php?brand=%C3%81frica">África</a>
            <a href="/catalog.php?brand=Asia">Asia</a>
            <div class="mobile-nav-divider"></div>
            <?php if ($user): ?>
                <p class="mobile-nav-section"><?= h($user['name']) ?></p>
                <a href="/mi-cuenta.php">Mis datos</a>
                <a href="/orders.php">Mis pedidos</a>
                <a href="/wishlist.php">Favoritos</a>
                <?php if ($user['is_admin']): ?>
                    <a href="/admin/" class="mobile-nav-admin">Admin</a>
                <?php endif; ?>
                <div class="mobile-nav-divider"></div>
                <a href="/logout.php" class="mobile-nav-danger">Cerrar sesión</a>
            <?php else: ?>
                <p class="mobile-nav-section">Mi cuenta</p>
                <a href="/login.php">Iniciar sesión</a>
                <a href="/register.php">Crear cuenta</a>
            <?php endif; ?>
        </nav>
    </div>

</header>
<div id="toast-container"></div>
<main class="container main-content">
