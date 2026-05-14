<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

// Handler de sesión en base de datos — necesario en Vercel (serverless, sin disco persistente)
if (getenv('DATABASE_URL')) {
    session_set_save_handler(
        // open
        static fn() => true,
        // close
        static fn() => true,
        // read
        static function (string $id): string {
            try {
                $st = get_pdo()->prepare('SELECT data FROM sessions WHERE id = ?');
                $st->execute([$id]);
                return (string)($st->fetchColumn() ?: '');
            } catch (\Throwable) {
                return '';
            }
        },
        // write
        static function (string $id, string $data): bool {
            try {
                $st = get_pdo()->prepare(
                    'INSERT INTO sessions (id, data, last_activity)
                     VALUES (?, ?, ?)
                     ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_activity = EXCLUDED.last_activity'
                );
                $st->execute([$id, $data, time()]);
                return true;
            } catch (\Throwable) {
                return false;
            }
        },
        // destroy
        static function (string $id): bool {
            try {
                get_pdo()->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]);
                return true;
            } catch (\Throwable) {
                return false;
            }
        },
        // gc
        static function (int $maxlifetime): int|false {
            try {
                $st = get_pdo()->prepare('DELETE FROM sessions WHERE last_activity < ?');
                $st->execute([time() - $maxlifetime]);
                return $st->rowCount();
            } catch (\Throwable) {
                return false;
            }
        }
    );
}

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require_once __DIR__ . '/functions.php';
