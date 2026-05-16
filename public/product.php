<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /index.php');
    exit;
}

$pdo     = get_pdo();
$user    = current_user();

$st = $pdo->prepare('SELECT * FROM productos WHERE id = ? AND activo = TRUE');
$st->execute([$id]);
$product = $st->fetch();
if (!$product) {
    http_response_code(404);
    $pageTitle = 'No encontrado';
    require dirname(__DIR__) . '/includes/header.php';
    echo '<h1>Producto no encontrado</h1><p><a href="/catalog.php">Volver al catálogo</a></p>';
    require dirname(__DIR__) . '/includes/footer.php';
    exit;
}

$st2 = $pdo->prepare("SELECT talla AS size, cantidad AS quantity FROM stock WHERE producto_id = ? ORDER BY CASE talla WHEN 'XS' THEN 1 WHEN 'S' THEN 2 WHEN 'M' THEN 3 WHEN 'L' THEN 4 WHEN 'XL' THEN 5 WHEN 'XXL' THEN 6 ELSE 7 END");
$st2->execute([$id]);
$stockRows = $st2->fetchAll();

// ── Valoraciones ──────────────────────────────────────────────
$stRating = $pdo->prepare(
    'SELECT COUNT(*) AS total, ROUND(AVG(puntuacion),1) AS media FROM valoraciones WHERE producto_id = ?'
);
$stRating->execute([$id]);
$ratingStats = $stRating->fetch();

$reviews = $pdo->prepare(
    'SELECT v.puntuacion, v.comentario, v.creado_en, u.nombre AS autor
     FROM valoraciones v JOIN usuarios u ON u.id = v.usuario_id
     WHERE v.producto_id = ? ORDER BY v.creado_en DESC'
);
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();

$inWishlist = false;
if ($user !== null) {
    try {
        $stWish = $pdo->prepare('SELECT 1 FROM favoritos WHERE usuario_id = ? AND producto_id = ?');
        $stWish->execute([$user['id'], $id]);
        $inWishlist = (bool) $stWish->fetchColumn();
    } catch (\PDOException) {}
}

$canReview   = false;
$alreadyReviewed = false;
$userReview  = null;

if ($user !== null) {
    $stBought = $pdo->prepare(
        'SELECT COUNT(*) FROM lineas_pedido l
         JOIN pedidos p ON p.id = l.pedido_id
         WHERE p.usuario_id = ? AND l.producto_id = ?'
    );
    $stBought->execute([$user['id'], $id]);
    $canReview = (int)$stBought->fetchColumn() > 0;

    $stMine = $pdo->prepare('SELECT * FROM valoraciones WHERE producto_id = ? AND usuario_id = ?');
    $stMine->execute([$id, $user['id']]);
    $userReview = $stMine->fetch();
    $alreadyReviewed = (bool)$userReview;
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $err = 'Sesión inválida.';
    } elseif (isset($_POST['add_to_cart'])) {
        $selSize = trim((string) ($_POST['size'] ?? ''));
        $qty = max(1, (int) ($_POST['qty'] ?? 1));
        if ($selSize === '') {
            $err = 'Elige una talla.';
        } else {
            $st3 = $pdo->prepare('SELECT cantidad FROM stock WHERE producto_id = ? AND talla = ?');
            $st3->execute([$id, $selSize]);
            $row = $st3->fetch();
            $avail = $row ? (int) $row['cantidad'] : 0;
            if ($avail <= 0) {
                $err = 'Esa talla no está disponible.';
            } elseif ($qty > $avail) {
                $err = 'Cantidad superior al stock (' . $avail . ').';
            } else {
                $key = cart_key($id, $selSize);
                $current = 0;
                if (!empty($_SESSION['cart'][$key]) && is_array($_SESSION['cart'][$key])) {
                    $current = (int) ($_SESSION['cart'][$key]['qty'] ?? 0);
                }
                cart_set_qty($id, $selSize, min($avail, $current + $qty));
                $msg = 'Añadido al carrito.';
            }
        }
    } elseif (isset($_POST['submit_review']) && $user !== null && $canReview) {
        $puntuacion = (int) ($_POST['puntuacion'] ?? 0);
        $comentario = trim((string) ($_POST['comentario'] ?? ''));
        if ($puntuacion < 1 || $puntuacion > 5) {
            $err = 'Elige una puntuación entre 1 y 5.';
        } else {
            if ($alreadyReviewed) {
                $pdo->prepare('UPDATE valoraciones SET puntuacion=?, comentario=? WHERE producto_id=? AND usuario_id=?')
                    ->execute([$puntuacion, $comentario ?: null, $id, $user['id']]);
                flash_set('Tu valoración ha sido actualizada.');
                header('Location: /product.php?id=' . $id . '#valoraciones');
                exit;
            } else {
                $pdo->prepare('INSERT INTO valoraciones (producto_id, usuario_id, puntuacion, comentario) VALUES (?,?,?,?)')
                    ->execute([$id, $user['id'], $puntuacion, $comentario ?: null]);
                flash_set('Valoración enviada. ¡Gracias!');
                header('Location: /product.php?id=' . $id . '#valoraciones');
                exit;
            }
            // Recargar stats y reviews
            $stRating->execute([$id]);
            $ratingStats = $stRating->fetch();
            $reviews = $pdo->prepare(
                'SELECT v.puntuacion, v.comentario, v.creado_en, u.nombre AS autor
                 FROM valoraciones v JOIN usuarios u ON u.id = v.usuario_id
                 WHERE v.producto_id = ? ORDER BY v.creado_en DESC'
            );
            $reviews->execute([$id]);
            $reviews = $reviews->fetchAll();
            $stMine->execute([$id, $user['id']]);
            $userReview = $stMine->fetch();
        }
    }
}

$pageTitle = $product['seleccion'] . ' · ' . $product['continente'];
require dirname(__DIR__) . '/includes/header.php';

function stars(float $n): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $n ? '★' : '☆';
    }
    return $html;
}
?>
<nav aria-label="Ruta de navegación">
    <ol class="breadcrumb">
        <li><a href="/index.php">Inicio</a></li>
        <li><a href="/catalog.php">Catálogo</a></li>
        <li><a href="/catalog.php?brand=<?= rawurlencode((string) $product['continente']) ?>"><?= h((string) $product['continente']) ?></a></li>
        <li aria-current="page"><?= h((string) $product['seleccion']) ?></li>
    </ol>
</nav>
<?php
$sizesInStock = array_filter($stockRows, static fn($s) => (int) $s['quantity'] > 0);
$maxStock = $sizesInStock !== [] ? max(array_column(iterator_to_array((function() use($sizesInStock){ foreach($sizesInStock as $s) yield $s; })()), 'quantity')) : 0;
?>
<article class="product-detail">
    <div class="product-detail__media">
        <div class="product-media-wrap">
            <img src="<?= h((string) $product['imagen']) ?>"
                 alt="Camiseta <?= h((string) $product['continente'] . ' · ' . (string) $product['seleccion']) ?>"
                 width="400" height="300">
        </div>
    </div>

    <div class="product-detail__info">
        <div>
            <span class="product-continent-tag"><?= h((string) $product['continente']) ?></span>
        </div>
        <h1><?= h((string) $product['seleccion']) ?></h1>

        <?php if ((int)$ratingStats['total'] > 0): ?>
            <a href="#valoraciones" class="product-rating-summary">
                <span class="product-rating-stars"><?= stars((float)$ratingStats['media']) ?></span>
                <strong class="product-rating-score"><?= $ratingStats['media'] ?></strong>
                <span class="product-rating-count">(<?= (int)$ratingStats['total'] ?> valoraci<?= (int)$ratingStats['total'] === 1 ? 'ón' : 'ones' ?>)</span>
            </a>
        <?php endif; ?>

        <div class="product-price-block">
            <span class="price big"><?= number_format((float) $product['precio'], 2, ',', ' ') ?> €</span>
            <span class="price-note">IVA incluido · Envío gratuito</span>
        </div>

        <div class="prose"><?= nl2br(h((string) $product['descripcion'])) ?></div>

        <?php if ($msg !== ''): ?>
            <script>showToast(<?= json_encode($msg) ?>);</script>
        <?php endif; ?>
        <?php if ($err !== ''): ?>
            <script>showToast(<?= json_encode($err) ?>, 'error');</script>
        <?php endif; ?>

        <?php if ($sizesInStock === []): ?>
            <p class="msg msg-info" style="margin-top:1.25rem">Sin stock disponible. <a href="/catalog.php">Ver catálogo</a>.</p>
        <?php else: ?>
            <!-- Selector visual de tallas -->
            <div class="size-section">
                <div class="size-section-label">
                    Talla <span id="size-chosen-label">— elige una talla</span>
                </div>
                <div class="size-chips">
                    <?php foreach ($stockRows as $s):
                        $qty = (int) $s['quantity'];
                        $cls = $qty <= 0 ? 'size-out' : ($qty <= 3 ? 'size-low' : '');
                    ?>
                    <button type="button"
                            class="size-chip <?= $cls ?>"
                            data-size="<?= h((string) $s['size']) ?>"
                            data-stock="<?= $qty ?>"
                            <?= $qty <= 0 ? 'disabled' : '' ?>>
                        <?= h((string) $s['size']) ?>
                        <?php if ($qty > 0 && $qty <= 3): ?>
                            <span class="size-chip-badge">¡Últimas!</span>
                        <?php elseif ($qty <= 0): ?>
                            <span class="size-chip-badge">Agot.</span>
                        <?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Formulario oculto (fallback sin JS + envío AJAX) -->
            <form method="post" class="form-inline" id="form-add-cart" action="/product.php?id=<?= $id ?>">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="add_to_cart" value="1">
                <input type="hidden" name="size" id="input-size" value="">

                <div class="cart-action-row">
                    <div class="qty-control">
                        <button type="button" class="qty-btn" id="qty-minus">−</button>
                        <input type="number" name="qty" id="qty-input" value="1" min="1" max="<?= (int) $maxStock ?>">
                        <button type="button" class="qty-btn" id="qty-plus">+</button>
                    </div>
                    <button type="submit" class="btn btn-primary btn-add-cart" id="btn-add-cart">
                        Añadir al carrito
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <div class="actions-row product-after-cart">
            <a href="/catalog.php" class="btn btn-outline">← Catálogo</a>
            <a href="/cart.php" class="btn btn-secondary">Ver carrito</a>
            <?php if ($user !== null): ?>
                <form method="post" action="/wishlist_toggle.php" style="display:contents">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="product_id" value="<?= $id ?>">
                    <input type="hidden" name="next" value="/product.php?id=<?= $id ?>">
                    <button type="submit" class="btn btn-outline" style="min-width:10rem">
                        <?= $inWishlist ? '❤ En favoritos' : '♡ Guardar' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</article>

<!-- ── VALORACIONES ───────────────────────────────────────── -->
<section id="valoraciones" class="reviews-section">
    <h2 class="reviews-title">Valoraciones</h2>

    <?php if ($canReview): ?>
        <div class="review-form-card">
            <p class="review-form-heading">
                <?= $alreadyReviewed ? 'Editar tu valoración' : 'Deja tu valoración' ?>
            </p>
            <form method="post" action="/product.php?id=<?= $id ?>">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="submit_review" value="1">
                <div class="star-picker" data-current="<?= $userReview ? (int)$userReview['puntuacion'] : 0 ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label class="star-label<?= ($userReview && (int)$userReview['puntuacion'] >= $i) ? ' is-lit' : '' ?>" data-val="<?= $i ?>">
                            <input type="radio" name="puntuacion" value="<?= $i ?>"
                                   <?= ($userReview && (int)$userReview['puntuacion'] === $i) ? 'checked' : '' ?>>&#9733;
                        </label>
                    <?php endfor; ?>
                </div>
                <label class="review-comment-label">
                    Comentario (opcional)
                    <textarea name="comentario" rows="3" class="review-textarea"><?= h((string)($userReview['comentario'] ?? '')) ?></textarea>
                </label>
                <button type="submit" class="btn btn-primary">
                    <?= $alreadyReviewed ? 'Actualizar valoración' : 'Enviar valoración' ?>
                </button>
            </form>
        </div>
    <?php elseif ($user === null): ?>
        <p class="review-gate muted">
            <a href="/login.php?next=<?= rawurlencode('/product.php?id='.$id) ?>">Inicia sesión</a> y compra este producto para dejar una valoración.
        </p>
    <?php elseif (!$canReview): ?>
        <p class="review-gate muted">Solo los clientes que han comprado este producto pueden valorarlo.</p>
    <?php endif; ?>

    <?php if ($reviews === []): ?>
        <p class="muted">Todavía no hay valoraciones para este producto.</p>
    <?php else: ?>
        <ul class="review-list">
            <?php foreach ($reviews as $r): ?>
                <li class="review-card">
                    <div class="review-avatar"><?= mb_strtoupper(mb_substr((string)$r['autor'], 0, 1)) ?></div>
                    <div class="review-body">
                        <div class="review-meta">
                            <span class="review-stars"><?= stars((float)$r['puntuacion']) ?></span>
                            <strong class="review-author"><?= h($r['autor']) ?></strong>
                            <span class="review-date muted"><?= date('d/m/Y', strtotime($r['creado_en'])) ?></span>
                        </div>
                        <?php if ($r['comentario'] !== null && $r['comentario'] !== ''): ?>
                            <p class="review-comment"><?= nl2br(h($r['comentario'])) ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<script>
// Chips de talla + qty control + AJAX carrito
(function () {
    var chips     = document.querySelectorAll('.size-chip:not(:disabled)');
    var inputSize = document.getElementById('input-size');
    var lbl       = document.getElementById('size-chosen-label');
    var qtyInput  = document.getElementById('qty-input');
    var form      = document.getElementById('form-add-cart');

    if (!form) return;

    var currentStock = 0;

    // Chips
    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            chips.forEach(function (c) { c.classList.remove('selected'); });
            chip.classList.add('selected');
            var size  = chip.dataset.size;
            var stock = parseInt(chip.dataset.stock, 10);
            currentStock = stock;
            inputSize.value = size;
            if (lbl) lbl.textContent = size + (stock <= 3 ? ' — ¡Últimas unidades!' : '');
            if (qtyInput) { qtyInput.max = stock; if (parseInt(qtyInput.value,10) > stock) qtyInput.value = stock; }
        });
    });

    // Qty +/−
    document.getElementById('qty-minus') && document.getElementById('qty-minus').addEventListener('click', function () {
        var v = parseInt(qtyInput.value, 10);
        if (v > 1) qtyInput.value = v - 1;
    });
    document.getElementById('qty-plus') && document.getElementById('qty-plus').addEventListener('click', function () {
        var v = parseInt(qtyInput.value, 10);
        var max = currentStock || parseInt(qtyInput.max, 10) || 99;
        if (v < max) qtyInput.value = v + 1;
    });

    // AJAX submit
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!inputSize.value) { showToast('Elige una talla.', 'error'); return; }
        var data = new FormData(form);
        data.append('product_id', <?= $id ?>);
        var btn = document.getElementById('btn-add-cart');
        var originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Añadiendo…';
        fetch('/cart_add.php', { method: 'POST', body: data })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                btn.disabled = false;
                btn.textContent = originalText;
                if (res.success) {
                    showToast(res.message);
                    var cartLink = document.querySelector('.nav-cart');
                    if (cartLink) {
                        var badge = cartLink.querySelector('.nav-cart-badge');
                        if (res.cart_count > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'nav-cart-badge';
                                cartLink.appendChild(badge);
                            }
                            badge.textContent = res.cart_count;
                        }
                        cartLink.classList.remove('cart-bounce');
                        void cartLink.offsetWidth;
                        cartLink.classList.add('cart-bounce');
                    }
                } else {
                    showToast(res.error, 'error');
                }
            })
            .catch(function () { btn.disabled = false; btn.textContent = originalText; showToast('Error de conexión.', 'error'); });
    });
})();

// Estrellas interactivas
(function () {
    var picker = document.querySelector('.star-picker');
    if (!picker) return;
    var labels = Array.from(picker.querySelectorAll('.star-label'));

    function setLit(upTo) {
        labels.forEach(function (l, i) {
            l.classList.toggle('is-lit', i < upTo);
        });
    }

    labels.forEach(function (lbl, idx) {
        lbl.addEventListener('mouseenter', function () { setLit(idx + 1); });
        lbl.addEventListener('click', function () { setLit(idx + 1); });
    });
    picker.addEventListener('mouseleave', function () {
        var checked = picker.querySelector('input:checked');
        setLit(checked ? parseInt(checked.value, 10) : 0);
    });
})();
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
