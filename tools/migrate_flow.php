<?php
/**
 * Añade columnas de envío/pago en orders y tabla password_resets en bases ya existentes.
 * Uso: php tools/migrate_flow.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/bootstrap.php';

$pdo = get_pdo();

$orderCols = [
    'shipping_name' => "VARCHAR(200) NOT NULL DEFAULT ''",
    'shipping_line1' => "VARCHAR(255) NOT NULL DEFAULT ''",
    'shipping_postal' => "VARCHAR(32) NOT NULL DEFAULT ''",
    'shipping_city' => "VARCHAR(120) NOT NULL DEFAULT ''",
    'shipping_country' => "VARCHAR(120) NOT NULL DEFAULT ''",
    'payment_method' => "VARCHAR(50) NOT NULL DEFAULT ''",
];

foreach ($orderCols as $col => $def) {
    $st = $pdo->query("SHOW COLUMNS FROM orders LIKE " . $pdo->quote($col));
    if ($st && $st->rowCount() === 0) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN `{$col}` {$def}");
        echo "Añadida columna orders.{$col}\n";
    }
}

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_token_hash (token_hash),
  KEY idx_user (user_id),
  KEY idx_expires (expires_at),
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
echo "Tabla password_resets verificada.\n";
echo "Migración lista.\n";
