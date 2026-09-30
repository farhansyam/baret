<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Autoloader sederhana: App\Core\Foo -> app/core/Foo.php, App\Controllers\Admin\Foo -> app/controllers/Admin/Foo.php
spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $parts = explode('\\', substr($class, 4));
    $parts[0] = strtolower($parts[0]);
    $file = APP_PATH . '/' . implode('/', $parts) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_PATH . '/core/helpers.php';

App\Core\Env::load(APP_PATH . '/config/.env');
$GLOBALS['__config'] = require APP_PATH . '/config/config.php';

date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));
mb_internal_encoding('UTF-8');

$debug = (bool) config('app.debug');
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
