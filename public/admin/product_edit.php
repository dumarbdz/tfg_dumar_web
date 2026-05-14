<?php
declare(strict_types=1);
$pageTitle    = 'Editar producto';
$adminSection = 'products';
require __DIR__ . '/admin_layout.php';

$pdo = get_pdo();
$msg = '';
$err = '';
$isNew = !isset($_GET['id']);
$id    = $isNew ? 0 : (int) $_GET['id'];

$sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

$product = null;
$stockMap = [];
if (!$isNew) {
    $st = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
    $st->execute([$id]);
    $product = $st->fetch();
    if (!$product) {
        http_response_code(404);
        echo '<p class="adm-msg adm-msg-err">Producto no encontrado. <a href="/admin/products.php">Volver</a></p>';
        require __DIR__ . '/admin_layout_end.php';
        exit;
    }
    $stStock = $pdo->prepare('SELECT talla AS size, cantidad AS quantity FROM stock WHERE producto_id = ?');
    $stStock->execute([$id]);
    foreach ($stStock->fetchAll() as $row) {
        $stockMap[$row['size']] = (int) $row['quantity'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $err = 'Sesión inválida.';
    } else {
        $brand       = trim((string)($_POST['brand']       ?? ''));
        $model       = trim((string)($_POST['model']       ?? ''));
        $price       = (float)str_replace(',', '.', (string)($_POST['price'] ?? '0'));
        $description = trim((string)($_POST['description'] ?? ''));
        $imagePath   = trim((string)($_POST['image_path']  ?? ''));
        $active      = isset($_POST['active']);

        if ($brand === '' || $model === '' || $price <= 0) {
            $err = 'Continente, selección y precio son obligatorios.';
        } else {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $brand . '-' . $model));
            if ($isNew) {
                $st = $pdo->prepare(
                    'INSERT INTO productos (continente, seleccion, slug, descripcion, precio, imagen, activo)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $st->execute([$brand, $model, $slug, $description, $price, $imagePath, $active]);
                $id = (int) $pdo->lastInsertId();
            } else {
                $pdo->prepare(
                    'UPDATE productos SET continente=?, seleccion=?, slug=?, descripcion=?, precio=?, imagen=?, activo=? WHERE id=?'
                )->execute([$brand, $model, $slug, $description, $price, $imagePath, $active, $id]);
            }

            $stUpsert = $pdo->prepare(
                'INSERT INTO stock (producto_id, talla, cantidad)
                 VALUES (?, ?, ?)
                 ON CONFLICT (producto_id, talla) DO UPDATE SET cantidad = EXCLUDED.cantidad'
            );
            foreach ($sizes as $sz) {
                $qty = max(0, (int)($_POST['stock'][$sz] ?? 0));
                $stUpsert->execute([$id, $sz, $qty]);
            }

            $msg = $isNew ? 'Producto creado correctamente.' : 'Producto actualizado correctamente.';
            $product = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
            $product->execute([$id]);
            $product = $product->fetch();
            $stStock = $pdo->prepare('SELECT talla AS size, cantidad AS quantity FROM stock WHERE producto_id = ?');
            $stStock->execute([$id]);
            $stockMap = [];
            foreach ($stStock->fetchAll() as $row) { $stockMap[$row['size']] = (int)$row['quantity']; }
            $isNew = false;
        }
    }
}

$f = $product ?? ['continente'=>'','seleccion'=>'','precio'=>'','descripcion'=>'','imagen'=>'','activo'=>1];
?>

<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
    <a href="/admin/products.php" style="color:#7a9282;font-size:.85rem">← Volver a productos</a>
    <h1 class="adm-page-title" style="margin:0"><?= $isNew ? 'Nuevo producto' : 'Editar producto' ?></h1>
</div>

<?php if ($msg !== ''): ?><p class="adm-msg adm-msg-ok"><?= h($msg) ?></p><?php endif; ?>
<?php if ($err !== ''): ?><p class="adm-msg adm-msg-err"><?= h($err) ?></p><?php endif; ?>

<form method="post" class="adm-form" style="max-width:680px">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <label>Continente
            <select name="brand" required>
                <option value="">— Selecciona —</option>
                <?php foreach (['Europa','Sudamérica','África','Asia'] as $opt): ?>
                    <option value="<?= h($opt) ?>" <?= $f['continente'] === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Selección (modelo)
            <input type="text" name="model" required maxlength="150" value="<?= h((string)$f['seleccion']) ?>" placeholder="Ej. España">
        </label>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <label>Precio (€)
            <input type="number" name="price" required min="0.01" step="0.01" value="<?= h((string)$f['precio']) ?>">
        </label>
        <label style="justify-content:flex-end;flex-direction:row;align-items:center;gap:.5rem;padding-top:1.4rem">
            <input type="checkbox" name="active" value="1" <?= $f['activo'] ? 'checked' : '' ?>>
            Producto activo (visible en tienda)
        </label>
    </div>

    <label>Ruta de imagen
        <input type="text" name="image_path" maxlength="255" value="<?= h((string)$f['imagen']) ?>" placeholder="/images/europa-espana.svg">
    </label>

    <label>Descripción
        <textarea name="description" rows="4"><?= h((string)$f['descripcion']) ?></textarea>
    </label>

    <fieldset style="border:1.5px solid #c3e0cf;border-radius:6px;padding:1rem">
        <legend style="font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#7a9282;padding:0 .4rem">Stock por talla</legend>
        <div class="adm-stock-grid">
            <?php foreach ($sizes as $sz): ?>
                <label class="adm-stock-item"><?= h($sz) ?>
                    <input type="number" name="stock[<?= h($sz) ?>]" min="0" value="<?= $stockMap[$sz] ?? 0 ?>">
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <div style="display:flex;gap:.75rem;margin-top:.5rem">
        <button type="submit" class="adm-btn adm-btn-green"><?= $isNew ? '✓ Crear producto' : '✓ Guardar cambios' ?></button>
        <a href="/admin/products.php" class="adm-btn adm-btn-outline">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/admin_layout_end.php'; ?>
