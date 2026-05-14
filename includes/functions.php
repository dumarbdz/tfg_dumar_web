<?php

declare(strict_types=1);

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
function smtp_send(string $to, string $subject, string $body): bool
{
    static $cfg = null;
    if ($cfg === null) {
        $path = dirname(__DIR__) . '/config/config.local.php';
        $all  = is_file($path) ? (require $path) : [];
        $cfg  = (isset($all['smtp']) && !empty($all['smtp']['host'])) ? $all['smtp'] : false;
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
        $msg = "From: Mundial Store <{$from}>\r\n"
             . "To: {$to}\r\n"
             . "Subject: {$enc}\r\n"
             . "MIME-Version: 1.0\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n"
             . "Content-Transfer-Encoding: base64\r\n"
             . "\r\n"
             . chunk_split(base64_encode($body))
             . "\r\n.\r\n";
        fwrite($socket, $msg);
        $read();
        $cmd('QUIT');
    } finally {
        fclose($socket);
    }

    return true;
}

/**
 * @param list<array{label:string,size:string,qty:int,line:float}> $lines
 */
function send_order_confirmation_email(string $customerEmail, int $orderId, float $total, array $lines): bool
{
    $detalle  = str_repeat('-', 40) . "\r\n";
    foreach ($lines as $ln) {
        $detalle .= '  · ' . $ln['label'] . ' — Talla ' . $ln['size']
                  . ' × ' . (int) $ln['qty'] . ' ud.  →  '
                  . number_format($ln['line'], 2, ',', ' ') . " €\r\n";
    }
    $detalle .= str_repeat('-', 40) . "\r\n";
    $detalle .= 'TOTAL: ' . number_format($total, 2, ',', ' ') . " €\r\n";

    // — Email al cliente —
    $subjectCliente = 'Mundial Store — Confirmación de pedido #' . $orderId;
    $bodyCliente  = "Hola,\r\n\r\n";
    $bodyCliente .= "Gracias por tu compra en Mundial Store.\r\n";
    $bodyCliente .= "Tu pedido #{$orderId} se ha registrado con éxito.\r\n\r\n";
    $bodyCliente .= "Detalle del pedido:\r\n" . $detalle . "\r\n";
    $bodyCliente .= "Nos pondremos en contacto contigo para gestionar el envío.\r\n\r\n";
    $bodyCliente .= "— Mundial Store · Camisetas del Mundial 2026\r\n";
    $ok = smtp_send($customerEmail, $subjectCliente, $bodyCliente);

    // — Copia al administrador (si está configurado) —
    static $smtpCfg = null;
    if ($smtpCfg === null) {
        $all = is_file(dirname(__DIR__) . '/config/config.local.php')
            ? (require dirname(__DIR__) . '/config/config.local.php') : [];
        $smtpCfg = $all['smtp'] ?? [];
    }
    $adminEmail = (string) ($smtpCfg['admin'] ?? '');
    if ($adminEmail !== '' && $adminEmail !== $customerEmail) {
        $subjectAdmin = 'Nuevo pedido #' . $orderId . ' — ' . $customerEmail;
        $bodyAdmin  = "Nuevo pedido recibido.\r\n\r\n";
        $bodyAdmin .= "Cliente: {$customerEmail}\r\n";
        $bodyAdmin .= "Pedido: #{$orderId}\r\n\r\n";
        $bodyAdmin .= $detalle . "\r\n";
        smtp_send($adminEmail, $subjectAdmin, $bodyAdmin);
    }

    return $ok;
}

function send_password_reset_email(string $to, string $resetUrl): bool
{
    $subject = 'Mundial Store — Restablecer contraseña';
    $body  = "Hola,\r\n\r\n";
    $body .= "Para establecer una nueva contraseña, accede al siguiente enlace (válido durante 1 hora):\r\n\r\n";
    $body .= $resetUrl . "\r\n\r\n";
    $body .= "Si no has solicitado este cambio, ignora este mensaje.\r\n\r\n";
    $body .= "— Mundial Store · Camisetas del Mundial 2026\r\n";

    return smtp_send($to, $subject, $body);
}
