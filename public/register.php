<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (current_user() !== null) {
    header('Location: /index.php');
    exit;
}

$error = '';
$next = safe_next_url(isset($_GET['next']) ? (string) $_GET['next'] : null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $next = safe_next_url(isset($_POST['next']) ? (string) $_POST['next'] : null);
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $error = 'Sesión inválida. Vuelve a intentarlo.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password2'] ?? '');
        if ($name === '' || $email === '' || $pass === '') {
            $error = 'Completa todos los campos obligatorios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'El email no es válido.';
        } elseif (strlen($pass) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($pass !== $pass2) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $pdo = get_pdo();
            try {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $st = $pdo->prepare('INSERT INTO usuarios (email, contrasena_hash, nombre) VALUES (?, ?, ?)');
                $st->execute([$email, $hash, $name]);
                $uid = (int) $pdo->lastInsertId();
                session_regenerate_id(true);
                $_SESSION['user_id']       = $uid;
                $_SESSION['user_email']    = $email;
                $_SESSION['user_name']     = $name;
                $_SESSION['user_is_admin'] = false;
                header('Location: ' . $next);
                exit;
            } catch (PDOException $e) {
                $dup = isset($e->errorInfo[0]) && $e->errorInfo[0] === '23505';
                $error = $dup ? 'Ese email ya está registrado.' : 'No se pudo crear la cuenta. Inténtalo más tarde.';
            }
        }
    }
}

$pageTitle = 'Crear cuenta';
require dirname(__DIR__) . '/includes/header.php';
?>
<h1>Registro (crear cuenta)</h1>
<?php if ($error !== ''): ?>
    <p class="msg msg-error" role="alert"><?= h($error) ?></p>
<?php endif; ?>
<form method="post" class="form-card" action="/register.php" novalidate>
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="next" value="<?= h($next) ?>">
    <label>Nombre
        <input type="text" name="name" required autocomplete="name" maxlength="100" value="<?= h((string) ($_POST['name'] ?? '')) ?>">
    </label>
    <label>Email
        <input type="email" name="email" required autocomplete="email" value="<?= h((string) ($_POST['email'] ?? '')) ?>">
    </label>
    <label>Contraseña (mín. 8 caracteres)
        <input type="password" name="password" required autocomplete="new-password" minlength="8">
    </label>
    <label>Repetir contraseña
        <input type="password" name="password2" required autocomplete="new-password" minlength="8">
    </label>
    <button type="submit" class="btn btn-primary">Registrarme</button>
</form>
<p class="muted">¿Ya tienes cuenta? <a href="/login.php<?= $next !== '/index.php' ? '?next=' . rawurlencode($next) : '' ?>">Iniciar sesión</a></p>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
