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
use App\Models\Event;
use App\Models\News;
use App\Models\Wilayah;

final class EventController extends Controller
{
    private const LABELS = [
        'judul' => 'Judul kegiatan', 'deskripsi' => 'Deskripsi', 'lokasi' => 'Lokasi', 'mulai' => 'Waktu mulai',
        'selesai' => 'Waktu selesai', 'wilayah_id' => 'Penyelenggara', 'status' => 'Status', 'penanggung_jawab' => 'Penanggung jawab',
    ];

    public function index(): void
    {
        $params = [];
        $where = '1=1' . Auth::scopeSql('e.wilayah_id', $params);
        if ($q = mb_substr((string) query('q', ''), 0, 100)) {
            $where .= ' AND (e.judul LIKE ? OR e.lokasi LIKE ?)';
            array_push($params, "%$q%", "%$q%");
        }
        $status = (string) query('status', '');
        if (in_array($status, ['draft', 'publish'], true)) {
            $where .= ' AND e.status = ?';
            $params[] = $status;
        }
        $waktu = (string) query('waktu', '');
        $where .= match ($waktu) {
            'akan_datang' => ' AND e.mulai > NOW()',
            'berlangsung' => ' AND e.mulai <= NOW() AND e.selesai >= NOW()',
            'lampau' => ' AND e.selesai < NOW()',
            default => '',
        };
        $total = (int) Database::value("SELECT COUNT(*) FROM events e WHERE $where", $params);
        $pager = Paginator::fromRequest($total);
        $items = Database::all(
            "SELECT e.*, w.nama AS wilayah_nama, (SELECT COUNT(*) FROM event_photos p WHERE p.event_id = e.id) AS jumlah_foto
             FROM events e LEFT JOIN wilayah w ON w.id = e.wilayah_id WHERE $where ORDER BY e.mulai DESC" . $pager->limitSql(),
            $params
        );
        $this->admin('kegiatan/index', [
            'title' => 'Kegiatan',
            'items' => $items, 'pager' => $pager,
            'f' => ['q' => $q, 'status' => $status, 'waktu' => $waktu],
        ]);
    }

    public function create(): void
    {
        $this->admin('kegiatan/form', ['title' => 'Tambah Kegiatan', 'e' => null, 'photos' => [], 'wilayahOptions' => Wilayah::options(Auth::scopeIds())]);
    }

    public function store(): void
    {
        $data = $this->data();
        $this->check($data, '/admin/kegiatan/tambah');
        if (Upload::has('gambar')) {
            [$img, $err] = Upload::image($_FILES['gambar'], 'events');
            if ($err) {
                $this->failWith(['gambar' => $err], $data, '/admin/kegiatan/tambah');
            }
            $data['gambar'] = $img;
        }
        $data['slug'] = News::uniqueSlug($data['judul'], null, 'events');
        $data['created_by'] = Auth::id();
        $data['created_at'] = now();
        $id = Database::insert('events', $data);
        ActivityLog::record('create', 'events', $id, null, $data);
        flash('success', 'Kegiatan ditambahkan. Anda dapat menambahkan foto dokumentasi di bawah.');
        redirect('/admin/kegiatan/' . $id . '/ubah');
    }

    public function edit(string $id): void
    {
        $e = $this->find((int) $id);
        $this->admin('kegiatan/form', [
            'title' => 'Ubah Kegiatan', 'e' => $e,
            'photos' => Event::photos((int) $e['id']),
            'wilayahOptions' => Wilayah::options(Auth::scopeIds()),
        ]);
    }

    public function update(string $id): void
    {
        $e = $this->find((int) $id);
        $back = '/admin/kegiatan/' . $e['id'] . '/ubah';
        $data = $this->data();
        $this->check($data, $back);
        $oldImg = null;
        if (Upload::has('gambar')) {
            [$img, $err] = Upload::image($_FILES['gambar'], 'events');
            if ($err) {
                $this->failWith(['gambar' => $err], $data, $back);
            }
            $data['gambar'] = $img;
            $oldImg = $e['gambar'];
        } elseif (input('hapus_gambar') === '1') {
            $data['gambar'] = null;
            $oldImg = $e['gambar'];
        }
        if ($data['judul'] !== $e['judul']) {
            $data['slug'] = News::uniqueSlug($data['judul'], (int) $e['id'], 'events');
        }
        Database::update('events', $data, 'id = ?', [$e['id']]);
        Upload::delete('events', $oldImg);
        ActivityLog::record('update', 'events', $e['id'], $e, $data);
        flash('success', 'Kegiatan diperbarui.');
        redirect('/admin/kegiatan');
    }

    public function toggle(string $id): void
    {
        $e = $this->find((int) $id);
        $to = $e['status'] === 'publish' ? 'draft' : 'publish';
        Database::update('events', ['status' => $to], 'id = ?', [$e['id']]);
        ActivityLog::record('status', 'events', $e['id'], ['status' => $e['status']], ['status' => $to]);
        flash('success', $to === 'publish' ? 'Kegiatan dipublikasikan.' : 'Kegiatan dikembalikan ke draft.');
        back('/admin/kegiatan');
    }

    public function destroy(string $id): void
    {
        $e = $this->find((int) $id);
        $photos = Event::photos((int) $e['id']);
        Database::query('DELETE FROM events WHERE id = ?', [$e['id']]); // foto ikut terhapus (ON DELETE CASCADE)
        Upload::delete('events', $e['gambar']);
        foreach ($photos as $p) {
            Upload::delete('events', $p['file']);
        }
        ActivityLog::record('delete', 'events', $e['id'], $e + ['jumlah_foto' => count($photos)], null);
        flash('success', 'Kegiatan "' . $e['judul'] . '" dihapus beserta ' . count($photos) . ' foto galeri.');
        redirect('/admin/kegiatan');
    }

    public function addPhotos(string $id): void
    {
        $e = $this->find((int) $id);
        $files = array_slice(Upload::many('foto'), 0, 12);
        $caption = mb_substr((string) input('caption', ''), 0, 160) ?: null;
        if (!$files) {
            $this->failWith(['foto' => 'Pilih minimal satu foto.'], [], '/admin/kegiatan/' . $e['id'] . '/ubah#galeri');
        }
        $ok = 0;
        $errors = [];
        foreach ($files as $f) {
            [$name, $err] = Upload::image($f, 'events');
            if ($err) {
                $errors[] = e($f['name']) . ': ' . $err;
                continue;
            }
            $pid = Database::insert('event_photos', ['event_id' => $e['id'], 'file' => $name, 'caption' => $caption, 'created_at' => now()]);
            ActivityLog::record('create', 'event_photos', $pid, null, ['event_id' => $e['id'], 'file' => $name]);
            $ok++;
        }
        if ($ok) {
            flash('success', "$ok foto ditambahkan ke galeri.");
        }
        if ($errors) {
            flash('error', 'Sebagian foto gagal diunggah: ' . implode('; ', $errors));
        }
        redirect('/admin/kegiatan/' . $e['id'] . '/ubah#galeri');
    }

    public function deletePhoto(string $id): void
    {
        $p = Database::one('SELECT * FROM event_photos WHERE id = ?', [(int) $id]) ?? abort(404);
        $e = $this->find((int) $p['event_id']);
        Database::query('DELETE FROM event_photos WHERE id = ?', [$p['id']]);
        Upload::delete('events', $p['file']);
        ActivityLog::record('delete', 'event_photos', $p['id'], $p, null);
        flash('success', 'Foto dihapus dari galeri.');
        redirect('/admin/kegiatan/' . $e['id'] . '/ubah#galeri');
    }

    private function find(int $id): array
    {
        $params = [$id];
        $scope = Auth::scopeSql('wilayah_id', $params);
        return Database::one("SELECT * FROM events WHERE id = ? $scope", $params) ?? abort(404, 'Kegiatan tidak ditemukan.');
    }

    private function data(): array
    {
        $d = $this->only(['judul', 'deskripsi', 'lokasi', 'mulai', 'selesai', 'wilayah_id', 'status', 'penanggung_jawab']);
        $d['wilayah_id'] = $d['wilayah_id'] ? (int) $d['wilayah_id'] : null;
        $d['penanggung_jawab'] = $d['penanggung_jawab'] ?: null;
        foreach (['mulai', 'selesai'] as $k) {
            if ($d[$k] && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', (string) $d[$k])) {
                $d[$k] = str_replace('T', ' ', (string) $d[$k]) . ':00';
            }
        }
        return $d;
    }

    private function check(array $data, string $back): void
    {
        $this->validate($data, [
            'judul' => 'required|min:5|max:200',
            'deskripsi' => 'required|min:20|max:10000',
            'lokasi' => 'required|max:160',
            'mulai' => 'required|datetime',
            'selesai' => 'required|datetime|after_or_equal:mulai',
            'wilayah_id' => (Auth::isScoped() ? 'required|' : 'nullable|') . 'integer|exists:wilayah,id',
            'status' => 'required|in:draft,publish',
            'penanggung_jawab' => 'nullable|max:120',
        ], self::LABELS, function (Validator $v) use ($data) {
            if ($data['wilayah_id'] && !Auth::inScope($data['wilayah_id'])) {
                $v->addError('wilayah_id', 'Anda hanya dapat memilih wilayah dalam cakupan Anda.');
            }
        }, $back);
    }
}
