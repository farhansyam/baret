<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Crypto;
use App\Core\Database;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Member;
use App\Models\Wilayah;

final class MemberController extends Controller
{
    private function filters(): array
    {
        return [
            'q' => mb_substr((string) query('q', ''), 0, 100),
            'status' => (string) query('status', ''),
            'wilayah_id' => (int) query('wilayah_id', 0) ?: null,
            'arsip' => query('arsip') === '1',
        ];
    }

    public function index(): void
    {
        $f = $this->filters();
        if ($f['wilayah_id'] && !Auth::inScope($f['wilayah_id'])) {
            $f['wilayah_id'] = null;
        }
        [$items, $pager] = Member::paginate($f);
        $this->admin('anggota/index', [
            'title' => 'Anggota',
            'items' => $items,
            'pager' => $pager,
            'f' => $f,
            'wilayahOptions' => Wilayah::options(Auth::scopeIds()),
        ]);
    }

    public function export(): void
    {
        $f = $this->filters();
        if ($f['wilayah_id'] && !Auth::inScope($f['wilayah_id'])) {
            $f['wilayah_id'] = null;
        }
        $rows = Member::forExport($f);
        ActivityLog::record('export', 'members', null, null, ['jumlah' => count($rows), 'filter' => array_filter($f)]);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="anggota-baret-' . date('Ymd-His') . '.csv"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
        fputcsv($out, ['No. Registrasi', 'NIA', 'Nama', 'JK', 'Tempat Lahir', 'Tgl Lahir', 'Pekerjaan', 'Telepon', 'Email', 'Kode Wilayah', 'Wilayah', 'Status', 'Tgl Daftar', 'Tgl Verifikasi'], ';', '"', '');
        foreach ($rows as $r) {
            fputcsv($out, array_map('csv_safe', array_values($r)), ';', '"', '');
        }
        fclose($out);
        exit;
    }

    public function show(string $id): void
    {
        $m = Member::find((int) $id, true) ?? abort(404, 'Anggota tidak ditemukan.');
        $this->admin('anggota/show', [
            'title' => $m['nama'],
            'm' => $m,
            'history' => Member::history((int) $id),
            'jabatan' => Database::all(
                'SELECT p.jabatan, p.periode_mulai, p.periode_selesai, w.nama AS wilayah_nama FROM pengurus p
                 JOIN wilayah w ON w.id = p.wilayah_id WHERE p.member_id = ? ORDER BY p.periode_mulai DESC',
                [(int) $id]
            ),
        ]);
    }

    public function create(): void
    {
        $this->admin('anggota/form', [
            'title' => 'Tambah Anggota',
            'm' => null,
            'wilayahOptions' => Wilayah::options(Auth::scopeIds()),
        ]);
    }

    public function store(): void
    {
        $data = $this->only(array_merge(Member::FIELDS, ['nik', 'langsung_aktif']));
        $data = $this->normalize($data);
        $v = Validator::make($data, Member::rules(), Member::LABELS);
        $this->extraChecks($v, $data, null);
        if ($v->fails()) {
            $this->failWith($v->errors(), $data, '/admin/anggota/tambah');
        }
        [$foto, $ktp] = $this->handleFiles($data, '/admin/anggota/tambah');

        $row = $this->row($data);
        $row['foto'] = $foto;
        $row['ktp_file'] = $ktp;
        $id = Member::create($row, $data['nik'], Auth::id());

        if (!empty($data['langsung_aktif']) && Auth::can('members.verify')) {
            Member::changeStatus(Member::find($id), 'aktif', 'Disetujui saat input admin');
        }
        flash('success', 'Anggota "' . $row['nama'] . '" berhasil ditambahkan.');
        redirect('/admin/anggota/' . $id);
    }

    public function edit(string $id): void
    {
        $m = Member::find((int) $id) ?? abort(404, 'Anggota tidak ditemukan.');
        $this->admin('anggota/form', [
            'title' => 'Ubah Anggota',
            'm' => $m,
            'wilayahOptions' => Wilayah::options(Auth::scopeIds()),
        ]);
    }

    public function update(string $id): void
    {
        $m = Member::find((int) $id) ?? abort(404, 'Anggota tidak ditemukan.');
        $back = '/admin/anggota/' . $m['id'] . '/ubah';
        $data = $this->normalize($this->only(array_merge(Member::FIELDS, ['nik'])));
        $v = Validator::make($data, Member::rules((int) $m['id']), Member::LABELS);
        $this->extraChecks($v, $data, (int) $m['id']);
        if ($v->fails()) {
            $this->failWith($v->errors(), $data, $back);
        }
        [$foto, $ktp] = $this->handleFiles($data, $back);

        $row = $this->row($data);
        if ($data['nik'] !== $m['nik']) {
            $row['nik_enc'] = Crypto::encrypt($data['nik']);
            $row['nik_hash'] = Crypto::blindIndex($data['nik']);
        }
        if ($foto) {
            $row['foto'] = $foto;
        }
        if ($ktp) {
            $row['ktp_file'] = $ktp;
        }
        Database::update('members', $row, 'id = ?', [$m['id']]);
        if ($foto) {
            Upload::delete('private/members', $m['foto']);
        }
        if ($ktp) {
            Upload::delete('private/ktp', $m['ktp_file']);
        }
        $oldForLog = array_intersect_key($m, $row);
        if (isset($row['nik_enc'])) {
            $oldForLog['nik'] = mask_nik($m['nik']);
            $row['nik'] = mask_nik($data['nik']);
        }
        ActivityLog::record('update', 'members', $m['id'], $oldForLog, $row);
        flash('success', 'Data anggota berhasil diperbarui.');
        redirect('/admin/anggota/' . $m['id']);
    }

    public function status(string $id): void
    {
        $m = Member::find((int) $id) ?? abort(404, 'Anggota tidak ditemukan.');
        $to = (string) input('status');
        $catatan = mb_substr((string) input('catatan', ''), 0, 255);
        if ($to === 'ditolak' && trim($catatan) === '') {
            $this->failWith(['catatan' => 'Alasan penolakan wajib diisi.'], ['catatan' => $catatan, 'status' => $to], '/admin/anggota/' . $m['id']);
        }
        if ($err = Member::changeStatus($m, $to, $catatan)) {
            flash('error', $err);
        } else {
            $fresh = Member::find((int) $id);
            $msg = match ($to) {
                'aktif' => $m['nia'] ? 'Anggota diaktifkan kembali.' : 'Pendaftar disetujui. NIA terbit: ' . $fresh['nia'],
                'ditolak' => 'Pendaftaran ditolak.',
                'nonaktif' => 'Anggota dinonaktifkan.',
                default => 'Status diperbarui.',
            };
            flash('success', $msg);
        }
        redirect('/admin/anggota/' . $m['id']);
    }

    public function archive(string $id): void
    {
        $m = Member::find((int) $id) ?? abort(404, 'Anggota tidak ditemukan.');
        $aktifJabatan = (int) Database::value(
            'SELECT COUNT(*) FROM pengurus WHERE member_id = ? AND (periode_selesai IS NULL OR periode_selesai >= CURDATE())',
            [$m['id']]
        );
        if ($aktifJabatan) {
            flash('error', 'Anggota ini masih tercatat sebagai pengurus aktif. Akhiri masa jabatannya terlebih dahulu.');
            redirect('/admin/anggota/' . $m['id']);
        }
        Database::update('members', ['deleted_at' => now()], 'id = ?', [$m['id']]);
        ActivityLog::record('archive', 'members', $m['id'], ['deleted_at' => null], ['deleted_at' => now()]);
        flash('success', 'Anggota "' . $m['nama'] . '" dipindahkan ke arsip.');
        redirect('/admin/anggota');
    }

    public function restore(string $id): void
    {
        $m = Member::find((int) $id, true) ?? abort(404, 'Anggota tidak ditemukan.');
        Database::update('members', ['deleted_at' => null], 'id = ?', [$m['id']]);
        ActivityLog::record('restore', 'members', $m['id'], ['deleted_at' => $m['deleted_at']], ['deleted_at' => null]);
        flash('success', 'Anggota "' . $m['nama'] . '" dipulihkan dari arsip.');
        redirect('/admin/anggota/' . $m['id']);
    }

    private function normalize(array $data): array
    {
        $data['nik'] = preg_replace('/\D/', '', (string) ($data['nik'] ?? ''));
        $data['telepon'] = preg_replace('/[\s-]/', '', (string) ($data['telepon'] ?? ''));
        return $data;
    }

    private function extraChecks(Validator $v, array $data, ?int $exceptId): void
    {
        if (!isset($v->errors()['nik']) && $data['nik'] && Member::nikTaken($data['nik'], $exceptId)) {
            $v->addError('nik', 'NIK ini sudah terdaftar pada anggota lain.');
        }
        if (!isset($v->errors()['wilayah_id']) && !Auth::inScope((int) $data['wilayah_id'])) {
            $v->addError('wilayah_id', 'Anda hanya dapat memilih wilayah dalam cakupan Anda.');
        }
    }

    /** @return array{0: ?string, 1: ?string} */
    private function handleFiles(array $data, string $back): array
    {
        $foto = $ktp = null;
        if (Upload::has('foto')) {
            [$foto, $err] = Upload::image($_FILES['foto'], 'private/members', 800);
            if ($err) {
                $this->failWith(['foto' => $err], $data, $back);
            }
        }
        if (Upload::has('ktp')) {
            [$ktp, $err] = Upload::image($_FILES['ktp'], 'private/ktp', 1400);
            if ($err) {
                Upload::delete('private/members', $foto);
                $this->failWith(['ktp' => $err], $data, $back);
            }
        }
        return [$foto, $ktp];
    }

    private function row(array $data): array
    {
        $row = array_intersect_key($data, array_flip(Member::FIELDS));
        $row['email'] = $row['email'] ?: null;
        $row['pekerjaan'] = $row['pekerjaan'] ?: null;
        $row['wilayah_id'] = (int) $row['wilayah_id'];
        return $row;
    }
}
