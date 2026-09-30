<?php
use App\Core\Env;

return [
    'app' => [
        'name'     => Env::get('APP_NAME', 'BARET'),
        'env'      => Env::get('APP_ENV', 'production'),
        'debug'    => (bool) Env::get('APP_DEBUG', false),
        'url'      => rtrim((string) Env::get('APP_URL', ''), '/'),
        'key'      => (string) Env::get('APP_KEY', ''),
        'timezone' => 'Asia/Jakarta',
    ],
    'db' => [
        'host'    => Env::get('DB_HOST', '127.0.0.1'),
        'port'    => (int) Env::get('DB_PORT', 3306),
        'socket'  => Env::get('DB_SOCKET', ''),
        'name'    => Env::get('DB_DATABASE', 'baret'),
        'user'    => Env::get('DB_USERNAME', 'root'),
        'pass'    => (string) Env::get('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'from'      => Env::get('MAIL_FROM', 'noreply@baret.or.id'),
        'from_name' => Env::get('MAIL_FROM_NAME', 'BARET'),
        // 'log' = tulis ke storage/logs/mail.log (pengembangan), 'mail' = fungsi mail() PHP
        'driver'    => Env::get('MAIL_DRIVER', 'log'),
    ],
    'upload' => [
        'max_image' => 2 * 1024 * 1024,
        'max_doc'   => 5 * 1024 * 1024,
        'image_max_width' => 1600,
    ],
    'security' => [
        'login_max_attempts' => 5,
        'login_lock_minutes' => 15,
        'session_idle'       => 7200,
        'public_form_limit'  => 5,   // kiriman form publik per IP per jam
    ],
    'per_page' => 15,
];
