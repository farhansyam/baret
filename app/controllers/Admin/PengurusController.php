<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Wilayah;

final class PengurusController extends Controller
{
    private const LABELS = [
        'member_id' => 'Anggota', 'wilayah_id' => 'Wilayah', 'jabatan' => 'Jabatan', 'urutan' => 'Urutan tampil',
        'periode_mulai' => 'Periode mulai', 'periode_selesai' => 'Periode selesai', 'sk_nomor' => 'Nomor SK', 'sk_file' => 'File SK',
    ];

    public function index(): void
    {
        $params = [];
        $where = '1=1' . Auth::scopeSql('p.wilayah_id', $params);
        $wid = (int) query('wilayah_id', 0);
        if ($wid && Auth::inScope($wid)) {
            $where .= ' AND p.wilayah_id = ?';
            $params[] = $wid;
        }
        $periode = (string) query('periode', 'aktif');
        if ($periode === 'aktif') {
            $where .= ' AND (p.periode_selesai IS NULL OR p.periode_selesai >= CURDATE())';
        } elseif ($periode === 'selesai') {
            $where .= ' AND p.periode_selesai < CURDATE()';
        }
        if ($q = mb_substr((string) query('q', ''), 0, 100)) {
            $where .= ' AND (m.nama LIKE ? OR p.jabatan LIKE ?)';
            array_push($params, "%$q%", "%$q%");
        }
        $total = (int) Database::value("SELECT COUNT(*) FROM pengurus p JOIN members m ON m.id = p.member_id WHERE $where", $params);
        $pager = Paginator::fromRequest($total);
        $items = Database::all(
            "SELECT p.*, m.nama, m.nia, w.nama AS wilayah_nama, w.tingkat FROM pengurus p
             JOIN members m ON m.id = p.member_id JOIN wilayah w ON w.id = p.wilayah_id
             WHERE $where ORDER BY FIELD(w.tingkat,'pusat','wilayah','cabang','ranting'), w.nama, p.urutan, p.id" . $pager->limitSql(),
            $params
        );
        $this->admin('pengurus/index', [
            'title' => 'Pengurus',
            'items' => $items, 'pager' => $pager,
            'f' => ['wilayah_id' => $wid, 'periode' => $periode, 'q' => $q],
            'wilayahOptions' => Wilayah::options(Auth::scopeIds()),
        ]);
    }

    public function create(): void
    {
        $this->admin('pengurus/form', ['title' => 'Tambah Pengurus', 'p' => null] + $this->formData());
    }

    public function store(): void
    {
        $data = $this->data();
        $this->check($data, '/admin/pengurus/tambah');
        if (Upload::has('sk_file')) {
            [$file, $err] = Upload::document($_FILES['sk_file'], 'private/sk');
            if ($err) {
                $this->failWith(['sk_file' => $err], $data, '/admin/pengurus/tambah');
            }
            $data['sk_file'] = $file;
        }
        $id = Database::insert('pengurus', $data + ['created_at' => now()]);
        ActivityLog::record('create', 'pengurus', $id, null, $data);
        flash('success', 'Pengurus berhasil ditambahkan.');
        redirect('/admin/pengurus');
    }

    public function edit(string $id): void
    {
        $p = $this->find((int) $id);
        $this->admin('pengurus/form', ['title' => 'Ubah Pengurus', 'p' => $p] + $this->formData());
    }

    public function update(string $id): void
    {
        $p = $this->find((int) $id);
        $back = '/admin/pengurus/' . $p['id'] . '/ubah';
        $data = $this->data();
        $this->check($data, $back);
        $oldFile = null;
        if (Upload::has('sk_file')) {
            [$file, $err] = Upload::document($_FILES['sk_file'], 'private/sk');
            if ($err) {
                $this->failWith(['sk_file' => $err], $data, $back);
            }
            $data['sk_file'] = $file;
            $oldFile = $p['sk_file'];
        }
        Database::update('pengurus', $data, 'id = ?', [$p['id']]);
        Upload::delete('private/sk', $oldFile);
        ActivityLog::record('update', 'pengurus', $p['id'], $p, $data);
        flash('success', 'Data pengurus diperbarui.');
        redirect('/admin/pengurus');
    }

    public function destroy(string $id): void
    {
        $p = $this->find((int) $id);
        Database::query('DELETE FROM pengurus WHERE id = ?', [$p['id']]);
        Upload::delete('private/sk', $p['sk_file']);
        ActivityLog::record('delete', 'pengurus', $p['id'], $p, null);
        flash('success', 'Data pengurus dihapus.');
        redirect('/admin/pengurus');
    }

    private function find(int $id): array
    {
        $params = [$id];
        $scope = Auth::scopeSql('p.wilayah_id', $params);
        return Database::one(
            "SELECT p.*, m.nama AS member_nama, m.nia FROM pengurus p JOIN members m ON m.id = p.member_id WHERE p.id = ? $scope",
            $params
        ) ?? abort(404, 'Data pengurus tidak ditemukan.');
    }

    private function formData(): array
    {
        $params = [];
        $scope = Auth::scopeSql('wilayah_id', $params);
        return [
            'members' => Database::all("SELECT id, nama, nia FROM members WHERE status = 'aktif' AND deleted_at IS NULL $scope ORDER BY nama", $params),
            'wilayahOptions' => Wilayah::options(Auth::scopeIds()),
        ];
    }

    private function data(): array
    {
        $d = $this->only(['member_id', 'wilayah_id', 'jabatan', 'urutan', 'periode_mulai', 'periode_selesai', 'sk_nomor']);
        $d['member_id'] = (int) $d['member_id'];
        $d['wilayah_id'] = (int) $d['wilayah_id'];
        $d['urutan'] = $d['urutan'] === '' || $d['urutan'] === null ? 100 : (int) $d['urutan'];
        $d['periode_selesai'] = $d['periode_selesai'] ?: null;
        $d['sk_nomor'] = $d['sk_nomor'] ?: null;
        return $d;
    }

    private function check(array $data, string $back): void
    {
        $this->validate($data, [
            'member_id' => 'required|integer',
            'wilayah_id' => 'required|integer|exists:wilayah,id',
            'jabatan' => 'required|max:80',
            'urutan' => 'integer',
            'periode_mulai' => 'required|date',
            'periode_selesai' => 'nullable|date|after_or_equal:periode_mulai',
            'sk_nomor' => 'nullable|max:80',
        ], self::LABELS, function (Validator $v) use ($data) {
            $m = Database::one("SELECT wilayah_id FROM members WHERE id = ? AND status = 'aktif' AND deleted_at IS NULL", [$data['member_id']]);
            if (!$m) {
                $v->addError('member_id', 'Pilih anggota berstatus aktif.');
            } elseif (!Auth::inScope((int) $m['wilayah_id'])) {
                $v->addError('member_id', 'Anggota di luar cakupan wilayah Anda.');
            }
            if (!Auth::inScope($data['wilayah_id'])) {
                $v->addError('wilayah_id', 'Anda hanya dapat memilih wilayah dalam cakupan Anda.');
            }
        }, $back);
    }
}
