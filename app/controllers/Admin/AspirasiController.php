<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Paginator;
use App\Models\Aspirasi;

final class AspirasiController extends Controller
{
    public function index(): void
    {
        $params = [];
        $arsip = query('arsip') === '1';
        $where = $arsip ? 'a.archived_at IS NOT NULL' : 'a.archived_at IS NULL';
        if ($q = mb_substr((string) query('q', ''), 0, 100)) {
            $where .= ' AND (a.tiket LIKE ? OR a.judul LIKE ? OR a.nama LIKE ?)';
            array_push($params, "%$q%", "%$q%", "%$q%");
        }
        $status = (string) query('status', '');
        if (isset(Aspirasi::STATUS[$status])) {
            $where .= ' AND a.status = ?';
            $params[] = $status;
        }
        $kategori = (string) query('kategori', '');
        if (in_array($kategori, Aspirasi::KATEGORI, true)) {
            $where .= ' AND a.kategori = ?';
            $params[] = $kategori;
        }
        $total = (int) Database::value("SELECT COUNT(*) FROM aspirasi a WHERE $where", $params);
        $pager = Paginator::fromRequest($total);
        $items = Database::all(
            "SELECT a.id, a.tiket, a.nama, a.kategori, a.judul, a.status, a.created_at, u.nama AS petugas
             FROM aspirasi a LEFT JOIN users u ON u.id = a.ditangani_oleh
             WHERE $where ORDER BY FIELD(a.status, 'baru','diproses','selesai','ditolak'), a.created_at DESC" . $pager->limitSql(),
            $params
        );
        $this->admin('aspirasi/index', [
            'title' => 'Aspirasi',
            'items' => $items, 'pager' => $pager,
            'f' => ['q' => $q, 'status' => $status, 'kategori' => $kategori, 'arsip' => $arsip],
        ]);
    }

    public function show(string $id): void
    {
        $a = $this->find((int) $id);
        $this->admin('aspirasi/show', ['title' => 'Tiket ' . $a['tiket'], 'a' => $a]);
    }

    public function update(string $id): void
    {
        $a = $this->find((int) $id);
        $data = $this->only(['status', 'tanggapan']);
        $this->validate($data, [
            'status' => 'required|in:' . implode(',', array_keys(Aspirasi::STATUS)),
            'tanggapan' => ($data['status'] ?? '') === 'baru' ? 'nullable|max:3000' : 'required|min:5|max:3000',
        ], ['status' => 'Status', 'tanggapan' => 'Tanggapan'], null, '/admin/aspirasi/' . $a['id']);
        $data['tanggapan'] = $data['tanggapan'] ?: null;
        $data['ditangani_oleh'] = Auth::id();
        Database::update('aspirasi', $data, 'id = ?', [$a['id']]);
        ActivityLog::record('update', 'aspirasi', $a['id'], $a, $data);
        flash('success', 'Tanggapan disimpan. Pelapor dapat melihatnya melalui cek status tiket.');
        redirect('/admin/aspirasi/' . $a['id']);
    }

    public function archive(string $id): void
    {
        $a = $this->find((int) $id);
        $to = $a['archived_at'] ? null : now();
        Database::update('aspirasi', ['archived_at' => $to], 'id = ?', [$a['id']]);
        ActivityLog::record($to ? 'archive' : 'restore', 'aspirasi', $a['id'], ['archived_at' => $a['archived_at']], ['archived_at' => $to]);
        flash('success', $to ? 'Tiket diarsipkan.' : 'Tiket dikeluarkan dari arsip.');
        redirect('/admin/aspirasi' . ($to ? '' : '/' . $a['id']));
    }

    private function find(int $id): array
    {
        return Database::one(
            'SELECT a.*, u.nama AS petugas FROM aspirasi a LEFT JOIN users u ON u.id = a.ditangani_oleh WHERE a.id = ?',
            [$id]
        ) ?? abort(404, 'Tiket tidak ditemukan.');
    }
}
