<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Validator;
use App\Models\Wilayah;

final class WilayahController extends Controller
{
    private const LABELS = ['kode' => 'Kode', 'nama' => 'Nama', 'tingkat' => 'Tingkat', 'parent_id' => 'Induk', 'alamat' => 'Alamat sekretariat'];

    public function index(): void
    {
        $counts = array_column(Database::all("SELECT wilayah_id, COUNT(*) AS n FROM members WHERE deleted_at IS NULL AND status = 'aktif' GROUP BY wilayah_id"), 'n', 'wilayah_id');
        $this->admin('wilayah/index', [
            'title' => 'Struktur Wilayah',
            'tree' => Wilayah::tree(Auth::scopeIds()),
            'counts' => $counts,
        ]);
    }

    public function create(): void
    {
        $this->admin('wilayah/form', ['title' => 'Tambah Wilayah', 'w' => null, 'parents' => Wilayah::options()]);
    }

    public function store(): void
    {
        $data = $this->data();
        $this->check($data, null, '/admin/wilayah/tambah');
        $id = Database::insert('wilayah', $data + ['created_at' => now()]);
        ActivityLog::record('create', 'wilayah', $id, null, $data);
        flash('success', 'Wilayah "' . $data['nama'] . '" ditambahkan.');
        redirect('/admin/wilayah');
    }

    public function edit(string $id): void
    {
        $w = Wilayah::find((int) $id) ?? abort(404);
        $this->admin('wilayah/form', ['title' => 'Ubah Wilayah', 'w' => $w, 'parents' => Wilayah::options()]);
    }

    public function update(string $id): void
    {
        $w = Wilayah::find((int) $id) ?? abort(404);
        $data = $this->data();
        $this->check($data, (int) $w['id'], '/admin/wilayah/' . $w['id'] . '/ubah');
        if ($w['kode'] !== $data['kode'] && Database::value('SELECT COUNT(*) FROM members WHERE wilayah_id = ? AND nia IS NOT NULL', [$w['id']])) {
            $this->failWith(['kode' => 'Kode tidak dapat diubah karena sudah dipakai pada NIA anggota.'], $data, '/admin/wilayah/' . $w['id'] . '/ubah');
        }
        Database::update('wilayah', $data, 'id = ?', [$w['id']]);
        ActivityLog::record('update', 'wilayah', $w['id'], $w, $data);
        flash('success', 'Wilayah diperbarui.');
        redirect('/admin/wilayah');
    }

    public function destroy(string $id): void
    {
        $w = Wilayah::find((int) $id) ?? abort(404);
        $rel = Wilayah::relations((int) $w['id']);
        if ($rel) {
            $parts = [];
            foreach ($rel as $k => $n) {
                $parts[] = "$n $k";
            }
            flash('error', 'Tidak dapat menghapus "' . $w['nama'] . '" karena masih memiliki ' . implode(', ', $parts) . '.');
            redirect('/admin/wilayah');
        }
        Database::query('DELETE FROM wilayah WHERE id = ?', [$w['id']]);
        ActivityLog::record('delete', 'wilayah', $w['id'], $w, null);
        flash('success', 'Wilayah "' . $w['nama'] . '" dihapus.');
        redirect('/admin/wilayah');
    }

    private function data(): array
    {
        $d = $this->only(['kode', 'nama', 'tingkat', 'parent_id', 'alamat']);
        $d['kode'] = strtoupper((string) $d['kode']);
        $d['parent_id'] = $d['parent_id'] ? (int) $d['parent_id'] : null;
        $d['alamat'] = $d['alamat'] ?: null;
        return $d;
    }

    private function check(array $data, ?int $id, string $back): void
    {
        $this->validate($data, [
            'kode' => 'required|code|unique:wilayah,kode' . ($id ? ",$id" : ''),
            'nama' => 'required|max:120',
            'tingkat' => 'required|in:' . implode(',', array_keys(Wilayah::TINGKAT)),
            'alamat' => 'nullable|max:255',
        ], self::LABELS, function (Validator $v) use ($data, $id) {
            $needParent = Wilayah::PARENT_OF[$data['tingkat']] ?? null;
            if ($data['tingkat'] === 'pusat') {
                if ($data['parent_id']) {
                    $v->addError('parent_id', 'Tingkat pusat tidak memiliki induk.');
                }
                return;
            }
            $parent = $data['parent_id'] ? Wilayah::find($data['parent_id']) : null;
            if (!$parent) {
                $v->addError('parent_id', 'Induk wajib dipilih.');
            } elseif ($needParent && $parent['tingkat'] !== $needParent) {
                $v->addError('parent_id', 'Induk untuk tingkat ' . Wilayah::TINGKAT[$data['tingkat']] . ' harus berupa ' . Wilayah::TINGKAT[$needParent] . '.');
            } elseif ($id && in_array((int) $parent['id'], Wilayah::descendantIds($id), true)) {
                $v->addError('parent_id', 'Induk tidak boleh wilayah itu sendiri atau turunannya.');
            }
        }, $back);
    }
}
