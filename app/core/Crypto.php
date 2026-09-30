<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Enkripsi data sensitif (NIK) dengan AES-256-GCM memakai APP_KEY. */
final class Crypto
{
    private static function key(): string
    {
        $raw = (string) config('app.key');
        if (str_starts_with($raw, 'base64:')) {
            $raw = base64_decode(substr($raw, 7), true) ?: '';
        }
        if (strlen($raw) < 32) {
            throw new RuntimeException('APP_KEY belum diatur. Jalankan: php database/install.php');
        }
        return substr($raw, 0, 32);
    }

    public static function encrypt(string $plain): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(?string $payload): ?string
    {
        if (!$payload) {
            return null;
        }
        $bin = base64_decode($payload, true);
        if ($bin === false || strlen($bin) < 29) {
            return null;
        }
        $plain = openssl_decrypt(substr($bin, 28), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr($bin, 0, 12), substr($bin, 12, 16));
        return $plain === false ? null : $plain;
    }

    /** Hash deterministik untuk cek duplikasi tanpa menyimpan NIK polos. */
    public static function blindIndex(string $value): string
    {
        return hash_hmac('sha256', $value, self::key());
    }
}
