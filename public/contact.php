<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = current_user();

$topics = [
    'pedido'    => 'Consulta sobre un pedido',
    'producto'  => 'Información sobre productos',
    'cuenta'    => 'Problema con mi cuenta',
    'devolucion'=> 'Devoluciones y cambios',
    'envio'     => 'Envío y plazos de entrega',
    'otros'     => 'Otros',
];

$msg   = '';
$error = '';
$sent  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $error = 'Sesión inválida. Recarga la página e inténtalo de nuevo.';
    } else {
        $name    = trim((string) ($_POST['contact_name']  ?? ''));
        $email   = trim((string) ($_POST['contact_email'] ?? ''));
        $topic   = trim((string) ($_POST['contact_topic'] ?? ''));
        $body    = trim((string) ($_POST['contact_body']  ?? ''));

        if ($name === '' || $email === '' || $topic === '' || $body === '') {
            $error = 'Rellena todos los campos.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'El correo electrónico no es válido.';
        } elseif (!isset($topics[$topic])) {
            $error = 'Selecciona un tema válido.';
        } elseif (mb_strlen($body) < 10) {
            $error = 'El mensaje es demasiado corto.';
        } else {
            $topicLabel = $topics[$topic];
            $subject    = '[Mundial Store] Consulta: ' . $topicLabel;
            $mailBody   = "Nueva consulta recibida desde el formulario de contacto.\r\n";
            $mailBody  .= str_repeat('-', 44) . "\r\n";
            $mailBody  .= "Nombre:  {$name}\r\n";
            $mailBody  .= "Correo:  {$email}\r\n";
            $mailBody  .= "Tema:    {$topicLabel}\r\n";
            $mailBody  .= str_repeat('-', 44) . "\r\n\r\n";
            $mailBody  .= $body . "\r\n\r\n";
            $mailBody  .= "— Mundial Store · Formulario de contacto\r\n";

            smtp_send('pandaosobear@gmail.com', $subject, $mailBody);

            // Confirmación al usuario
            $confirmBody  = "Hola {$name},\r\n\r\n";
            $confirmBody .= "Hemos recibido tu consulta y te responderemos lo antes posible.\r\n\r\n";
            $confirmBody .= "Tema: {$topicLabel}\r\n\r\n";
            $confirmBody .= "Tu mensaje:\r\n" . str_repeat('-', 30) . "\r\n";
            $confirmBody .= $body . "\r\n" . str_repeat('-', 30) . "\r\n\r\n";
            $confirmBody .= "— Mundial Store · Camisetas del Mundial 2026\r\n";
            smtp_send($email, 'Mundial Store — Hemos recibido tu consulta', $confirmBody);

            $sent = true;
        }
    }
}

$pageTitle = 'Contacto';
require dirname(__DIR__) . '/includes/header.php';
?>

<nav aria-label="Ruta de navegación">
    <ol class="breadcrumb">
        <li><a href="/index.php">Inicio</a></li>
        <li aria-current="page">Contacto</li>
    </ol>
</nav>

<div class="contact-layout">
    <div class="contact-info">
        <h1>Contacto</h1>
        <p class="lead">¿Tienes alguna duda sobre tu pedido o nuestros productos? Escríbenos y te responderemos lo antes posible.</p>

        <ul class="contact-channels">
            <li class="contact-channel">
                <span class="contact-channel-icon">&#128666;</span>
                <div>
                    <strong>Seguimiento de pedidos</strong>
                    <span>Consulta el estado en <a href="/orders.php">Mis pedidos</a></span>
                </div>
            </li>
            <li class="contact-channel">
                <span class="contact-channel-icon">&#9201;</span>
                <div>
                    <strong>Tiempo de respuesta</strong>
                    <span>Respondemos en menos de 24&nbsp;h en días laborables</span>
                </div>
            </li>
            <li class="contact-channel">
                <span class="contact-channel-icon">&#8617;</span>
                <div>
                    <strong>Devoluciones</strong>
                    <span>Plazo de 14 días desde la recepción del pedido</span>
                </div>
            </li>
        </ul>
    </div>

    <div class="contact-form-wrap">
        <?php if ($sent): ?>
            <div class="contact-success">
                <span class="contact-success-icon">✓</span>
                <h2>¡Mensaje enviado!</h2>
                <p>Hemos recibido tu consulta. Te hemos enviado una copia a tu correo y te responderemos lo antes posible.</p>
                <a href="/index.php" class="btn btn-primary">Volver al inicio</a>
            </div>
        <?php else: ?>
            <?php if ($error !== ''): ?>
                <p class="msg msg-error"><?= h($error) ?></p>
            <?php endif; ?>

            <form method="post" action="/contact.php" class="contact-form" novalidate>
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

                <div class="contact-fields-row">
                    <label>
                        <span>Nombre</span>
                        <input type="text" name="contact_name" required maxlength="120"
                               value="<?= h($user ? $user['name'] : ($_POST['contact_name'] ?? '')) ?>"
                               placeholder="Tu nombre">
                    </label>
                    <label>
                        <span>Correo electrónico</span>
                        <input type="email" name="contact_email" required maxlength="255"
                               value="<?= h($user ? $user['email'] : ($_POST['contact_email'] ?? '')) ?>"
                               placeholder="tu@correo.com">
                    </label>
                </div>

                <label>
                    <span>Tema de la consulta</span>
                    <select name="contact_topic" required>
                        <option value="">— Selecciona un tema —</option>
                        <?php foreach ($topics as $val => $label): ?>
                            <option value="<?= h($val) ?>" <?= (($_POST['contact_topic'] ?? '') === $val) ? 'selected' : '' ?>>
                                <?= h($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span>Mensaje</span>
                    <textarea name="contact_body" required minlength="10" maxlength="2000" rows="6"
                              placeholder="Describe tu consulta con el máximo detalle posible…"><?= h($_POST['contact_body'] ?? '') ?></textarea>
                </label>

                <button type="submit" class="btn btn-primary btn-contact-send">Enviar consulta</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
