<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ActivityLog;
use App\Core\Database;
use App\Models\News;

final class CategoryController extends Controller
{
    public function index(): void
    {
        $this->admin('berita/kategori', [
            'title' => 'Kategori Berita',
            'items' => Database::all('SELECT c.*, (SELECT COUNT(*) FROM news n WHERE n.kategori_id = c.id) AS jumlah FROM news_categories c ORDER BY c.nama'),
            'editId' => (int) query('ubah', 0),
        ]);
    }

    public function store(): void
    {
        $data = $this->only(['nama']);
        $this->validate($data, ['nama' => 'required|max:60|unique:news_categories,nama'], ['nama' => 'Nama kategori'], null, '/admin/kategori');
        $data['slug'] = News::uniqueSlug($data['nama'], null, 'news_categories');
        $id = Database::insert('news_categories', $data);
        ActivityLog::record('create', 'news_categories', $id, null, $data);
        flash('success', 'Kategori "' . $data['nama'] . '" ditambahkan.');
        redirect('/admin/kategori');
    }

    public function update(string $id): void
    {
        $c = Database::one('SELECT * FROM news_categories WHERE id = ?', [(int) $id]) ?? abort(404);
        $data = $this->only(['nama']);
        $this->validate($data, ['nama' => 'required|max:60|unique:news_categories,nama,' . $c['id']], ['nama' => 'Nama kategori'], null, '/admin/kategori?ubah=' . $c['id']);
        $data['slug'] = News::uniqueSlug($data['nama'], (int) $c['id'], 'news_categories');
        Database::update('news_categories', $data, 'id = ?', [$c['id']]);
        ActivityLog::record('update', 'news_categories', $c['id'], $c, $data);
        flash('success', 'Kategori diperbarui.');
        redirect('/admin/kategori');
    }

    public function destroy(string $id): void
    {
        $c = Database::one('SELECT * FROM news_categories WHERE id = ?', [(int) $id]) ?? abort(404);
        $n = (int) Database::value('SELECT COUNT(*) FROM news WHERE kategori_id = ?', [$c['id']]);
        if ($n) {
            flash('error', "Kategori \"{$c['nama']}\" masih dipakai oleh $n berita. Pindahkan beritanya terlebih dahulu.");
            redirect('/admin/kategori');
        }
        Database::query('DELETE FROM news_categories WHERE id = ?', [$c['id']]);
        ActivityLog::record('delete', 'news_categories', $c['id'], $c, null);
        flash('success', 'Kategori dihapus.');
        redirect('/admin/kategori');
    }
}
