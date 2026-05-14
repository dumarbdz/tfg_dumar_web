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

            <!-- Catálogo con dropdown -->
            <div class="nav-item has-dropdown">
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

            <!-- Carrito -->
            <a href="/cart.php" class="nav-link nav-cart">
                🛒<?php if ($cartCount > 0): ?> <span class="nav-cart-badge"><?= (int)$cartCount ?></span><?php endif; ?>
            </a>

            <!-- Mi cuenta con dropdown -->
            <div class="nav-item has-dropdown">
                <button class="nav-link nav-account-btn" type="button" aria-haspopup="true">
                    <?php if ($user): ?>
                        <span class="nav-account-avatar"><?= mb_strtoupper(mb_substr($user['name'], 0, 1)) ?></span>
                        <span class="nav-account-name"><?= h($user['name']) ?></span>
                    <?php else: ?>
                        👤 Mi cuenta
                    <?php endif; ?>
                    <span class="nav-arrow">▾</span>
                </button>
                <div class="nav-dropdown nav-dropdown-right">
                    <?php if ($user): ?>
                        <div class="nav-dropdown-header"><?= h($user['email']) ?></div>
                        <a href="/mi-cuenta.php">Mis datos</a>
                        <a href="/orders.php">Mis pedidos</a>
                        <a href="/wishlist.php">❤ Favoritos</a>
                        <?php if ($user['is_admin']): ?>
                            <div class="nav-dropdown-divider"></div>
                            <a href="/admin/" class="nav-dropdown-admin">⚙ Admin</a>
                        <?php endif; ?>
                        <div class="nav-dropdown-divider"></div>
                        <a href="/logout.php" class="nav-dropdown-danger">Cerrar sesión</a>
                    <?php else: ?>
                        <a href="/login.php">Iniciar sesión</a>
                        <a href="/register.php">Crear cuenta</a>
                    <?php endif; ?>
                </div>
            </div>

        </nav>
    </div>
</header>
<div id="toast-container"></div>
<main class="container main-content">
