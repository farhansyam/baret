<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Validator;
use App\Models\Wilayah;

final class UserController extends Controller
{
    private const LABELS = ['nama' => 'Nama', 'email' => 'Email', 'role_id' => 'Peran', 'wilayah_id' => 'Wilayah', 'password' => 'Kata sandi', 'member_id' => 'Data anggota'];

    public function index(): void
    {
        $params = [];
        $where = '1=1';
        if ($q = mb_substr((string) query('q', ''), 0, 100)) {
            $where .= ' AND (u.nama LIKE ? OR u.email LIKE ?)';
            array_push($params, "%$q%", "%$q%");
        }
        $role = (int) query('role_id', 0);
        if ($role) {
            $where .= ' AND u.role_id = ?';
            $params[] = $role;
        }
        $total = (int) Database::value("SELECT COUNT(*) FROM users u WHERE $where", $params);
        $pager = Paginator::fromRequest($total);
        $items = Database::all(
            "SELECT u.*, r.nama AS role_nama, r.slug AS role, w.nama AS wilayah_nama FROM users u
             JOIN roles r ON r.id = u.role_id LEFT JOIN wilayah w ON w.id = u.wilayah_id
             WHERE $where ORDER BY u.aktif DESC, r.id, u.nama" . $pager->limitSql(),
            $params
        );
        $this->admin('pengguna/index', [
            'title' => 'Pengguna & Peran',
            'items' => $items, 'pager' => $pager,
            'f' => ['q' => $q, 'role_id' => $role],
            'roles' => $this->roles(),
        ]);
    }

    public function create(): void
    {
        $this->admin('pengguna/form', ['title' => 'Tambah Pengguna', 'u' => null] + $this->formData());
    }

    public function store(): void
    {
        $data = $this->data();
        $data['password'] = (string) input('password');
        $data['password_confirmation'] = (string) input('password_confirmation');
        $this->check($data, null, '/admin/pengguna/tambah');
        $row = $this->row($data);
        $row['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $row['created_at'] = now();
        $id = Database::insert('users', $row);
        $this->linkMember($id, $data['member_id']);
        ActivityLog::record('create', 'users', $id, null, $row);
        flash('success', 'Akun "' . $row['email'] . '" dibuat.');
        redirect('/admin/pengguna');
    }

    public function edit(string $id): void
    {
        $u = $this->find((int) $id);
        $u['member_id'] = Database::value('SELECT id FROM members WHERE user_id = ?', [$u['id']]);
        $this->admin('pengguna/form', ['title' => 'Ubah Pengguna', 'u' => $u] + $this->formData());
    }

    public function update(string $id): void
    {
        $u = $this->find((int) $id);
        $data = $this->data();
        $this->check($data, (int) $u['id'], '/admin/pengguna/' . $u['id'] . '/ubah');
        $row = $this->row($data);
        if ((int) $u['id'] === Auth::id() && (int) $row['role_id'] !== (int) $u['role_id']) {
            $this->failWith(['role_id' => 'Anda tidak dapat mengubah peran akun Anda sendiri.'], $data, '/admin/pengguna/' . $u['id'] . '/ubah');
        }
        Database::update('users', $row, 'id = ?', [$u['id']]);
        $this->linkMember((int) $u['id'], $data['member_id']);
        ActivityLog::record('update', 'users', $u['id'], $u, $row);
        flash('success', 'Akun diperbarui.');
        redirect('/admin/pengguna');
    }

    /** Nonaktifkan/aktifkan (akun tidak pernah dihapus). */
    public function toggle(string $id): void
    {
        $u = $this->find((int) $id);
        if ((int) $u['id'] === Auth::id()) {
            flash('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            redirect('/admin/pengguna');
        }
        if ((int) $u['aktif'] && $u['role'] === 'super_admin'
            && (int) Database::value("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'super_admin' AND u.aktif = 1") <= 1) {
            flash('error', 'Minimal harus ada satu Super Admin aktif.');
            redirect('/admin/pengguna');
        }
        $to = (int) $u['aktif'] ? 0 : 1;
        Database::update('users', ['aktif' => $to], 'id = ?', [$u['id']]);
        ActivityLog::record($to ? 'activate' : 'deactivate', 'users', $u['id'], ['aktif' => $u['aktif']], ['aktif' => $to]);
        flash('success', 'Akun ' . $u['email'] . ($to ? ' diaktifkan.' : ' dinonaktifkan.'));
        redirect('/admin/pengguna');
    }

    public function resetPassword(string $id): void
    {
        $u = $this->find((int) $id);
        $data = ['password' => (string) input('password'), 'password_confirmation' => (string) input('password_confirmation')];
        $this->validate($data, ['password' => 'required|password|confirmed'], ['password' => 'Kata sandi baru'], null, '/admin/pengguna/' . $u['id'] . '/ubah#sandi');
        Database::update('users', ['password_hash' => password_hash($data['password'], PASSWORD_DEFAULT)], 'id = ?', [$u['id']]);
        Database::query('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL', [$u['id']]);
        ActivityLog::record('reset_sandi', 'users', $u['id'], null, ['oleh_admin' => Auth::id()]);
        flash('success', 'Kata sandi ' . $u['email'] . ' telah diatur ulang. Sampaikan kepada pemilik akun secara aman.');
        redirect('/admin/pengguna/' . $u['id'] . '/ubah');
    }

    private function find(int $id): array
    {
        return Database::one('SELECT u.*, r.slug AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?', [$id])
            ?? abort(404, 'Pengguna tidak ditemukan.');
    }

    private function roles(): array
    {
        return Database::all('SELECT * FROM roles ORDER BY id');
    }

    private function formData(): array
    {
        return [
            'roles' => $this->roles(),
            'wilayahOptions' => Wilayah::options(),
            'members' => Database::all("SELECT id, nama, nia, user_id FROM members WHERE status = 'aktif' AND deleted_at IS NULL ORDER BY nama"),
        ];
    }

    private function data(): array
    {
        $d = $this->only(['nama', 'email', 'role_id', 'wilayah_id', 'member_id']);
        $d['email'] = mb_strtolower((string) $d['email']);
        $d['role_id'] = (int) $d['role_id'];
        $d['wilayah_id'] = $d['wilayah_id'] ? (int) $d['wilayah_id'] : null;
        $d['member_id'] = $d['member_id'] ? (int) $d['member_id'] : null;
        return $d;
    }

    private function row(array $d): array
    {
        return ['nama' => $d['nama'], 'email' => $d['email'], 'role_id' => $d['role_id'], 'wilayah_id' => $d['wilayah_id']];
    }

    private function check(array $data, ?int $id, string $back): void
    {
        $rules = [
            'nama' => 'required|max:120',
            'email' => 'required|email|unique:users,email' . ($id ? ",$id" : ''),
            'role_id' => 'required|integer|exists:roles,id',
            'wilayah_id' => 'nullable|integer|exists:wilayah,id',
        ];
        if (!$id) {
            $rules['password'] = 'required|password|confirmed';
        }
        $this->validate($data, $rules, self::LABELS, function (Validator $v) use ($data, $id) {
            $slug = Database::value('SELECT slug FROM roles WHERE id = ?', [$data['role_id']]);
            if ($slug === 'pengurus_wilayah' && !$data['wilayah_id']) {
                $v->addError('wilayah_id', 'Pengurus wilayah/cabang wajib memiliki wilayah.');
            }
            if ($slug === 'anggota' && !$data['member_id']) {
                $v->addError('member_id', 'Akun anggota wajib ditautkan ke data anggota aktif.');
            }
            if ($data['member_id']) {
                $m = Database::one("SELECT user_id FROM members WHERE id = ? AND status = 'aktif' AND deleted_at IS NULL", [$data['member_id']]);
                if (!$m) {
                    $v->addError('member_id', 'Data anggota tidak valid.');
                } elseif ($m['user_id'] && (int) $m['user_id'] !== $id) {
                    $v->addError('member_id', 'Anggota ini sudah tertaut ke akun lain.');
                }
            }
        }, $back);
    }

    private function linkMember(int $userId, ?int $memberId): void
    {
        Database::query('UPDATE members SET user_id = NULL WHERE user_id = ? AND id <> ?', [$userId, (int) $memberId]);
        if ($memberId) {
            Database::update('members', ['user_id' => $userId], 'id = ?', [$memberId]);
        }
    }
}
