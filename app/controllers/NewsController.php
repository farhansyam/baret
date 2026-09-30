<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\News;

final class NewsController extends Controller
{
    public function index(): void
    {
        $this->listing(null);
    }

    public function category(string $slug): void
    {
        $cat = Database::one('SELECT * FROM news_categories WHERE slug = ?', [$slug]) ?? abort(404);
        $this->listing($cat);
    }

    private function listing(?array $cat): void
    {
        $q = mb_substr((string) query('q', ''), 0, 100);
        [$items, $pager] = News::publicPaginate($q ?: null, $cat ? (int) $cat['id'] : null);
        $this->render('public/berita/index', [
            'title' => $cat ? 'Berita: ' . $cat['nama'] : 'Berita',
            'items' => $items,
            'pager' => $pager,
            'q' => $q,
            'category' => $cat,
            'categories' => News::categories(),
        ]);
    }

    public function show(string $slug): void
    {
        $item = News::findPublic($slug) ?? abort(404, 'Berita tidak ditemukan atau belum diterbitkan.');
        Database::query('UPDATE news SET views = views + 1 WHERE id = ?', [$item['id']]);
        $related = Database::all(
            "SELECT judul, slug, gambar, published_at FROM news
             WHERE status = 'publish' AND published_at <= NOW() AND id <> ? AND (kategori_id <=> ?)
             ORDER BY published_at DESC LIMIT 3",
            [$item['id'], $item['kategori_id']]
        );
        $this->render('public/berita/show', [
            'title' => $item['judul'],
            'description' => $item['ringkasan'] ?: str_limit($item['isi'], 160),
            'item' => $item,
            'related' => $related,
        ]);
    }
}
