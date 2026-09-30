<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Setting
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            try {
                self::$cache = array_column(Database::all('SELECT `key`, `value` FROM settings'), 'value', 'key');
            } catch (\Throwable) {
                self::$cache = [];
            }
        }
        return self::$cache;
    }

    public static function get(string $key, string $default = ''): string
    {
        $v = self::all()[$key] ?? null;
        return ($v === null || $v === '') ? $default : (string) $v;
    }

    public static function set(string $key, ?string $value): void
    {
        Database::query('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', [$key, $value]);
        self::$cache = null;
    }

    /** Baris "Judul|Deskripsi" -> [[judul, deskripsi], ...] */
    public static function pairs(string $key): array
    {
        $out = [];
        foreach (self::lines($key) as $line) {
            [$a, $b] = array_pad(explode('|', $line, 2), 2, '');
            $out[] = [trim($a), trim($b)];
        }
        return $out;
    }

    public static function lines(string $key): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', self::get($key)) ?: [])));
    }
}
