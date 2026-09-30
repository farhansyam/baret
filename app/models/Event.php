<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Paginator;

final class Event
{
    public const WAKTU = ['akan_datang' => 'Akan Datang', 'berlangsung' => 'Berlangsung', 'lampau' => 'Selesai'];

    /** Status waktu dihitung dari tanggal mulai/selesai. */
    public static function timeStatus(array $e): string
    {
        $now = time();
        return match (true) {
            strtotime($e['mulai']) > $now => 'akan_datang',
            strtotime($e['selesai']) >= $now => 'berlangsung',
            default => 'lampau',
        };
    }

    private static function waktuSql(?string $waktu): string
    {
        return match ($waktu) {
            'akan_datang' => ' AND e.mulai > NOW()',
            'berlangsung' => ' AND e.mulai <= NOW() AND e.selesai >= NOW()',
            'lampau' => ' AND e.selesai < NOW()',
            default => '',
        };
    }

    public static function publicPaginate(?string $waktu, ?string $q, int $perPage = 9): array
    {
        $params = [];
        $where = "e.status = 'publish'" . self::waktuSql($waktu);
        if ($q) {
            $where .= ' AND (e.judul LIKE ? OR e.lokasi LIKE ?)';
            array_push($params, "%$q%", "%$q%");
        }
        $total = (int) Database::value("SELECT COUNT(*) FROM events e WHERE $where", $params);
        $pager = Paginator::fromRequest($total, $perPage);
        $order = $waktu === 'lampau' ? 'e.mulai DESC' : ($waktu ? 'e.mulai ASC' : '(e.selesai < NOW()) ASC, CASE WHEN e.selesai < NOW() THEN 0 ELSE UNIX_TIMESTAMP(e.mulai) END ASC, e.mulai DESC');
        $rows = Database::all(
            "SELECT e.*, w.nama AS wilayah_nama,
                    (SELECT COUNT(*) FROM event_photos p WHERE p.event_id = e.id) AS jumlah_foto
             FROM events e LEFT JOIN wilayah w ON w.id = e.wilayah_id
             WHERE $where ORDER BY $order" . $pager->limitSql(),
            $params
        );
        return [$rows, $pager];
    }

    public static function upcoming(int $limit = 4): array
    {
        return Database::all(
            "SELECT e.*, w.nama AS wilayah_nama FROM events e LEFT JOIN wilayah w ON w.id = e.wilayah_id
             WHERE e.status = 'publish' AND e.selesai >= NOW() ORDER BY e.mulai ASC LIMIT " . (int) $limit
        );
    }

    /** Kegiatan publik dalam satu bulan (untuk kalender). */
    public static function inMonth(int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end = date('Y-m-d 23:59:59', strtotime("$start +1 month -1 day"));
        return Database::all(
            "SELECT id, judul, slug, mulai, selesai, lokasi FROM events
             WHERE status = 'publish' AND mulai <= ? AND selesai >= ? ORDER BY mulai",
            [$end, $start]
        );
    }

    public static function findPublic(string $slug): ?array
    {
        return Database::one(
            "SELECT e.*, w.nama AS wilayah_nama FROM events e LEFT JOIN wilayah w ON w.id = e.wilayah_id
             WHERE e.status = 'publish' AND e.slug = ?",
            [$slug]
        );
    }

    public static function photos(int $eventId): array
    {
        return Database::all('SELECT * FROM event_photos WHERE event_id = ? ORDER BY id', [$eventId]);
    }
}
