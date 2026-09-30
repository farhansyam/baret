<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('BARETSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => (App::basePath() ?: '') . '/',
            'secure'   => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();

        // Batas idle sesi login
        $idle = (int) config('security.session_idle', 7200);
        if (isset($_SESSION['_last']) && time() - $_SESSION['_last'] > $idle && isset($_SESSION['user_id'])) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['_flash']['warning'] = 'Sesi berakhir karena tidak aktif. Silakan masuk kembali.';
        }
        $_SESSION['_last'] = time();

        // Pindahkan flash dari request sebelumnya agar hanya tampil sekali
        $_SESSION['_flash_now'] = $_SESSION['_flash'] ?? [];
        $_SESSION['_old_now'] = $_SESSION['_old'] ?? [];
        $_SESSION['_errors_now'] = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_flash'], $_SESSION['_old'], $_SESSION['_errors']);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type] = $message;
    }

    public static function withInput(array $old, array $errors = []): void
    {
        $_SESSION['_old'] = $old;
        $_SESSION['_errors'] = $errors;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
