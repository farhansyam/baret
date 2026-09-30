<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Wilayah;

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    /** @return true|string true bila berhasil, pesan error bila gagal. */
    public static function attempt(string $email, string $password): bool|string
    {
        $email = mb_strtolower(trim($email));
        $key = 'login:' . $email . '|' . client_ip();
        $max = (int) config('security.login_max_attempts', 5);
        $lock = (int) config('security.login_lock_minutes', 15);

        if ($wait = Throttle::lockedFor($key)) {
            return 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ceil($wait / 60) . ' menit.';
        }

        $user = Database::one('SELECT id, password_hash, aktif FROM users WHERE email = ?', [$email]);
        // Tetap jalankan password_verify walau email tidak ada, agar waktu respons seragam.
        $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
        $ok = password_verify($password, $hash);

        if (!$user || !$ok) {
            $hits = Throttle::hit($key, $max, $lock * 60);
            ActivityLog::record('login_gagal', 'users', $user['id'] ?? null, null, ['email' => $email]);
            $sisa = $max - $hits;
            return $sisa > 0
                ? "Email atau kata sandi salah. Sisa percobaan: $sisa."
                : "Akun dikunci sementara selama $lock menit karena terlalu banyak percobaan gagal.";
        }
        if (!(int) $user['aktif']) {
            return 'Akun Anda dinonaktifkan. Hubungi administrator.';
        }

        Throttle::clear($key);
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Database::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$user['id']]);
        }
        Session::regenerate();
        Csrf::rotate();
        $_SESSION['user_id'] = (int) $user['id'];
        Database::update('users', ['last_login' => now()], 'id = ?', [$user['id']]);
        self::$loaded = false;
        ActivityLog::record('login', 'users', $user['id']);
        return true;
    }

    public static function logout(): void
    {
        if (self::check()) {
            ActivityLog::record('logout', 'users', self::id());
        }
        Session::destroy();
        Csrf::rotate();
        self::$user = null;
        self::$loaded = true;
    }

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = $_SESSION['user_id'] ?? null;
            if ($id) {
                $u = Database::one(
                    'SELECT u.id, u.nama, u.email, u.role_id, u.wilayah_id, u.aktif, u.last_login,
                            r.slug AS role, r.nama AS role_nama, w.nama AS wilayah_nama
                     FROM users u JOIN roles r ON r.id = u.role_id
                     LEFT JOIN wilayah w ON w.id = u.wilayah_id WHERE u.id = ?',
                    [$id]
                );
                if ($u && (int) $u['aktif']) {
                    self::$user = $u;
                } else {
                    Session::destroy();
                }
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::user() ? (int) self::user()['id'] : null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function can(string $perm): bool
    {
        $role = self::role();
        if (!$role) {
            return false;
        }
        $map = self::permissionMap()[$role] ?? ['perms' => []];
        return in_array('*', $map['perms'], true) || in_array($perm, $map['perms'], true);
    }

    public static function authorize(string $perm): void
    {
        if (!self::can($perm)) {
            throw new HttpException('Anda tidak memiliki hak akses untuk halaman ini.', 403);
        }
    }

    public static function isScoped(): bool
    {
        $role = self::role();
        return $role !== null && (self::permissionMap()[$role]['scoped'] ?? false);
    }

    /**
     * ID wilayah yang boleh diakses (wilayah pengguna + seluruh turunannya),
     * atau null bila tidak dibatasi.
     */
    public static function scopeIds(): ?array
    {
        if (!self::isScoped()) {
            return null;
        }
        $wid = (int) (self::user()['wilayah_id'] ?? 0);
        return $wid ? Wilayah::descendantIds($wid) : [0];
    }

    public static function inScope(?int $wilayahId): bool
    {
        $ids = self::scopeIds();
        return $ids === null || ($wilayahId !== null && in_array($wilayahId, $ids, true));
    }

    /** Menambah klausa "AND kolom IN (...)" ke query bila pengguna dibatasi wilayah. */
    public static function scopeSql(string $column, array &$params): string
    {
        $ids = self::scopeIds();
        if ($ids === null) {
            return '';
        }
        array_push($params, ...$ids);
        return " AND $column IN (" . Database::in($ids) . ')';
    }

    public static function permissionMap(): array
    {
        static $map = null;
        return $map ??= require APP_PATH . '/config/permissions.php';
    }
}
