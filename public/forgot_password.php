<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (current_user() !== null) {
    header('Location: /index.php');
    exit;
}

$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $error = 'Sesión inválida. Vuelve a intentarlo.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Introduce un email válido.';
        } else {
            $pdo = get_pdo();
            $st = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
            $st->execute([$email]);
            $row = $st->fetch();
            if ($row) {
                try {
                    $uid = (int) $row['id'];
                    $pdo->prepare('DELETE FROM recuperaciones_password WHERE usuario_id = ?')->execute([$uid]);
                    $raw = bin2hex(random_bytes(32));
                    $hash = hash('sha256', $raw);
                    $exp = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
                    $ins = $pdo->prepare(
                        'INSERT INTO recuperaciones_password (usuario_id, token_hash, expira_en) VALUES (?, ?, ?)'
                    );
                    $ins->execute([$uid, $hash, $exp]);
                    $url = app_base_url() . '/reset_password.php?t=' . $raw;
                    send_password_reset_email($email, $url);
                } catch (PDOException $e) {
                    $error = 'No se pudo generar el enlace. ¿Has ejecutado php tools/migrate_flow.php en el servidor?';
                }
            }
            if ($error === '') {
                $done = true;
            }
        }
    }
}

$pageTitle = 'Olvidé la contraseña';
require dirname(__DIR__) . '/includes/header.php';
?>

<h1>Olvidé la contraseña</h1>
<p class="lead">Introduce el email de tu cuenta. Si existe, recibirás un enlace para elegir una nueva clave (válido 1 hora).</p>

<?php if ($error !== ''): ?>
    <p class="msg msg-error" role="alert"><?= h($error) ?></p>
<?php endif; ?>

<?php if ($done): ?>
    <p class="msg msg-success">Si ese email está registrado, te hemos enviado las instrucciones. Revisa la bandeja de entrada y el spam.</p>
    <p><a href="/login.php" class="btn btn-primary">Volver a iniciar sesión</a></p>
<?php else: ?>
    <form method="post" class="form-card" action="/forgot_password.php" novalidate>
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <label>Email
            <input type="email" name="email" required autocomplete="email" value="<?= h((string) ($_POST['email'] ?? '')) ?>">
        </label>
        <button type="submit" class="btn btn-primary">Enviar enlace</button>
    </form>
    <p class="muted"><a href="/login.php">Volver a iniciar sesión</a></p>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
