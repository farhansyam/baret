<?php
declare(strict_types=1);

namespace App\Core;

/** Pembatas laju berbasis tabel `throttle` (login, form publik). */
final class Throttle
{
    /** Detik tersisa bila kunci sedang terkunci, 0 bila tidak. */
    public static function lockedFor(string $key): int
    {
        $row = Database::one('SELECT locked_until FROM throttle WHERE `key` = ?', [self::k($key)]);
        if ($row && $row['locked_until'] && strtotime($row['locked_until']) > time()) {
            return strtotime($row['locked_until']) - time();
        }
        return 0;
    }

    /** Catat satu percobaan; kunci selama $lockSeconds bila mencapai $max dalam jendela $lockSeconds. */
    public static function hit(string $key, int $max, int $lockSeconds): int
    {
        $k = self::k($key);
        $row = Database::one('SELECT hits, first_at FROM throttle WHERE `key` = ?', [$k]);
        if (!$row || strtotime($row['first_at']) < time() - $lockSeconds) {
            Database::query('REPLACE INTO throttle (`key`, hits, first_at, locked_until) VALUES (?, 1, ?, NULL)', [$k, now()]);
            $hits = 1;
        } else {
            $hits = (int) $row['hits'] + 1;
            Database::update('throttle', ['hits' => $hits], '`key` = ?', [$k]);
        }
        if ($hits >= $max) {
            Database::update('throttle', ['locked_until' => date('Y-m-d H:i:s', time() + $lockSeconds)], '`key` = ?', [$k]);
        }
        return $hits;
    }

    /** Untuk form publik: true bila masih di bawah batas per jam. */
    public static function allow(string $key, int $maxPerHour): bool
    {
        if (self::lockedFor($key)) {
            return false;
        }
        self::hit($key, $maxPerHour, 3600);
        return true;
    }

    public static function clear(string $key): void
    {
        Database::query('DELETE FROM throttle WHERE `key` = ?', [self::k($key)]);
    }

    private static function k(string $key): string
    {
        return hash('sha256', $key);
    }
}
