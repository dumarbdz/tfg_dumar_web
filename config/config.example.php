<?php
/**
 * Copiar a config.local.php y ajustar credenciales MySQL.
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'mundial_store',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
        // Solo si conectas a MySQL en la nube con TLS (p. ej. Railway) y da error de certificado (PHP 8.2+):
        // 'mysql_disable_ssl_verify' => true,
    ],
];
