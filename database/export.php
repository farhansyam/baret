<?php
/**
 * Ekspor database menjadi satu file SQL (skema + data) yang bisa diimpor lewat phpMyAdmin.
 *   php database/export.php [file-tujuan.sql]
 * Catatan: NIK anggota terenkripsi dengan APP_KEY — .env di hosting harus memakai APP_KEY yang sama.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$target = $argv[1] ?? __DIR__ . '/baret_demo.sql';
$tables = ['roles', 'wilayah', 'users', 'members', 'member_status_history', 'nia_sequences', 'pengurus', 'news_categories',
    'news', 'events', 'event_photos', 'aspirasi', 'activity_logs', 'settings', 'password_resets'];

$pdo = Database::pdo();
$sql = "-- BARET — ekspor database " . date('Y-m-d H:i') . "\n-- Impor ke database kosong (phpMyAdmin > Import).\n\n"
    . "SET NAMES utf8mb4;\nSET time_zone = '+07:00';\n\n"
    . preg_replace('/^--.*\n/m', '', file_get_contents(__DIR__ . '/schema.sql'))
    . "\nSET FOREIGN_KEY_CHECKS = 0;\n";

foreach ($tables as $t) {
    $rows = Database::all("SELECT * FROM `$t`" . ($t === 'wilayah' ? ' ORDER BY (parent_id IS NOT NULL), id' : ''));
    if (!$rows) {
        continue;
    }
    $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
    $sql .= "\n-- $t (" . count($rows) . " baris)\n";
    foreach (array_chunk($rows, 100) as $chunk) {
        $values = array_map(function (array $r) use ($pdo) {
            return '(' . implode(', ', array_map(fn($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : $pdo->quote((string) $v)), $r)) . ')';
        }, $chunk);
        $sql .= "INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $values) . ";\n";
    }
}
$sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents($target, $sql);
echo "✓ Diekspor ke $target (" . round(strlen($sql) / 1024) . " KB)\n";
