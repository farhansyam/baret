<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Crypto;
use App\Core\Database;
use App\Core\Paginator;

final class Member
{
    public const STATUS = ['menunggu' => 'Menunggu', 'aktif' => 'Aktif', 'ditolak' => 'Ditolak', 'nonaktif' => 'Nonaktif'];
    /** Transisi status yang diizinkan. */
    public const TRANSITIONS = [
        'menunggu' => ['aktif', 'ditolak'],
        'ditolak'  => ['menunggu'],
        'aktif'    => ['nonaktif'],
        'nonaktif' => ['aktif'],
    ];

    public const FIELDS = ['nama', 'tempat_lahir', 'tgl_lahir', 'jk', 'pekerjaan', 'telepon', 'email', 'alamat', 'wilayah_id'];

    public static function rules(?int $exceptId = null): array
    {
        return [
            'nama' => 'required|min:3|max:120',
            'nik' => 'required|digits:16',
            'tempat_lahir' => 'required|max:80',
            'tgl_lahir' => 'required|date|before:today',
            'jk' => 'required|in:L,P',
            'pekerjaan' => 'nullable|max:80',
            'telepon' => 'required|phone',
            'email' => 'nullable|email',
            'alamat' => 'required|min:10|max:500',
            'wilayah_id' => 'required|integer|exists:wilayah,id',
        ];
    }

    public const LABELS = [
        'nama' => 'Nama lengkap', 'nik' => 'NIK', 'tempat_lahir' => 'Tempat lahir', 'tgl_lahir' => 'Tanggal lahir',
        'jk' => 'Jenis kelamin', 'pekerjaan' => 'Pekerjaan', 'telepon' => 'Nomor HP/WhatsApp', 'email' => 'Email',
        'alamat' => 'Alamat', 'wilayah_id' => 'Wilayah/Cabang', 'foto' => 'Pas foto', 'ktp' => 'Foto KTP', 'persetujuan' => 'Persetujuan penggunaan data',
    ];

    public static function nikTaken(string $nik, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM members WHERE nik_hash = ?';
        $params = [Crypto::blindIndex($nik)];
        if ($exceptId) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (int) Database::value($sql, $params) > 0;
    }

    public static function newRegistrationNumber(): string
    {
        do {
            $no = 'REG-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        } while (Database::value('SELECT 1 FROM members WHERE no_registrasi = ?', [$no]));
        return $no;
    }

    public static function create(array $data, string $nik, ?int $byUser = null): int
    {
        return Database::transaction(function () use ($data, $nik, $byUser) {
            $row = array_intersect_key($data, array_flip(array_merge(self::FIELDS, ['foto', 'ktp_file', 'consent_at'])));
            $row['no_registrasi'] = self::newRegistrationNumber();
            $row['nik_enc'] = Crypto::encrypt($nik);
            $row['nik_hash'] = Crypto::blindIndex($nik);
            $row['status'] = 'menunggu';
            $row['created_at'] = now();
            $id = Database::insert('members', $row);
            Database::insert('member_status_history', [
                'member_id' => $id, 'status_lama' => null, 'status_baru' => 'menunggu',
                'catatan' => $byUser ? 'Diinput oleh admin' : 'Pendaftaran online', 'oleh' => $byUser, 'waktu' => now(),
            ]);
            ActivityLog::record('create', 'members', $id, null, array_diff_key($row, ['nik_enc' => 1, 'nik_hash' => 1]));
            return $id;
        });
    }

    public static function find(int $id, bool $withTrashed = false): ?array
    {
        $params = [$id];
        $scope = Auth::scopeSql('m.wilayah_id', $params);
        $row = Database::one(
            'SELECT m.*, w.nama AS wilayah_nama, w.kode AS wilayah_kode, v.nama AS verifier_nama
             FROM members m JOIN wilayah w ON w.id = m.wilayah_id
             LEFT JOIN users v ON v.id = m.verified_by
             WHERE m.id = ?' . $scope . ($withTrashed ? '' : ' AND m.deleted_at IS NULL'),
            $params
        );
        if ($row) {
            $row['nik'] = Crypto::decrypt($row['nik_enc']);
        }
        return $row;
    }

    /** Filter: q, status, wilayah_id, arsip */
    private static function where(array $f, array &$params): string
    {
        $where = !empty($f['arsip']) ? 'm.deleted_at IS NOT NULL' : 'm.deleted_at IS NULL';
        $where .= Auth::scopeSql('m.wilayah_id', $params);
        if (!empty($f['q'])) {
            $where .= ' AND (m.nama LIKE ? OR m.nia LIKE ? OR m.no_registrasi LIKE ? OR m.telepon LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($f['status']) && isset(self::STATUS[$f['status']])) {
            $where .= ' AND m.status = ?';
            $params[] = $f['status'];
        }
        if (!empty($f['wilayah_id'])) {
            $ids = Wilayah::descendantIds((int) $f['wilayah_id']);
            $where .= ' AND m.wilayah_id IN (' . Database::in($ids) . ')';
            array_push($params, ...$ids);
        }
        return $where;
    }

    public static function paginate(array $f): array
    {
        $params = [];
        $where = self::where($f, $params);
        $total = (int) Database::value("SELECT COUNT(*) FROM members m WHERE $where", $params);
        $pager = Paginator::fromRequest($total);
        $rows = Database::all(
            "SELECT m.id, m.no_registrasi, m.nia, m.nama, m.jk, m.telepon, m.status, m.foto, m.created_at, m.deleted_at,
                    w.nama AS wilayah_nama
             FROM members m JOIN wilayah w ON w.id = m.wilayah_id
             WHERE $where ORDER BY m.created_at DESC, m.id DESC" . $pager->limitSql(),
            $params
        );
        return [$rows, $pager];
    }

    public static function forExport(array $f): array
    {
        $params = [];
        $where = self::where($f, $params);
        return Database::all(
            "SELECT m.no_registrasi, m.nia, m.nama, m.jk, m.tempat_lahir, m.tgl_lahir, m.pekerjaan, m.telepon, m.email,
                    w.kode AS wilayah_kode, w.nama AS wilayah_nama, m.status, m.created_at, m.verified_at
             FROM members m JOIN wilayah w ON w.id = m.wilayah_id WHERE $where ORDER BY m.nama",
            $params
        );
    }

    public static function history(int $id): array
    {
        return Database::all(
            'SELECT h.*, u.nama AS oleh_nama FROM member_status_history h LEFT JOIN users u ON u.id = h.oleh
             WHERE h.member_id = ? ORDER BY h.waktu DESC, h.id DESC',
            [$id]
        );
    }

    /**
     * Ubah status + catat riwayat. NIA dibuat otomatis saat pertama kali disetujui.
     * @return string|null pesan error
     */
    public static function changeStatus(array $member, string $to, ?string $catatan): ?string
    {
        $from = $member['status'];
        if (!in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            return 'Perubahan status dari "' . self::STATUS[$from] . '" ke "' . (self::STATUS[$to] ?? $to) . '" tidak diizinkan.';
        }
        Database::transaction(function () use ($member, $from, $to, $catatan) {
            $data = ['status' => $to];
            if ($to === 'aktif' && !$member['nia']) {
                $data['nia'] = self::generateNia($member['wilayah_kode']);
                $data['verified_by'] = Auth::id();
                $data['verified_at'] = now();
            }
            if (in_array($to, ['aktif', 'ditolak'], true)) {
                $data['catatan_verifikasi'] = $catatan ?: null;
            }
            Database::update('members', $data, 'id = ?', [$member['id']]);
            Database::insert('member_status_history', [
                'member_id' => $member['id'], 'status_lama' => $from, 'status_baru' => $to,
                'catatan' => $catatan ?: null, 'oleh' => Auth::id(), 'waktu' => now(),
            ]);
            ActivityLog::record('status', 'members', $member['id'], ['status' => $from, 'nia' => $member['nia']], ['status' => $to, 'nia' => $data['nia'] ?? $member['nia']]);
        });
        return null;
    }

    /** BRT-{KODE_WILAYAH}-{TAHUN}-{URUT 5 DIGIT}, dikunci agar unik walau disetujui bersamaan. */
    public static function generateNia(string $kode): string
    {
        $tahun = (int) date('Y');
        Database::query('INSERT IGNORE INTO nia_sequences (kode, tahun, last_no) VALUES (?, ?, 0)', [$kode, $tahun]);
        $last = (int) Database::value('SELECT last_no FROM nia_sequences WHERE kode = ? AND tahun = ? FOR UPDATE', [$kode, $tahun]);
        $next = $last + 1;
        Database::update('nia_sequences', ['last_no' => $next], 'kode = ? AND tahun = ?', [$kode, $tahun]);
        return sprintf('BRT-%s-%d-%05d', $kode, $tahun, $next);
    }

    public static function countVerified(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM members WHERE status = 'aktif' AND deleted_at IS NULL");
    }

    /** Data untuk halaman verifikasi publik (tanpa data pribadi). */
    public static function publicLookup(string $nia): ?array
    {
        return Database::one(
            "SELECT m.nia, m.nama, m.status, m.verified_at, w.nama AS wilayah_nama
             FROM members m JOIN wilayah w ON w.id = m.wilayah_id
             WHERE m.nia = ? AND m.deleted_at IS NULL AND m.status IN ('aktif','nonaktif')",
            [$nia]
        );
    }

    /** Cek status pendaftaran: wajib cocok nomor registrasi + tanggal lahir (mencegah penebakan). */
    public static function registrationLookup(string $no, string $tglLahir): ?array
    {
        return Database::one(
            'SELECT m.no_registrasi, m.nama, m.status, m.nia, m.created_at, m.catatan_verifikasi, w.nama AS wilayah_nama
             FROM members m JOIN wilayah w ON w.id = m.wilayah_id
             WHERE m.no_registrasi = ? AND m.tgl_lahir = ? AND m.deleted_at IS NULL',
            [$no, $tglLahir]
        );
    }
}
