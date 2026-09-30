<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validasi & penyimpanan unggahan:
 * - cek kode error, ukuran, MIME asli (finfo), ekstensi whitelist
 * - gambar di-encode ulang dengan GD menjadi WebP (membuang metadata & payload tersembunyi)
 * - nama file acak
 */
final class Upload
{
    private const IMAGE_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** Folder tujuan: 'news', 'events', 'docs' (publik) atau 'private/xxx' (di storage). */
    public static function dir(string $area): string
    {
        return str_starts_with($area, 'private/')
            ? STORAGE_PATH . '/' . $area
            : PUBLIC_PATH . '/uploads/' . $area;
    }

    public static function has(string $field): bool
    {
        return isset($_FILES[$field]) && is_array($_FILES[$field]) && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /** @return array{0: ?string, 1: ?string} [namaFile, pesanError] */
    public static function image(array $file, string $area, ?int $maxWidth = null): array
    {
        if ($err = self::basicCheck($file, (int) config('upload.max_image'))) {
            return [null, $err];
        }
        $mime = self::mime($file['tmp_name']);
        if (!isset(self::IMAGE_MIMES[$mime]) || !@getimagesize($file['tmp_name'])) {
            return [null, 'File harus berupa gambar JPG, PNG, atau WebP.'];
        }
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png' => @imagecreatefrompng($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        };
        if (!$src) {
            return [null, 'Gambar rusak atau tidak dapat dibaca.'];
        }
        if ($mime === 'image/jpeg') {
            $src = self::fixOrientation($src, $file['tmp_name']);
        }
        $maxWidth ??= (int) config('upload.image_max_width', 1600);
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w > $maxWidth) {
            $nh = (int) round($h * $maxWidth / $w);
            $dst = imagecreatetruecolor($maxWidth, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxWidth, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        } else {
            imagepalettetotruecolor($src);
            imagealphablending($src, false);
            imagesavealpha($src, true);
        }
        $name = bin2hex(random_bytes(16)) . '.webp';
        $dir = self::dir($area);
        self::ensureDir($dir);
        $ok = imagewebp($src, $dir . '/' . $name, 82);
        imagedestroy($src);
        return $ok ? [$name, null] : [null, 'Gagal menyimpan gambar.'];
    }

    /** Dokumen PDF (atau gambar bila $allowImages). */
    public static function document(array $file, string $area, bool $allowImages = false): array
    {
        if ($err = self::basicCheck($file, (int) config('upload.max_doc'))) {
            return [null, $err];
        }
        $mime = self::mime($file['tmp_name']);
        if ($allowImages && isset(self::IMAGE_MIMES[$mime])) {
            return self::image($file, $area);
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 5);
        if ($mime !== 'application/pdf' || $ext !== 'pdf' || $head !== '%PDF-') {
            return [null, $allowImages ? 'File harus berupa PDF, JPG, PNG, atau WebP.' : 'File harus berupa PDF.'];
        }
        $name = bin2hex(random_bytes(16)) . '.pdf';
        $dir = self::dir($area);
        self::ensureDir($dir);
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            return [null, 'Gagal menyimpan file.'];
        }
        return [$name, null];
    }

    public static function delete(string $area, ?string $name): void
    {
        if ($name && self::validName($name)) {
            $path = self::dir($area) . '/' . $name;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public static function validName(string $name): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}\.(webp|pdf)$/', $name);
    }

    private static function basicCheck(array $file, int $max): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return 'Ukuran file melebihi batas ' . self::human($max) . '.';
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            return 'Unggahan gagal. Silakan coba lagi.';
        }
        if ($file['size'] > $max) {
            return 'Ukuran file melebihi batas ' . self::human($max) . '.';
        }
        return null;
    }

    private static function mime(string $path): string
    {
        return (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path);
    }

    private static function fixOrientation(\GdImage $img, string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($path);
        $rot = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180, 6 => -90, 8 => 90, default => 0,
        };
        return $rot ? (imagerotate($img, $rot, 0) ?: $img) : $img;
    }

    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    public static function human(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : round($bytes / 1024) . ' KB';
    }

    /** Menormalkan $_FILES['x'] multi-file menjadi daftar file. */
    public static function many(string $field): array
    {
        $f = $_FILES[$field] ?? null;
        if (!$f || !is_array($f['name'])) {
            return [];
        }
        $out = [];
        foreach ($f['name'] as $i => $n) {
            if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        }
        return $out;
    }
}
