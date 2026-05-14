<?php

declare(strict_types=1);

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Convierte el valor booleano de PostgreSQL ('t'/'f') a bool PHP. */
function pg_bool(mixed $val): bool
{
    return $val === true || $val === 't' || $val === '1' || $val === 1;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_verify(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $token);
}

/** @return array{id:int,email:string,name:string,is_admin:bool}|null */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'       => (int)  $_SESSION['user_id'],
        'email'    => (string) $_SESSION['user_email'],
        'name'     => (string) $_SESSION['user_name'],
        'is_admin' => (bool) ($_SESSION['user_is_admin'] ?? false),
    ];
}

function require_login(): void
{
    if (current_user() === null) {
        header('Location: /login.php?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
}

function require_admin(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: /login.php?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
    if (!$user['is_admin']) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso denegado</title></head>';
        echo '<body style="font-family:sans-serif;text-align:center;padding:4rem">';
        echo '<h1>403 — Acceso denegado</h1><p>No tienes permiso para acceder a esta sección.</p>';
        echo '<a href="/">Volver al inicio</a></body></html>';
        exit;
    }
    return $user;
}

/** @return list<array{product_id:int,size:string,qty:int}> */
function cart_items(): array
{
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return [];
    }
    $out = [];
    foreach ($_SESSION['cart'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $pid = isset($row['product_id']) ? (int) $row['product_id'] : 0;
        $size = isset($row['size']) ? (string) $row['size'] : '';
        $qty = isset($row['qty']) ? (int) $row['qty'] : 0;
        if ($pid > 0 && $size !== '' && $qty > 0) {
            $out[] = ['product_id' => $pid, 'size' => $size, 'qty' => $qty];
        }
    }
    return $out;
}

function cart_key(int $productId, string $size): string
{
    return $productId . '|' . $size;
}

function cart_set_qty(int $productId, string $size, int $qty): void
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $key = cart_key($productId, $size);
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
        return;
    }
    $_SESSION['cart'][$key] = [
        'product_id' => $productId,
        'size' => $size,
        'qty' => $qty,
    ];
}

/** Ruta interna segura para redirecciones ?next= */
function safe_next_url(?string $next, string $default = '/index.php'): string
{
    if (!is_string($next) || $next === '') {
        return $default;
    }
    if (!str_starts_with($next, '/') || str_starts_with($next, '//')) {
        return $default;
    }
    return $next;
}

function flash_set(string $msg, string $type = 'success'): void
{
    $_SESSION['_flash'] = ['msg' => $msg, 'type' => $type];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['_flash'])) {
        return null;
    }
    $f = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $f;
}

function checkout_clear_draft(): void
{
    unset($_SESSION['checkout']);
}

/** @return array{shipping_name:string,shipping_line1:string,shipping_postal:string,shipping_city:string,shipping_country:string,payment_method:string} */
function checkout_draft_get(): array
{
    $d = $_SESSION['checkout'] ?? [];
    if (!is_array($d)) {
        $d = [];
    }
    return [
        'shipping_name' => isset($d['shipping_name']) ? (string) $d['shipping_name'] : '',
        'shipping_line1' => isset($d['shipping_line1']) ? (string) $d['shipping_line1'] : '',
        'shipping_postal' => isset($d['shipping_postal']) ? (string) $d['shipping_postal'] : '',
        'shipping_city' => isset($d['shipping_city']) ? (string) $d['shipping_city'] : '',
        'shipping_country' => isset($d['shipping_country']) ? (string) $d['shipping_country'] : '',
        'payment_method' => isset($d['payment_method']) ? (string) $d['payment_method'] : '',
    ];
}

/** @param array{shipping_name?:string,shipping_line1?:string,shipping_postal?:string,shipping_city?:string,shipping_country?:string,payment_method?:string} $patch */
function checkout_draft_merge(array $patch): void
{
    $cur = checkout_draft_get();
    foreach ($patch as $k => $v) {
        if (array_key_exists($k, $cur)) {
            $cur[$k] = is_string($v) ? trim($v) : '';
        }
    }
    $_SESSION['checkout'] = $cur;
}

function checkout_shipping_complete(array $d): bool
{
    return $d['shipping_name'] !== ''
        && $d['shipping_line1'] !== ''
        && $d['shipping_postal'] !== ''
        && $d['shipping_city'] !== ''
        && $d['shipping_country'] !== '';
}

function checkout_payment_complete(array $d): bool
{
    return $d['payment_method'] !== '';
}

function app_base_url(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

/**
 * Envía un correo usando SMTP directo (compatible con Gmail, Outlook, etc.)
 * Lee la configuración del bloque 'smtp' en config.local.php.
 */
function smtp_send(string $to, string $subject, string $plain, string $html = ''): bool
{
    static $cfg = null;
    if ($cfg === null) {
        $path = dirname(__DIR__) . '/config/config.local.php';
        $all  = is_file($path) ? (require $path) : [];
        if (isset($all['smtp']) && !empty($all['smtp']['host'])) {
            $cfg = $all['smtp'];
        } elseif (getenv('SMTP_HOST')) {
            $cfg = [
                'host'  => (string) getenv('SMTP_HOST'),
                'port'  => (int) (getenv('SMTP_PORT') ?: 587),
                'user'  => (string) (getenv('SMTP_USER') ?: ''),
                'pass'  => (string) (getenv('SMTP_PASS') ?: ''),
                'from'  => (string) (getenv('SMTP_FROM') ?: ''),
                'admin' => (string) (getenv('ADMIN_EMAIL') ?: ''),
            ];
        } else {
            $cfg = false;
        }
    }
    if ($cfg === false) {
        return false;
    }

    $host = (string) $cfg['host'];
    $port = (int) ($cfg['port'] ?? 587);
    $user = (string) $cfg['user'];
    $pass = (string) $cfg['pass'];
    $from = (string) ($cfg['from'] ?? $user);

    $ctx    = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
    if ($socket === false) {
        return false;
    }

    $read = static function () use ($socket): int {
        $code = 0;
        while ($line = fgets($socket, 515)) {
            $code = (int) substr($line, 0, 3);
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $code;
    };
    $cmd = static function (string $c) use ($socket, $read): int {
        fwrite($socket, $c . "\r\n");
        return $read();
    };

    try {
        $read();
        $cmd('EHLO localhost');
        $cmd('STARTTLS');
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
        $cmd('EHLO localhost');
        $cmd('AUTH LOGIN');
        $cmd(base64_encode($user));
        $code = $cmd(base64_encode($pass));
        if ($code !== 235) {
            return false;
        }
        $cmd("MAIL FROM:<{$from}>");
        $cmd("RCPT TO:<{$to}>");
        $cmd('DATA');

        $enc = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        if ($html !== '') {
            $boundary = 'mws_' . bin2hex(random_bytes(8));
            $msg = "From: Mundial Store <{$from}>\r\n"
                 . "To: {$to}\r\n"
                 . "Subject: {$enc}\r\n"
                 . "MIME-Version: 1.0\r\n"
                 . "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n"
                 . "\r\n"
                 . "--{$boundary}\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n"
                 . "Content-Transfer-Encoding: base64\r\n"
                 . "\r\n"
                 . chunk_split(base64_encode($plain))
                 . "--{$boundary}\r\n"
                 . "Content-Type: text/html; charset=UTF-8\r\n"
                 . "Content-Transfer-Encoding: base64\r\n"
                 . "\r\n"
                 . chunk_split(base64_encode($html))
                 . "--{$boundary}--\r\n"
                 . "\r\n.\r\n";
        } else {
            $msg = "From: Mundial Store <{$from}>\r\n"
                 . "To: {$to}\r\n"
                 . "Subject: {$enc}\r\n"
                 . "MIME-Version: 1.0\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n"
                 . "Content-Transfer-Encoding: base64\r\n"
                 . "\r\n"
                 . chunk_split(base64_encode($plain))
                 . "\r\n.\r\n";
        }

        fwrite($socket, $msg);
        $read();
        $cmd('QUIT');
    } finally {
        fclose($socket);
    }

    return true;
}

function email_html_layout(string $content): string
{
    return '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f0f4f1;font-family:Arial,Helvetica,sans-serif;color:#1a2e22">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f1;padding:40px 16px">
  <tr><td align="center">
    <table width="580" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%">

      <!-- Cabecera -->
      <tr>
        <td style="background:#0d3320;padding:28px 40px;border-radius:10px 10px 0 0;text-align:center">
          <div style="font-size:24px;font-weight:700;color:#ffffff;letter-spacing:1px">&#9917; MUNDIAL STORE</div>
          <div style="font-size:13px;color:#7ec89a;margin-top:4px">Camisetas del Mundial 2026</div>
        </td>
      </tr>

      <!-- Cuerpo -->
      <tr>
        <td style="background:#ffffff;padding:36px 40px;border-left:1px solid #d8eadf;border-right:1px solid #d8eadf">
          ' . $content . '
        </td>
      </tr>

      <!-- Pie -->
      <tr>
        <td style="background:#f0f7f3;padding:20px 40px;border-radius:0 0 10px 10px;border:1px solid #d8eadf;border-top:none;text-align:center">
          <p style="margin:0;font-size:12px;color:#6b8a76">Mundial Store &middot; Camisetas del Mundial 2026</p>
          <p style="margin:6px 0 0;font-size:11px;color:#9ab5a3">Este mensaje es generado automáticamente, por favor no respondas a este correo.</p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>';
}

/**
 * @param list<array{label:string,size:string,qty:int,line:float}> $lines
 */
function send_order_confirmation_email(string $customerEmail, int $orderId, float $total, array $lines): bool
{
    $subject = 'Mundial Store — Confirmación de pedido #' . $orderId;

    // — Texto plano (fallback) —
    $plainRows = '';
    foreach ($lines as $ln) {
        $plainRows .= '  · ' . $ln['label'] . ' — Talla ' . $ln['size']
                    . ' x ' . (int)$ln['qty'] . ' ud.  '
                    . number_format($ln['line'], 2, ',', ' ') . " EUR\r\n";
    }
    $plain  = "Hola,\r\n\r\n";
    $plain .= "Gracias por tu compra en Mundial Store.\r\n";
    $plain .= "Tu pedido #{$orderId} se ha registrado con exito.\r\n\r\n";
    $plain .= "Detalle:\r\n" . str_repeat('-', 40) . "\r\n" . $plainRows;
    $plain .= str_repeat('-', 40) . "\r\nTOTAL: " . number_format($total, 2, ',', ' ') . " EUR\r\n\r\n";
    $plain .= "Nos pondremos en contacto contigo para gestionar el envio.\r\n";
    $plain .= "-- Mundial Store\r\n";

    // — HTML —
    $htmlRows = '';
    foreach ($lines as $ln) {
        $htmlRows .= '<tr>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #e8f0eb;font-size:14px">' . htmlspecialchars($ln['label'], ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #e8f0eb;font-size:14px;text-align:center">' . htmlspecialchars($ln['size'], ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #e8f0eb;font-size:14px;text-align:center">' . (int)$ln['qty'] . '</td>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #e8f0eb;font-size:14px;text-align:right;font-weight:600">' . number_format($ln['line'], 2, ',', ' ') . ' &euro;</td>'
            . '</tr>';
    }

    $htmlContent = '
      <p style="margin:0 0 6px;font-size:15px;color:#6b8a76;font-weight:600">CONFIRMACI&Oacute;N DE PEDIDO</p>
      <h1 style="margin:0 0 24px;font-size:26px;font-weight:700;color:#0d3320">Pedido #' . $orderId . '</h1>
      <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#2d4a37">
        Hola,<br><br>
        Gracias por tu compra en <strong>Mundial Store</strong>. Tu pedido ha sido registrado con &eacute;xito
        y nos pondremos en contacto contigo para gestionar el env&iacute;o.
      </p>

      <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:24px;border:1px solid #d8eadf;border-radius:6px;overflow:hidden">
        <thead>
          <tr style="background:#f0f7f3">
            <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:700;color:#5a7a65;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #c3e0cf">Producto</th>
            <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:700;color:#5a7a65;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #c3e0cf">Talla</th>
            <th style="padding:10px 12px;text-align:center;font-size:11px;font-weight:700;color:#5a7a65;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #c3e0cf">Cant.</th>
            <th style="padding:10px 12px;text-align:right;font-size:11px;font-weight:700;color:#5a7a65;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #c3e0cf">Importe</th>
          </tr>
        </thead>
        <tbody>' . $htmlRows . '</tbody>
        <tfoot>
          <tr style="background:#f0f7f3">
            <td colspan="3" style="padding:12px;font-size:14px;font-weight:700;color:#0d3320;border-top:2px solid #c3e0cf;text-align:right">TOTAL</td>
            <td style="padding:12px;font-size:18px;font-weight:700;color:#138a4a;border-top:2px solid #c3e0cf;text-align:right">' . number_format($total, 2, ',', ' ') . ' &euro;</td>
          </tr>
        </tfoot>
      </table>

      <div style="background:#f0f7f3;border-left:4px solid #138a4a;padding:14px 18px;border-radius:4px;font-size:13px;color:#3a5a44;line-height:1.5">
        &#128666; Te notificaremos por email cuando tu pedido sea enviado.
      </div>';

    $html = email_html_layout($htmlContent);
    $ok   = smtp_send($customerEmail, $subject, $plain, $html);

    // — Copia al administrador —
    static $smtpCfg = null;
    if ($smtpCfg === null) {
        $all = is_file(dirname(__DIR__) . '/config/config.local.php')
            ? (require dirname(__DIR__) . '/config/config.local.php') : [];
        $smtpCfg = $all['smtp'] ?? [];
    }
    $adminEmail = (string)($smtpCfg['admin'] ?? getenv('ADMIN_EMAIL') ?: '');
    if ($adminEmail !== '' && $adminEmail !== $customerEmail) {
        $adminSubject = 'Nuevo pedido #' . $orderId . ' — ' . $customerEmail;
        $adminPlain   = "Nuevo pedido recibido.\r\nCliente: {$customerEmail}\r\nPedido: #{$orderId}\r\nTotal: "
                      . number_format($total, 2, ',', ' ') . " EUR\r\n\r\n" . $plainRows;
        smtp_send($adminEmail, $adminSubject, $adminPlain);
    }

    return $ok;
}

function send_welcome_email(string $to, string $name): bool
{
    $subject = '¡Bienvenido/a a Mundial Store, ' . $name . '!';

    $plain  = "Hola {$name},\r\n\r\n";
    $plain .= "Gracias por registrarte en Mundial Store.\r\n";
    $plain .= "Ya puedes explorar nuestro catalogo de camisetas del Mundial 2026.\r\n\r\n";
    $plain .= "Visita el catalogo: https://mundial-store.vercel.app/catalog.php\r\n\r\n";
    $plain .= "-- Mundial Store\r\n";

    $nameEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $htmlContent = '
      <p style="margin:0 0 6px;font-size:15px;color:#6b8a76;font-weight:600">BIENVENIDO/A</p>
      <h1 style="margin:0 0 20px;font-size:26px;font-weight:700;color:#0d3320">Hola, ' . $nameEsc . '!</h1>
      <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#2d4a37">
        Gracias por registrarte en <strong>Mundial Store</strong>.<br>
        Ya formas parte de nuestra comunidad. Explora m&aacute;s de 30 camisetas
        oficiales de las mejores selecciones del mundo.
      </p>

      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px">
        <tr>
          <td style="background:#f0f7f3;border:1px solid #c3e0cf;border-radius:8px;padding:20px 24px">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td width="40" style="font-size:24px;vertical-align:top">&#127942;</td>
                <td style="padding-left:12px;font-size:14px;color:#2d4a37;line-height:1.5">
                  <strong style="display:block;margin-bottom:2px;color:#0d3320">Cat&aacute;logo completo</strong>
                  Europa, Sudam&eacute;rica, &Aacute;frica y Asia
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px">
        <tr>
          <td style="background:#138a4a;border-radius:6px;text-align:center">
            <a href="https://mundial-store.vercel.app/catalog.php"
               style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;letter-spacing:.3px">
              Ver cat&aacute;logo &rarr;
            </a>
          </td>
        </tr>
      </table>

      <p style="margin:0;font-size:13px;color:#8aaa96;text-align:center">
        Si no te has registrado t&uacute;, puedes ignorar este mensaje.
      </p>';

    return smtp_send($to, $subject, $plain, email_html_layout($htmlContent));
}

function send_password_reset_email(string $to, string $resetUrl): bool
{
    $subject = 'Mundial Store — Restablecer contrase&ntilde;a';

    $plain  = "Hola,\r\n\r\n";
    $plain .= "Para establecer una nueva contrasena, accede al siguiente enlace (valido durante 1 hora):\r\n\r\n";
    $plain .= $resetUrl . "\r\n\r\n";
    $plain .= "Si no has solicitado este cambio, ignora este mensaje.\r\n\r\n";
    $plain .= "-- Mundial Store\r\n";

    $urlEsc = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    $htmlContent = '
      <p style="margin:0 0 6px;font-size:15px;color:#6b8a76;font-weight:600">SEGURIDAD DE CUENTA</p>
      <h1 style="margin:0 0 20px;font-size:26px;font-weight:700;color:#0d3320">Restablecer contrase&ntilde;a</h1>
      <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#2d4a37">
        Hemos recibido una solicitud para restablecer la contrase&ntilde;a de tu cuenta.<br>
        Haz clic en el bot&oacute;n para crear una nueva. El enlace es v&aacute;lido durante <strong>1 hora</strong>.
      </p>

      <table cellpadding="0" cellspacing="0" style="margin:0 auto 28px">
        <tr>
          <td style="background:#138a4a;border-radius:6px;text-align:center">
            <a href="' . $urlEsc . '"
               style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;letter-spacing:.3px">
              Restablecer contrase&ntilde;a
            </a>
          </td>
        </tr>
      </table>

      <div style="background:#fff8f0;border:1px solid #f5d9b0;border-radius:6px;padding:14px 18px;font-size:13px;color:#7a5520;line-height:1.5;margin-bottom:20px">
        &#9888;&#65039; Si no has solicitado este cambio, ignora este mensaje. Tu contrase&ntilde;a actual seguir&aacute; siendo la misma.
      </div>

      <p style="margin:0;font-size:12px;color:#9ab5a3;text-align:center;word-break:break-all">
        Si el bot&oacute;n no funciona, copia este enlace en tu navegador:<br>
        <a href="' . $urlEsc . '" style="color:#138a4a">' . $urlEsc . '</a>
      </p>';

    return smtp_send($to, $subject, $plain, email_html_layout($htmlContent));
}

/**
 * Carga la configuración de la aplicación.
 * En producción (Vercel) lee de variables de entorno.
 * En local lee de config.local.php con fallback a config.example.php.
 *
 * @return array<string, mixed>
 */
function load_app_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    if (getenv('DATABASE_URL')) {
        $cfg = [
            'api_key' => (string)(getenv('API_KEY') ?: ''),
        ];
        return $cfg;
    }

    $local   = dirname(__DIR__) . '/config/config.local.php';
    $example = dirname(__DIR__) . '/config/config.example.php';
    $path    = is_file($local) ? $local : $example;
    /** @var array<string, mixed> $cfg */
    $cfg = require $path;
    return $cfg;
}

/**
 * Rate limiting por IP usando la base de datos.
 * Devuelve true si se ha superado el límite (la petición debe rechazarse).
 */
function api_rate_limit(string $endpoint, int $max_per_minute = 60): bool
{
    $window  = (int)(time() / 60);
    $ip_hash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . $endpoint);

    try {
        $pdo = get_pdo();

        $pdo->exec("CREATE TABLE IF NOT EXISTS api_rate_limits (
            ip_hash      VARCHAR(64) NOT NULL,
            window_minute BIGINT      NOT NULL,
            requests     INT         NOT NULL DEFAULT 1,
            PRIMARY KEY (ip_hash, window_minute)
        )");

        $st = $pdo->prepare(
            'INSERT INTO api_rate_limits (ip_hash, window_minute, requests) VALUES (?, ?, 1)
             ON CONFLICT (ip_hash, window_minute)
             DO UPDATE SET requests = api_rate_limits.requests + 1
             RETURNING requests'
        );
        $st->execute([$ip_hash, $window]);
        $count = (int)$st->fetchColumn();

        // Limpieza ocasional de ventanas antiguas (1 de cada 100 peticiones)
        if (mt_rand(1, 100) === 1) {
            $pdo->prepare('DELETE FROM api_rate_limits WHERE window_minute < ?')
                ->execute([$window - 5]);
        }

        return $count > $max_per_minute;
    } catch (\Throwable) {
        return false;
    }
}
