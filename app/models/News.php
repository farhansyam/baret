<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Paginator;

final class News
{
    public static function uniqueSlug(string $title, ?int $exceptId = null, string $table = 'news'): string
    {
        $base = mb_substr(slugify($title), 0, 200);
        $slug = $base;
        $i = 2;
        while (true) {
            $params = [$slug];
            $sql = "SELECT COUNT(*) FROM `$table` WHERE slug = ?";
            if ($exceptId) {
                $sql .= ' AND id <> ?';
                $params[] = $exceptId;
            }
            if (!(int) Database::value($sql, $params)) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }

    /** Berita publik: hanya status publish & tanggal terbit sudah lewat. */
    public static function publicWhere(array &$params, ?string $q = null, ?int $kategoriId = null): string
    {
        $where = "n.status = 'publish' AND n.published_at <= NOW()";
        if ($q) {
            $where .= ' AND (n.judul LIKE ? OR n.ringkasan LIKE ?)';
            array_push($params, "%$q%", "%$q%");
        }
        if ($kategoriId) {
            $where .= ' AND n.kategori_id = ?';
            $params[] = $kategoriId;
        }
        return $where;
    }

    public static function publicPaginate(?string $q, ?int $kategoriId, int $perPage = 9): array
    {
        $params = [];
        $where = self::publicWhere($params, $q, $kategoriId);
        $total = (int) Database::value("SELECT COUNT(*) FROM news n WHERE $where", $params);
        $pager = Paginator::fromRequest($total, $perPage);
        $rows = Database::all(
            "SELECT n.id, n.judul, n.slug, n.ringkasan, n.isi, n.gambar, n.published_at, c.nama AS kategori, c.slug AS kategori_slug
             FROM news n LEFT JOIN news_categories c ON c.id = n.kategori_id
             WHERE $where ORDER BY n.published_at DESC" . $pager->limitSql(),
            $params
        );
        return [$rows, $pager];
    }

    public static function latest(int $limit = 3): array
    {
        $params = [];
        $where = self::publicWhere($params);
        return Database::all(
            "SELECT n.id, n.judul, n.slug, n.ringkasan, n.isi, n.gambar, n.published_at, c.nama AS kategori
             FROM news n LEFT JOIN news_categories c ON c.id = n.kategori_id
             WHERE $where ORDER BY n.published_at DESC LIMIT " . (int) $limit,
            $params
        );
    }

    public static function findPublic(string $slug): ?array
    {
        $params = [];
        $where = self::publicWhere($params);
        $params[] = $slug;
        return Database::one(
            "SELECT n.*, c.nama AS kategori, c.slug AS kategori_slug, u.nama AS penulis
             FROM news n LEFT JOIN news_categories c ON c.id = n.kategori_id LEFT JOIN users u ON u.id = n.author_id
             WHERE $where AND n.slug = ?",
            $params
        );
    }

    public static function categories(): array
    {
        return Database::all(
            "SELECT c.*, (SELECT COUNT(*) FROM news n WHERE n.kategori_id = c.id AND n.status = 'publish' AND n.published_at <= NOW()) AS jumlah
             FROM news_categories c ORDER BY c.nama"
        );
    }
}
