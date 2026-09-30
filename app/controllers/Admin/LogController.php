<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Database;
use App\Core\Paginator;

final class LogController extends Controller
{
    public const TABLES = [
        'members' => 'Anggota', 'wilayah' => 'Wilayah', 'pengurus' => 'Pengurus', 'news' => 'Berita', 'news_categories' => 'Kategori',
        'events' => 'Kegiatan', 'event_photos' => 'Foto kegiatan', 'aspirasi' => 'Aspirasi', 'users' => 'Pengguna', 'settings' => 'Pengaturan',
    ];

    public function index(): void
    {
        $params = [];
        $where = '1=1';
        $tabel = (string) query('tabel', '');
        if (isset(self::TABLES[$tabel])) {
            $where .= ' AND l.tabel = ?';
            $params[] = $tabel;
        }
        $aksi = (string) query('aksi', '');
        if ($aksi !== '' && preg_match('/^[a-z_]{2,40}$/', $aksi)) {
            $where .= ' AND l.aksi = ?';
            $params[] = $aksi;
        }
        $userId = (int) query('user_id', 0);
        if ($userId) {
            $where .= ' AND l.user_id = ?';
            $params[] = $userId;
        }
        $dari = (string) query('dari', '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) {
            $where .= ' AND l.waktu >= ?';
            $params[] = $dari . ' 00:00:00';
        }
        $sampai = (string) query('sampai', '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) {
            $where .= ' AND l.waktu <= ?';
            $params[] = $sampai . ' 23:59:59';
        }
        $total = (int) Database::value("SELECT COUNT(*) FROM activity_logs l WHERE $where", $params);
        $pager = Paginator::fromRequest($total, 25);
        $items = Database::all(
            "SELECT l.*, u.nama AS user_nama FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id
             WHERE $where ORDER BY l.waktu DESC, l.id DESC" . $pager->limitSql(),
            $params
        );
        $this->admin('log/index', [
            'title' => 'Log Aktivitas',
            'items' => $items, 'pager' => $pager,
            'f' => ['tabel' => $tabel, 'aksi' => $aksi, 'user_id' => $userId, 'dari' => $dari, 'sampai' => $sampai],
            'users' => Database::all('SELECT id, nama FROM users ORDER BY nama'),
            'aksiList' => Database::all('SELECT DISTINCT aksi FROM activity_logs ORDER BY aksi'),
        ]);
    }
}
