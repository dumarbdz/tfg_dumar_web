<?php
// Incluido al inicio de cada página admin.
// Llama a require_admin() y renderiza el header del panel.
// Uso: require __DIR__ . '/admin_layout.php';
// Antes: definir $pageTitle y $adminSection

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
$adminUser = require_admin();
$adminSection = $adminSection ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' · ' : '' ?>Admin · Mundial Store</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: "Barlow", system-ui, sans-serif; background: #f0f4f2; color: #0d1a0f; }
        a { color: #138a4a; text-decoration: none; }
        a:hover { text-decoration: underline; }

        .adm-header {
            background: #052e1a;
            border-bottom: 3px solid #138a4a;
            padding: 0;
        }
        .adm-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 56px;
            padding-inline: 1.5rem;
            max-width: 1300px;
            margin: auto;
        }
        .adm-logo {
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #fff;
            text-decoration: none;
        }
        .adm-logo span { color: #86efac; }
        .adm-header-links { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; }
        .adm-header-links a { color: rgba(255,255,255,0.65); padding: 0.25rem 0.5rem; border-radius: 4px; }
        .adm-header-links a:hover { color: #fff; background: rgba(255,255,255,0.1); text-decoration: none; }

        .adm-layout { display: flex; min-height: calc(100vh - 56px); }

        .adm-sidebar {
            width: 220px;
            flex-shrink: 0;
            background: #fff;
            border-right: 1px solid #c3e0cf;
            padding: 1.5rem 0;
        }
        .adm-nav-title {
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #7a9282;
            padding: 0 1.25rem 0.5rem;
            margin-top: 0.75rem;
        }
        .adm-nav-title:first-child { margin-top: 0; }
        .adm-nav a {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.55rem 1.25rem;
            color: #2d4a37;
            font-size: 0.9rem;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: background 0.12s, border-color 0.12s;
        }
        .adm-nav a:hover { background: #f0f8f3; text-decoration: none; }
        .adm-nav a.active {
            border-left-color: #138a4a;
            background: #e8f5ee;
            color: #0a5c32;
            font-weight: 700;
        }

        .adm-main {
            flex: 1;
            padding: 2rem 2.5rem;
            max-width: 1080px;
        }

        .adm-page-title {
            font-size: 1.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 0 0 1.5rem;
            color: #0d1a0f;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .adm-page-title::before {
            content: "";
            display: inline-block;
            width: 5px;
            height: 1.1em;
            background: #138a4a;
            border-radius: 3px;
        }

        /* Stats cards */
        .adm-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .adm-stat {
            background: #fff;
            border: 1px solid #c3e0cf;
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            border-top: 3px solid #138a4a;
        }
        .adm-stat-value { font-size: 2rem; font-weight: 800; color: #0a5c32; line-height: 1; }
        .adm-stat-label { font-size: 0.8rem; color: #7a9282; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 0.35rem; }

        /* Tabla */
        .adm-table-wrap { background: #fff; border: 1px solid #c3e0cf; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .adm-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .adm-table th { background: #052e1a; color: rgba(255,255,255,0.75); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; padding: 0.7rem 1rem; text-align: left; font-weight: 700; }
        .adm-table td { padding: 0.7rem 1rem; border-bottom: 1px solid #e4efe9; color: #2d4a37; }
        .adm-table tbody tr:last-child td { border-bottom: none; }
        .adm-table tbody tr:hover { background: #f7fdf9; }

        /* Botones */
        .adm-btn {
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.4rem 0.9rem;
            border-radius: 4px;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border: 2px solid transparent;
            cursor: pointer;
            font-family: inherit;
            text-decoration: none;
            transition: background 0.12s, border-color 0.12s, color 0.12s;
        }
        .adm-btn:hover { text-decoration: none; }
        .adm-btn-green  { background: #138a4a; color: #fff; border-color: #0a5c32; }
        .adm-btn-green:hover  { background: #0a5c32; color: #fff; }
        .adm-btn-outline { background: transparent; color: #138a4a; border-color: #138a4a; }
        .adm-btn-outline:hover { background: #e8f5ee; }
        .adm-btn-danger { background: transparent; color: #b91c1c; border-color: #b91c1c; }
        .adm-btn-danger:hover { background: #fef2f2; }
        .adm-btn-sm { padding: 0.28rem 0.65rem; font-size: 0.75rem; }

        /* Badges */
        .adm-badge { display: inline-block; padding: 0.18rem 0.55rem; border-radius: 4px; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; white-space: nowrap; }
        .adm-badge-green  { background: #edfaf3; color: #0e6138; border: 1px solid #a7f3cc; }
        .adm-badge-yellow { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .adm-badge-blue   { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .adm-badge-gray   { background: #f3f4f6; color: #4b5563; border: 1px solid #d1d5db; }
        .adm-badge-red    { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* Form */
        .adm-form { display: flex; flex-direction: column; gap: 1rem; max-width: 560px; }
        .adm-form label { display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.82rem; font-weight: 700; color: #3a4f3e; text-transform: uppercase; letter-spacing: 0.05em; }
        .adm-form input, .adm-form select, .adm-form textarea {
            padding: 0.6rem 0.85rem;
            border-radius: 4px;
            border: 1.5px solid #c3e0cf;
            background: #fff;
            color: #0d1a0f;
            font: inherit;
            font-size: 0.95rem;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .adm-form input:focus, .adm-form select:focus, .adm-form textarea:focus {
            outline: none; border-color: #138a4a; box-shadow: 0 0 0 3px rgba(19,138,74,0.18);
        }
        .adm-form textarea { resize: vertical; min-height: 100px; line-height: 1.6; }

        /* Msg */
        .adm-msg { padding: 0.8rem 1rem; border-radius: 4px; margin-bottom: 1rem; font-weight: 500; font-size: 0.9rem; border-left: 4px solid transparent; }
        .adm-msg-ok  { background: #edfaf3; border-color: #138a4a; color: #0e6138; }
        .adm-msg-err { background: #fef2f2; border-color: #b91c1c; color: #991b1b; }

        /* Stock inline */
        .adm-stock-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.6rem; }
        .adm-stock-item { display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.8rem; font-weight: 700; color: #3a4f3e; text-transform: uppercase; letter-spacing: 0.04em; }
        .adm-stock-item input { min-width: 0; }

        /* Pagination */
        .adm-pagination { display: flex; align-items: center; gap: 0.35rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #c3e0cf; }
        .adm-pagination .adm-btn { min-width: 2.2rem; justify-content: center; }
        .adm-pag-info { color: #7a9282; font-size: 0.82rem; padding: 0 0.35rem; }

        /* Charts */
        .adm-charts { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 2rem; }
        @media (max-width: 760px) { .adm-charts { grid-template-columns: 1fr; } }
        .adm-chart-card { background: #fff; border: 1px solid #c3e0cf; border-radius: 8px; padding: 1.25rem 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .adm-chart-card h3 { margin: 0 0 1rem; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #7a9282; }
        .adm-chart-wide { grid-column: 1 / -1; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<header class="adm-header">
    <div class="adm-header-inner">
        <a class="adm-logo" href="/admin/">⚽ Mundial <span>Admin</span></a>
        <div class="adm-header-links">
            <a href="/">Ver tienda</a>
            <a href="/logout.php">Cerrar sesión</a>
        </div>
    </div>
</header>
<div class="adm-layout">
    <aside class="adm-sidebar">
        <nav class="adm-nav">
            <p class="adm-nav-title">General</p>
            <a href="/admin/" class="<?= $adminSection === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
            <p class="adm-nav-title">Catálogo</p>
            <a href="/admin/products.php" class="<?= $adminSection === 'products' ? 'active' : '' ?>">👕 Productos</a>
            <p class="adm-nav-title">Ventas</p>
            <a href="/admin/orders.php" class="<?= $adminSection === 'orders' ? 'active' : '' ?>">📦 Pedidos</a>
        </nav>
    </aside>
    <main class="adm-main">
