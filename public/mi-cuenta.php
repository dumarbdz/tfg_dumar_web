<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$pdo  = get_pdo();
$user = current_user();
assert($user !== null);

$error   = '';
$success = '';

// Cargar datos completos del usuario (dirección guardada)
$stUser = $pdo->prepare('SELECT email, nombre, dir_nombre, dir_linea1, dir_postal, dir_ciudad, dir_pais FROM usuarios WHERE id = ?');
$stUser->execute([$user['id']]);
$userData = $stUser->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $error = 'Sesión inválida.';
    } elseif (isset($_POST['save_address'])) {
        $nombre  = trim((string) ($_POST['dir_nombre']  ?? ''));
        $linea1  = trim((string) ($_POST['dir_linea1']  ?? ''));
        $postal  = trim((string) ($_POST['dir_postal']  ?? ''));
        $ciudad  = trim((string) ($_POST['dir_ciudad']  ?? ''));
        $pais    = trim((string) ($_POST['dir_pais']    ?? ''));

        if ($nombre === '' || $linea1 === '' || $postal === '' || $ciudad === '' || $pais === '') {
            $error = 'Completa todos los campos de la dirección.';
        } else {
            $st = $pdo->prepare('UPDATE usuarios SET dir_nombre=?, dir_linea1=?, dir_postal=?, dir_ciudad=?, dir_pais=? WHERE id=?');
            $st->execute([$nombre, $linea1, $postal, $ciudad, $pais, $user['id']]);
            $success = 'Dirección guardada correctamente.';
            // Recargar datos
            $stUser->execute([$user['id']]);
            $userData = $stUser->fetch();
        }
    }
}

// Últimos 5 pedidos
$stOrders = $pdo->prepare(
    "SELECT o.id, o.total, o.estado AS status, o.creado_en AS created_at,
            COUNT(l.id) AS items_count
     FROM pedidos o
     LEFT JOIN lineas_pedido l ON l.pedido_id = o.id
     WHERE o.usuario_id = ?
     GROUP BY o.id
     ORDER BY o.creado_en DESC
     LIMIT 5"
);
$stOrders->execute([$user['id']]);
$recentOrders = $stOrders->fetchAll();

$pageTitle = 'Mi cuenta';
require dirname(__DIR__) . '/includes/header.php';
?>

<nav aria-label="Ruta de navegación">
    <ol class="breadcrumb">
        <li><a href="/index.php">Inicio</a></li>
        <li aria-current="page">Mi cuenta</li>
    </ol>
</nav>

<h1>Mi cuenta</h1>

<div class="account-grid">

    <!-- Columna izquierda: datos personales + dirección -->
    <div class="account-col">

        <section class="account-card">
            <h2 class="account-card-title">Datos personales</h2>
            <dl class="account-dl">
                <dt>Nombre</dt>
                <dd><?= h($userData['nombre'] ?? $user['name']) ?></dd>
                <dt>Correo electrónico</dt>
                <dd><?= h($userData['email'] ?? $user['email']) ?></dd>
            </dl>
            <p class="muted small">Para cambiar tu contraseña usa la opción de recuperación en <a href="/login.php">inicio de sesión</a>.</p>
        </section>

        <section class="account-card">
            <h2 class="account-card-title">Dirección de envío guardada</h2>

            <?php if ($error !== ''): ?>
                <p class="msg msg-error" role="alert"><?= h($error) ?></p>
            <?php endif; ?>
            <?php if ($success !== ''): ?>
                <p class="msg msg-success" role="alert"><?= h($success) ?></p>
            <?php endif; ?>

            <form method="post" action="/mi-cuenta.php" class="account-form">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="save_address" value="1">

                <label for="dir_nombre">Nombre completo</label>
                <input id="dir_nombre" type="text" name="dir_nombre" required maxlength="200"
                       autocomplete="shipping name"
                       value="<?= h((string)($userData['dir_nombre'] ?? '')) ?>">

                <label for="dir_linea1">Dirección (calle y número)</label>
                <input id="dir_linea1" type="text" name="dir_linea1" required maxlength="255"
                       autocomplete="shipping street-address"
                       value="<?= h((string)($userData['dir_linea1'] ?? '')) ?>">

                <div class="account-form-row">
                    <div>
                        <label for="dir_postal">Código postal</label>
                        <input id="dir_postal" type="text" name="dir_postal" required maxlength="32"
                               autocomplete="shipping postal-code"
                               value="<?= h((string)($userData['dir_postal'] ?? '')) ?>">
                    </div>
                    <div>
                        <label for="dir_ciudad">Ciudad</label>
                        <input id="dir_ciudad" type="text" name="dir_ciudad" required maxlength="120"
                               autocomplete="shipping address-level2"
                               value="<?= h((string)($userData['dir_ciudad'] ?? '')) ?>">
                    </div>
                </div>

                <label for="dir_pais">País</label>
                <input id="dir_pais" type="text" name="dir_pais" required maxlength="120"
                       autocomplete="shipping country-name"
                       placeholder="Ej. España"
                       value="<?= h((string)($userData['dir_pais'] ?? '')) ?>">

                <button type="submit" class="btn btn-primary">Guardar dirección</button>
            </form>
        </section>

    </div>

    <!-- Columna derecha: últimos pedidos -->
    <div class="account-col">
        <section class="account-card">
            <h2 class="account-card-title">Últimos pedidos</h2>

            <?php if ($recentOrders === []): ?>
                <p class="muted">Aún no has realizado ningún pedido. <a href="/catalog.php">Ver catálogo</a>.</p>
            <?php else: ?>
                <ul class="account-orders-list">
                    <?php foreach ($recentOrders as $o): ?>
                        <?php
                        $statusLabels = [
                            'completado' => 'Completado',
                            'pendiente'  => 'Pendiente',
                            'enviado'    => 'Enviado',
                            'cancelado'  => 'Cancelado',
                        ];
                        $statusClasses = [
                            'completado' => 'badge-green',
                            'pendiente'  => 'badge-warning',
                            'enviado'    => 'badge-green',
                            'cancelado'  => 'badge-danger',
                        ];
                        $statusLabel = $statusLabels[$o['status']] ?? ucfirst($o['status']);
                        $statusClass = $statusClasses[$o['status']] ?? 'badge-warning';
                        ?>
                        <li class="account-order-item">
                            <div class="account-order-main">
                                <span class="account-order-id">#<?= (int)$o['id'] ?></span>
                                <span class="badge <?= $statusClass ?>"><?= h($statusLabel) ?></span>
                            </div>
                            <div class="account-order-meta">
                                <?= (int)$o['items_count'] ?> artículo<?= $o['items_count'] != 1 ? 's' : '' ?>
                                · <?= date('d/m/Y', strtotime($o['created_at'])) ?>
                                · <strong><?= number_format((float)$o['total'], 2, ',', ' ') ?> €</strong>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="account-orders-link">
                    <a href="/orders.php" class="btn btn-outline btn-small">Ver todos los pedidos</a>
                </p>
            <?php endif; ?>
        </section>
    </div>

</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
