<?php
/**
 * Front controller BARET.
 * Semua request diarahkan ke sini (lihat .htaccess / router PHP built-in server).
 */
declare(strict_types=1);

// Untuk `php -S`: biarkan file statis (bukan PHP) dilayani langsung.
if (PHP_SAPI === 'cli-server') {
    $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($path && is_file($path) && str_starts_with($path, __DIR__) && !preg_match('/\.(php|phtml|phar)$/i', $path)) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

App\Core\App::run();
