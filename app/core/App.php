<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

final class App
{
    public static function run(): void
    {
        self::securityHeaders();
        Session::start();

        $router = new Router(self::basePath());
        require APP_PATH . '/routes.php';

        try {
            $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
        } catch (HttpException $e) {
            self::renderError($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            error_log((string) $e);
            if (config('app.debug')) {
                http_response_code(500);
                echo '<pre>' . e((string) $e) . '</pre>';
                return;
            }
            self::renderError(500);
        }
    }

    /** Sub-folder tempat aplikasi dipasang, mis. "/baret/public" di MAMP; kosong bila di root. */
    public static function basePath(): string
    {
        static $base = null;
        if ($base === null) {
            $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
            $base = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
            // Hosting tanpa akses document root: .htaccess di root meneruskan ke public/ secara internal,
            // sehingga URL publik tidak memuat "/public".
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            if ($base !== '' && str_ends_with($base, '/public') && !str_starts_with($uri, $base . '/') && $uri !== $base) {
                $base = substr($base, 0, -strlen('/public'));
            }
        }
        return $base;
    }

    public static function renderError(int $code, string $message = ''): void
    {
        $code = in_array($code, [403, 404, 405, 419, 429, 500], true) ? $code : 500;
        http_response_code($code);
        try {
            echo View::render('errors/error', ['code' => $code, 'message' => $message, 'title' => (string) $code], 'layouts/public');
        } catch (Throwable $e) {
            // Mis. database tidak dapat dihubungi: tampilkan halaman minimal
            error_log((string) $e);
            echo '<!doctype html><html lang="id"><meta charset="utf-8"><title>' . $code . ' | BARET</title>'
                . '<body style="font-family:system-ui;background:#0D0D0D;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0">'
                . '<div style="text-align:center"><h1 style="font-size:64px;margin:0;color:#D9141C">' . $code . '</h1>'
                . '<p>Layanan sedang mengalami gangguan. Silakan coba beberapa saat lagi.</p></div></body></html>';
        }
    }

    private static function securityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://*.tile.openstreetmap.org; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; "
            . "script-src 'self'; frame-src https://www.openstreetmap.org; connect-src 'self'; "
            . "form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
        if (is_https()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
