<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$pdo = get_pdo();

$tables = [
    'favoritos' => "CREATE TABLE IF NOT EXISTS favoritos (
        usuario_id  INT UNSIGNED NOT NULL,
        producto_id INT UNSIGNED NOT NULL,
        creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (usuario_id, producto_id),
        KEY idx_usuario (usuario_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'intentos_login' => "CREATE TABLE IF NOT EXISTS intentos_login (
        ip              VARCHAR(45) NOT NULL,
        intentos        TINYINT UNSIGNED NOT NULL DEFAULT 1,
        ultimo_intento  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        bloqueado_hasta DATETIME NULL,
        PRIMARY KEY (ip)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

$results = [];
foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        $results[$name] = '✅ OK';
    } catch (\PDOException $e) {
        $results[$name] = '❌ ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Setup tablas</title></head>
<body style="font-family:sans-serif;padding:2rem;max-width:600px">
<h1>Crear tablas nuevas</h1>
<?php foreach ($results as $t => $r): ?>
    <p><code><?= h($t) ?></code>: <?= $r ?></p>
<?php endforeach; ?>
<p><strong>Hecho.</strong> Puedes borrar este archivo.</p>
<p><a href="/admin/">Volver al panel</a></p>
</body></html>
