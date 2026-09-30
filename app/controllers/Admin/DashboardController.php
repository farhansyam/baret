<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\Event;

final class DashboardController extends Controller
{
    public function index(): void
    {
        if (!Auth::can('dashboard.view')) {
            $this->memberHome();
            return;
        }

        $p = [];
        $scope = Auth::scopeSql('wilayah_id', $p);
        $counts = Database::one(
            "SELECT SUM(status = 'aktif') AS aktif, SUM(status = 'menunggu') AS menunggu, COUNT(*) AS total
             FROM members WHERE deleted_at IS NULL $scope",
            $p
        );

        $pw = [];
        $scopeW = Auth::scopeSql('id', $pw);
        $cabang = (int) Database::value("SELECT COUNT(*) FROM wilayah WHERE tingkat IN ('cabang','ranting') $scopeW", $pw);

        $pe = [];
        $scopeE = Auth::scopeSql('wilayah_id', $pe);
        $kegiatan = (int) Database::value("SELECT COUNT(*) FROM events WHERE selesai >= NOW() $scopeE", $pe);

        $aspirasi = Auth::can('aspirasi.view')
            ? (int) Database::value("SELECT COUNT(*) FROM aspirasi WHERE status IN ('baru','diproses') AND archived_at IS NULL")
            : null;

        // Pendaftaran per bulan, 12 bulan terakhir
        $pc = [date('Y-m-01', strtotime('first day of -11 months'))];
        $scopeC = Auth::scopeSql('wilayah_id', $pc);
        $rows = Database::all(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS n FROM members
             WHERE created_at >= ? AND deleted_at IS NULL $scopeC GROUP BY ym",
            $pc
        );
        $byMonth = array_column($rows, 'n', 'ym');
        $chart = [];
        for ($i = 11; $i >= 0; $i--) {
            $t = strtotime("first day of -$i months");
            $ym = date('Y-m', $t);
            $chart[] = ['label' => bulan((int) date('n', $t), true), 'full' => bulan((int) date('n', $t)) . ' ' . date('Y', $t), 'value' => (int) ($byMonth[$ym] ?? 0)];
        }

        $pp = [];
        $scopeP = Auth::scopeSql('m.wilayah_id', $pp);
        $pending = Database::all(
            "SELECT m.id, m.nama, m.no_registrasi, m.created_at, w.nama AS wilayah_nama FROM members m
             JOIN wilayah w ON w.id = m.wilayah_id
             WHERE m.status = 'menunggu' AND m.deleted_at IS NULL $scopeP ORDER BY m.created_at ASC LIMIT 5",
            $pp
        );

        $pu = [];
        $scopeU = Auth::scopeSql('e.wilayah_id', $pu);
        $upcoming = Database::all(
            "SELECT e.id, e.judul, e.mulai, e.selesai, e.lokasi, e.status FROM events e
             WHERE e.selesai >= NOW() $scopeU ORDER BY e.mulai ASC LIMIT 5",
            $pu
        );

        $this->admin('dashboard', [
            'title' => 'Dashboard',
            'stats' => [
                'aktif' => (int) ($counts['aktif'] ?? 0),
                'menunggu' => (int) ($counts['menunggu'] ?? 0),
                'cabang' => $cabang,
                'kegiatan' => $kegiatan,
                'aspirasi' => $aspirasi,
            ],
            'chart' => $chart,
            'pending' => $pending,
            'upcoming' => $upcoming,
        ]);
    }

    /** Beranda untuk peran Anggota: profil keanggotaan sendiri + agenda. */
    private function memberHome(): void
    {
        $member = Database::one(
            'SELECT m.nia, m.no_registrasi, m.nama, m.status, m.telepon, m.email, m.verified_at, w.nama AS wilayah_nama
             FROM members m JOIN wilayah w ON w.id = m.wilayah_id WHERE m.user_id = ? AND m.deleted_at IS NULL',
            [Auth::id()]
        );
        $this->admin('member-home', [
            'title' => 'Beranda Anggota',
            'member' => $member,
            'events' => Event::upcoming(5),
        ]);
    }
}
