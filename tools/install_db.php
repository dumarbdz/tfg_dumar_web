<?php
/**
 * Crea la base de datos e importa sql/schema.sql y sql/seed.sql.
 * Uso (desde la raíz del proyecto): php tools/install_db.php
 * Requisito: servidor MySQL en marcha (p. ej. XAMPP → Start MySQL).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$configPath = $root . '/config/config.local.php';
if (!is_file($configPath)) {
    $configPath = $root . '/config/config.example.php';
}
if (!is_file($configPath)) {
    fwrite(STDERR, "No se encontró config.\n");
    exit(1);
}
/** @var array<string, mixed> $cfg */
$cfg = require $configPath;
$db = $cfg['db'];
$host = (string) $db['host'];
$port = (int) $db['port'];
$user = (string) $db['user'];
$pass = (string) $db['pass'];
$name = (string) $db['name'];

if (!extension_loaded('mysqli')) {
    fwrite(STDERR, "Extensión mysqli no disponible. Activa mysqli en php.ini.\n");
    exit(1);
}

$mysqli = @new mysqli($host, $user, $pass, '', $port);
if ($mysqli->connect_error) {
    fwrite(STDERR, "No se pudo conectar a MySQL (¿está iniciado el servicio?): {$mysqli->connect_error}\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$sqlCreate = sprintf(
    "CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    $mysqli->real_escape_string($name)
);
if (!$mysqli->query($sqlCreate)) {
    fwrite(STDERR, "Error al crear la base: {$mysqli->error}\n");
    exit(1);
}

if (!$mysqli->select_db($name)) {
    fwrite(STDERR, "No se pudo usar la base {$name}: {$mysqli->error}\n");
    exit(1);
}

function run_sql_file(mysqli $mysqli, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException("No se pudo leer: {$path}");
    }
    if (!$mysqli->multi_query($sql)) {
        throw new RuntimeException($mysqli->error);
    }
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    if ($mysqli->errno !== 0) {
        throw new RuntimeException($mysqli->error);
    }
}

$schema = $root . '/sql/schema.sql';
$seed = $root . '/sql/seed.sql';

try {
    echo "Importando esquema...\n";
    run_sql_file($mysqli, $schema);
    echo "Importando datos semilla...\n";
    run_sql_file($mysqli, $seed);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$mysqli->close();
echo "Base `{$name}` lista.\n";
echo "Si partías de una base antigua sin columnas de envío/pago ni password_resets, ejecuta: php tools/migrate_flow.php\n";
