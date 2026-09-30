<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\HtmlSanitizer;
use App\Core\Paginator;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\News;

final class NewsController extends Controller
{
    private const LABELS = ['judul' => 'Judul', 'kategori_id' => 'Kategori', 'ringkasan' => 'Ringkasan', 'isi' => 'Isi berita', 'status' => 'Status', 'published_at' => 'Tanggal terbit', 'gambar' => 'Gambar utama'];

    public function index(): void
    {
        $params = [];
        $where = '1=1';
        if ($q = mb_substr((string) query('q', ''), 0, 100)) {
            $where .= ' AND n.judul LIKE ?';
            $params[] = "%$q%";
        }
        $status = (string) query('status', '');
        if (in_array($status, ['draft', 'publish'], true)) {
            $where .= ' AND n.status = ?';
            $params[] = $status;
        }
        $kat = (int) query('kategori_id', 0);
        if ($kat) {
            $where .= ' AND n.kategori_id = ?';
            $params[] = $kat;
        }
        $total = (int) Database::value("SELECT COUNT(*) FROM news n WHERE $where", $params);
        $pager = Paginator::fromRequest($total);
        $items = Database::all(
            "SELECT n.id, n.judul, n.slug, n.status, n.published_at, n.views, n.gambar, n.created_at, c.nama AS kategori, u.nama AS penulis
             FROM news n LEFT JOIN news_categories c ON c.id = n.kategori_id LEFT JOIN users u ON u.id = n.author_id
             WHERE $where ORDER BY COALESCE(n.published_at, n.created_at) DESC" . $pager->limitSql(),
            $params
        );
        $this->admin('berita/index', [
            'title' => 'Berita',
            'items' => $items, 'pager' => $pager,
            'f' => ['q' => $q, 'status' => $status, 'kategori_id' => $kat],
            'categories' => Database::all('SELECT id, nama FROM news_categories ORDER BY nama'),
        ]);
    }

    public function create(): void
    {
        $this->admin('berita/form', ['title' => 'Tulis Berita', 'n' => null, 'categories' => Database::all('SELECT id, nama FROM news_categories ORDER BY nama')]);
    }

    public function store(): void
    {
        $data = $this->data();
        $this->check($data, '/admin/berita/tambah');
        if (Upload::has('gambar')) {
            [$img, $err] = Upload::image($_FILES['gambar'], 'news');
            if ($err) {
                $this->failWith(['gambar' => $err], $data, '/admin/berita/tambah');
            }
            $data['gambar'] = $img;
        }
        $data['slug'] = News::uniqueSlug($data['judul']);
        $data['author_id'] = Auth::id();
        $data['created_at'] = now();
        $id = Database::insert('news', $data);
        ActivityLog::record('create', 'news', $id, null, array_diff_key($data, ['isi' => 1]));
        flash('success', $data['status'] === 'publish' ? 'Berita diterbitkan.' : 'Berita disimpan sebagai draft.');
        redirect('/admin/berita');
    }

    public function edit(string $id): void
    {
        $n = $this->find((int) $id);
        $this->admin('berita/form', ['title' => 'Ubah Berita', 'n' => $n, 'categories' => Database::all('SELECT id, nama FROM news_categories ORDER BY nama')]);
    }

    public function update(string $id): void
    {
        $n = $this->find((int) $id);
        $back = '/admin/berita/' . $n['id'] . '/ubah';
        $data = $this->data($n);
        $this->check($data, $back);
        $oldImg = null;
        if (Upload::has('gambar')) {
            [$img, $err] = Upload::image($_FILES['gambar'], 'news');
            if ($err) {
                $this->failWith(['gambar' => $err], $data, $back);
            }
            $data['gambar'] = $img;
            $oldImg = $n['gambar'];
        } elseif (input('hapus_gambar') === '1') {
            $data['gambar'] = null;
            $oldImg = $n['gambar'];
        }
        if ($data['judul'] !== $n['judul']) {
            $data['slug'] = News::uniqueSlug($data['judul'], (int) $n['id']);
        }
        Database::update('news', $data, 'id = ?', [$n['id']]);
        Upload::delete('news', $oldImg);
        ActivityLog::record('update', 'news', $n['id'], array_diff_key($n, ['isi' => 1]), array_diff_key($data, ['isi' => 1]) + (($data['isi'] !== $n['isi']) ? ['isi' => '[diubah]'] : []));
        flash('success', 'Berita diperbarui.');
        redirect('/admin/berita');
    }

    public function toggle(string $id): void
    {
        $n = $this->find((int) $id);
        $to = $n['status'] === 'publish' ? 'draft' : 'publish';
        $data = ['status' => $to];
        if ($to === 'publish' && !$n['published_at']) {
            $data['published_at'] = now();
        }
        Database::update('news', $data, 'id = ?', [$n['id']]);
        ActivityLog::record('status', 'news', $n['id'], ['status' => $n['status']], $data);
        flash('success', $to === 'publish' ? 'Berita diterbitkan.' : 'Berita dikembalikan ke draft (tidak tampil di publik).');
        back('/admin/berita');
    }

    public function destroy(string $id): void
    {
        $n = $this->find((int) $id);
        Database::query('DELETE FROM news WHERE id = ?', [$n['id']]);
        Upload::delete('news', $n['gambar']);
        ActivityLog::record('delete', 'news', $n['id'], array_diff_key($n, ['isi' => 1]), null);
        flash('success', 'Berita "' . $n['judul'] . '" dihapus beserta gambarnya.');
        redirect('/admin/berita');
    }

    private function find(int $id): array
    {
        return Database::one('SELECT * FROM news WHERE id = ?', [$id]) ?? abort(404, 'Berita tidak ditemukan.');
    }

    private function data(?array $existing = null): array
    {
        $d = $this->only(['judul', 'kategori_id', 'ringkasan', 'isi', 'status', 'published_at']);
        $d['isi'] = HtmlSanitizer::clean((string) $d['isi']);
        $d['kategori_id'] = $d['kategori_id'] ? (int) $d['kategori_id'] : null;
        $d['ringkasan'] = $d['ringkasan'] ?: null;
        if ($d['published_at']) {
            $d['published_at'] = str_replace('T', ' ', (string) $d['published_at']) . (strlen((string) $d['published_at']) === 16 ? ':00' : '');
            // Input datetime-local tanpa detik: pertahankan nilai lama bila menitnya sama
            if (!empty($existing['published_at']) && substr($existing['published_at'], 0, 16) === substr($d['published_at'], 0, 16)) {
                $d['published_at'] = $existing['published_at'];
            }
        } else {
            $d['published_at'] = $d['status'] === 'publish' ? ($existing['published_at'] ?? now()) : null;
        }
        return $d;
    }

    private function check(array $data, string $back): void
    {
        $this->validate($data, [
            'judul' => 'required|min:5|max:200',
            'kategori_id' => 'required|integer|exists:news_categories,id',
            'ringkasan' => 'nullable|max:300',
            'isi' => 'required|min:20',
            'status' => 'required|in:draft,publish',
            'published_at' => 'nullable|datetime',
        ], self::LABELS, function (Validator $v) use ($data) {
            if (trim(strip_tags((string) $data['isi'])) === '') {
                $v->addError('isi', 'Isi berita wajib diisi.');
            }
        }, $back);
    }
}
