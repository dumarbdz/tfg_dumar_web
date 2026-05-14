<?php

declare(strict_types=1);

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $url = getenv('DATABASE_URL');

    if ($url) {
        // Neon / PostgreSQL en la nube
        $dsn = 'pgsql:' . str_replace('postgresql://', '', preg_replace(
            '#^postgresql://([^:]+):([^@]+)@([^/]+)/(.+?)(\?.*)?$#',
            'host=$3 dbname=$4 user=$1 password=$2 sslmode=require',
            $url
        ));
        // Parseo limpio de la URL
        $parts = parse_url($url);
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=require',
            $parts['host'],
            $parts['port'] ?? 5432,
            ltrim($parts['path'], '/')
        );
        $user = $parts['user'];
        $pass = $parts['pass'];
    } else {
        // Entorno local con config.local.php (MySQL)
        $cfg = (function () {
            $local   = dirname(__DIR__) . '/config/config.local.php';
            $example = dirname(__DIR__) . '/config/config.example.php';
            $path    = is_file($local) ? $local : $example;
            /** @var array<string, mixed> $c */
            $c = require $path;
            return $c['db'];
        })();
        $dsn  = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'], (int)$cfg['port'], $cfg['name'], $cfg['charset']);
        $user = $cfg['user'];
        $pass = $cfg['pass'];
    }

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
