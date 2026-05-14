<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (current_user() !== null) {
    header('Location: /index.php');
    exit;
}

$token = isset($_GET['t']) ? trim((string) $_GET['t']) : '';
if ($token === '' || strlen($token) < 32) {
    http_response_code(400);
    $pageTitle = 'Enlace inválido';
    require dirname(__DIR__) . '/includes/header.php';
    echo '<h1>Enlace no válido</h1><p><a href="/forgot_password.php">Solicitar uno nuevo</a></p>';
    require dirname(__DIR__) . '/includes/footer.php';
    exit;
}

$hash = hash('sha256', $token);
$pdo = get_pdo();
$st = $pdo->prepare(
    'SELECT id, usuario_id FROM recuperaciones_password WHERE token_hash = ? AND usado_en IS NULL AND expira_en > NOW() LIMIT 1'
);
$st->execute([$hash]);
$pr = $st->fetch();
if (!$pr) {
    http_response_code(400);
    $pageTitle = 'Enlace caducado';
    require dirname(__DIR__) . '/includes/header.php';
    echo '<h1>Enlace caducado o ya usado</h1><p><a href="/forgot_password.php">Solicitar uno nuevo</a></p>';
    require dirname(__DIR__) . '/includes/footer.php';
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $error = 'Sesión inválida.';
    } else {
        $p1 = (string) ($_POST['password'] ?? '');
        $p2 = (string) ($_POST['password2'] ?? '');
        if (strlen($p1) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($p1 !== $p2) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            try {
                $pdo->beginTransaction();
                $newHash = password_hash($p1, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE usuarios SET contrasena_hash = ? WHERE id = ?')->execute([$newHash, (int) $pr['usuario_id']]);
                $pdo->prepare('UPDATE recuperaciones_password SET usado_en = NOW() WHERE id = ?')->execute([(int) $pr['id']]);
                $pdo->commit();
                header('Location: /login.php?reset=1');
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'No se pudo actualizar la contraseña.';
            }
        }
    }
}

$pageTitle = 'Nueva contraseña';
require dirname(__DIR__) . '/includes/header.php';
?>

<h1>Elegir nueva contraseña</h1>
<?php if ($error !== ''): ?>
    <p class="msg msg-error" role="alert"><?= h($error) ?></p>
<?php endif; ?>
<form method="post" class="form-card" action="/reset_password.php?t=<?= h($token) ?>" novalidate>
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <label>Nueva contraseña (mín. 8)
        <input type="password" name="password" required minlength="8" autocomplete="new-password">
    </label>
    <label>Repetir contraseña
        <input type="password" name="password2" required minlength="8" autocomplete="new-password">
    </label>
    <button type="submit" class="btn btn-primary">Guardar</button>
</form>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
