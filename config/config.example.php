<?php
/**
 * Configuración local (MySQL/XAMPP).
 * Copiar a config.local.php y ajustar credenciales.
 *
 * En producción (Vercel + Neon) NO se usa este archivo.
 * Las credenciales se pasan como variables de entorno en el panel de Vercel:
 *
 *   DATABASE_URL  → connection string de Neon
 *                   postgresql://user:pass@host/dbname?sslmode=require
 *   API_KEY       → clave para la API REST
 *   SMTP_HOST     → smtp-relay.brevo.com
 *   SMTP_PORT     → 587
 *   SMTP_USER     → usuario de Brevo
 *   SMTP_PASS     → contraseña de Brevo
 *   SMTP_FROM     → dirección de correo remitente
 *   ADMIN_EMAIL   → correo que recibe copia de cada pedido
 */
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'mundial_store',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'api_key'    => 'CHANGE_ME_pon_aqui_tu_api_key',
    'smtp' => [
        'host' => 'smtp-relay.brevo.com',
        'port' => 587,
        'user' => '',
        'pass' => '',
        'from' => 'noreply@example.com',
    ],
    'admin_email' => 'admin@example.com',
];
