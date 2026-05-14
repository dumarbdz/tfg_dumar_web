<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
function load_app_config(): array
{
    $local = dirname(__DIR__) . '/config/config.local.php';
    $example = dirname(__DIR__) . '/config/config.example.php';
    $path = is_file($local) ? $local : $example;
    if (!is_file($path)) {
        throw new RuntimeException('Falta config: copia config.example.php a config.local.php');
    }
    /** @var array<string, mixed> $c */
    $c = require $path;
    return $c;
}

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $cfg = load_app_config()['db'];
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'],
        (int) $cfg['port'],
        $cfg['name'],
        $cfg['charset']
    );
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    // Railway / MySQL en la nube: TLS; sin esto a veces falla la verificación del certificado (PHP 8.2+).
    if (!empty($cfg['mysql_disable_ssl_verify']) && defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $opts);
    return $pdo;
}
