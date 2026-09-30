<?php
declare(strict_types=1);

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\HttpException;
use App\Models\Setting;

function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['__config'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** Escape output HTML (anti-XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return App::basePath() . '/' . ltrim($path, '/');
}

function absolute_url(string $path = ''): string
{
    $base = config('app.url');
    if (!$base) {
        $base = (is_https() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . App::basePath();
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = PUBLIC_PATH . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url('assets/' . ltrim($path, '/')) . $v;
}

function upload_url(string $area, ?string $name): ?string
{
    return $name ? url("uploads/$area/$name") : null;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function redirect(string $path, int $code = 302): never
{
    $target = preg_match('#^https?://#', $path) ? $path : url($path);
    header('Location: ' . $target, true, $code);
    exit;
}

/** Kembali ke halaman sebelumnya (hanya URL internal) atau $fallback. */
function back(string $fallback = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && parse_url($ref, PHP_URL_HOST) === $host) {
        header('Location: ' . $ref, true, 302);
        exit;
    }
    redirect($fallback);
}

function abort(int $code, string $message = ''): never
{
    throw new HttpException($message, $code);
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][$type] = $message;
}

function flashes(): array
{
    return $_SESSION['_flash_now'] ?? [];
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old_now'][$key] ?? $default;
}

function error(string $key): ?string
{
    return $_SESSION['_errors_now'][$key] ?? null;
}

function errors(): array
{
    return $_SESSION['_errors_now'] ?? [];
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function input(string $key, mixed $default = null): mixed
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function query(string $key, mixed $default = null): mixed
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function auth(): ?array
{
    return Auth::user();
}

function can(string $perm): bool
{
    return Auth::can($perm);
}

function setting(string $key, string $default = ''): string
{
    return Setting::get($key, $default);
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = strtr($text, ['&' => ' dan ']);
    $text = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string) $text, '-') ?: 'item';
}

function str_limit(?string $text, int $limit = 120): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));
    return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit)) . '…' : $text;
}

function bulan(int $m, bool $short = false): string
{
    $full = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $short ? mb_substr($full[$m], 0, 3) : $full[$m];
}

function tanggal(?string $date, bool $withTime = false, bool $short = false): string
{
    if (!$date) {
        return '—';
    }
    $t = strtotime($date);
    $s = date('j', $t) . ' ' . bulan((int) date('n', $t), $short) . ' ' . date('Y', $t);
    return $withTime ? $s . ', ' . date('H.i', $t) . ' WIB' : $s;
}

function hari(?string $date): string
{
    $d = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return $date ? $d[(int) date('w', strtotime($date))] : '';
}

function time_ago(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $diff = time() - strtotime($date);
    return match (true) {
        $diff < 60 => 'baru saja',
        $diff < 3600 => floor($diff / 60) . ' menit lalu',
        $diff < 86400 => floor($diff / 3600) . ' jam lalu',
        $diff < 604800 => floor($diff / 86400) . ' hari lalu',
        default => tanggal($date, false, true),
    };
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $i = mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1);
    return mb_strtoupper($i ?: '?');
}

function number(int|float|string|null $n): string
{
    return number_format((float) $n, 0, ',', '.');
}

/** Nilai untuk input datetime-local. */
function dt_local(?string $dt): string
{
    return $dt ? date('Y-m-d\TH:i', strtotime($dt)) : '';
}

function nav_active(string $prefix, bool $exact = false): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = App::basePath();
    if ($base && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base)) ?: '/';
    }
    $path = rtrim($path, '/') ?: '/';
    $active = $exact ? $path === $prefix : ($path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/'));
    return $active ? ' is-active' : '';
}

function partial(string $name, array $data = []): string
{
    return \App\Core\View::capture('partials/' . $name, $data);
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function checked(bool $cond): string
{
    return $cond ? ' checked' : '';
}

/** Ikon Lucide (inline SVG, satu set konsisten). */
function icon(string $name, string $class = ''): string
{
    static $icons = null;
    $icons ??= require APP_PATH . '/views/partials/icons.php';
    $body = $icons[$name] ?? $icons['circle'];
    return '<svg class="icon ' . e($class) . '" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
}

function status_badge(string $status): string
{
    $map = [
        'menunggu' => ['warning', 'Menunggu'], 'aktif' => ['success', 'Aktif'], 'ditolak' => ['danger', 'Ditolak'],
        'nonaktif' => ['muted', 'Nonaktif'], 'draft' => ['muted', 'Draft'], 'publish' => ['success', 'Publish'],
        'baru' => ['info', 'Baru'], 'diproses' => ['warning', 'Diproses'], 'selesai' => ['success', 'Selesai'],
        'akan_datang' => ['info', 'Akan Datang'], 'berlangsung' => ['red', 'Berlangsung'], 'lampau' => ['muted', 'Selesai'],
    ];
    [$tone, $label] = $map[$status] ?? ['muted', ucfirst($status)];
    return '<span class="badge badge--' . $tone . '">' . e($label) . '</span>';
}

function mask_nik(?string $nik): string
{
    return $nik ? substr($nik, 0, 4) . str_repeat('•', 8) . substr($nik, -4) : '—';
}

function csv_safe(mixed $v): string
{
    $v = (string) ($v ?? '');
    return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
}
