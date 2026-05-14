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
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass  = (string) ($_POST['password'] ?? '');
        if ($email === '' || $pass === '') {
            $error = 'Completa email y contraseña.';
        } else {
            $pdo = get_pdo();
            $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            // ── Rate limiting ──────────────────────────────────────────────
            $blocked = false;
            try {
                $stA = $pdo->prepare('SELECT intentos, bloqueado_hasta FROM intentos_login WHERE ip = ?');
                $stA->execute([$ip]);
                $attempt = $stA->fetch();
                if ($attempt && $attempt['bloqueado_hasta'] !== null
                    && strtotime((string) $attempt['bloqueado_hasta']) > time()) {
                    $segs  = strtotime((string) $attempt['bloqueado_hasta']) - time();
                    $error = $segs > 0
                        ? 'Demasiados intentos fallidos. Espera ' . $segs . ' segundo(s) e inténtalo de nuevo.'
                        : 'Demasiados intentos fallidos. Espera un momento e inténtalo de nuevo.';
                    $blocked = true;
                }
            } catch (\PDOException) {
                // tabla aún no creada — se ignora hasta que se ejecute setup_tables.php
            }

            if (!$blocked) {
                $st = $pdo->prepare('SELECT id, email, nombre, contrasena_hash, es_admin FROM usuarios WHERE email = ? LIMIT 1');
                $st->execute([$email]);
                $row = $st->fetch();
                if ($row && password_verify($pass, (string) $row['contrasena_hash'])) {
                    // Éxito — borrar intentos
                    try {
                        $pdo->prepare('DELETE FROM intentos_login WHERE ip = ?')->execute([$ip]);
                    } catch (\PDOException) {}

                    session_regenerate_id(true);
                    $_SESSION['user_id']       = (int)  $row['id'];
                    $_SESSION['user_email']    = $row['email'];
                    $_SESSION['user_name']     = $row['nombre'];
                    $_SESSION['user_is_admin'] = pg_bool($row['es_admin']);
                    header('Location: ' . $next);
                    exit;
                }

                // Fallo — incrementar contador; bloquear tras 5 intentos por 1 min
                try {
                    $pdo->prepare(
                        'INSERT INTO intentos_login (ip, intentos) VALUES (?, 1)
                         ON CONFLICT (ip) DO UPDATE SET
                             intentos = intentos_login.intentos + 1,
                             bloqueado_hasta = CASE WHEN intentos_login.intentos + 1 >= 5
                                 THEN NOW() + INTERVAL \'1 minute\' ELSE NULL END,
                             actualizado_en = NOW()'
                    )->execute([$ip]);

                    // Al llegar exactamente a 5 intentos, avisar al dueño de la cuenta
                    $stNew = $pdo->prepare('SELECT intentos FROM intentos_login WHERE ip = ?');
                    $stNew->execute([$ip]);
                    $newCount = (int) $stNew->fetchColumn();
                    if ($newCount === 5 && $email !== '') {
                        $stOwner = $pdo->prepare('SELECT email, nombre FROM usuarios WHERE email = ? LIMIT 1');
                        $stOwner->execute([$email]);
                        $owner = $stOwner->fetch();
                        if ($owner) {
                            $subject = 'Mundial Store — Alerta de seguridad en tu cuenta';
                            $body  = "Hola {$owner['nombre']},\r\n\r\n";
                            $body .= "Hemos detectado 5 intentos fallidos de inicio de sesión en tu cuenta ";
                            $body .= "desde la dirección IP {$ip}.\r\n\r\n";
                            $body .= "Tu cuenta ha sido bloqueada temporalmente durante 1 minuto.\r\n\r\n";
                            $body .= "Si no has sido tú, te recomendamos cambiar tu contraseña:\r\n";
                            $body .= app_base_url() . "/forgot_password.php\r\n\r\n";
                            $body .= "— Mundial Store · Camisetas del Mundial 2026\r\n";
                            smtp_send($owner['email'], $subject, $body);
                        }
                    }
                } catch (\PDOException) {}

                $error = 'Credenciales incorrectas.';
            }
        }
    }
}

$pageTitle = 'Iniciar sesión';
require dirname(__DIR__) . '/includes/header.php';
?>
<h1>Iniciar sesión</h1>
<?php if (isset($_GET['reset'])): ?>
    <p class="msg msg-success">Contraseña actualizada. Ya puedes entrar.</p>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <p class="msg msg-error" role="alert"><?= h($error) ?></p>
<?php endif; ?>
<form method="post" class="form-card" action="/login.php" novalidate>
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="next" value="<?= h($next) ?>">
    <label>Email
        <input type="email" name="email" required autocomplete="email" value="<?= h((string) ($_POST['email'] ?? '')) ?>">
    </label>
    <label>Contraseña
        <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button type="submit" class="btn btn-primary">Entrar</button>
</form>
<p class="muted"><a href="/forgot_password.php">Olvidé mi contraseña</a></p>
<p class="muted">¿No tienes cuenta? <a href="/register.php<?= $next !== '/index.php' ? '?next=' . rawurlencode($next) : '' ?>">Crear cuenta</a></p>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
