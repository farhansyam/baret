<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Validator;

/** Akun saya: ubah profil & kata sandi sendiri. */
final class AccountController extends Controller
{
    public function edit(): void
    {
        $this->admin('akun', ['title' => 'Akun Saya', 'u' => Auth::user()]);
    }

    public function update(): void
    {
        $u = Auth::user();
        $data = $this->only(['nama', 'email']);
        $data['email'] = mb_strtolower((string) $data['email']);
        $this->validate($data, [
            'nama' => 'required|max:120',
            'email' => 'required|email|unique:users,email,' . $u['id'],
        ], ['nama' => 'Nama', 'email' => 'Email'], null, '/admin/akun');
        Database::update('users', $data, 'id = ?', [$u['id']]);
        ActivityLog::record('update', 'users', $u['id'], $u, $data);
        flash('success', 'Profil akun diperbarui.');
        redirect('/admin/akun');
    }

    public function password(): void
    {
        $u = Auth::user();
        $data = $this->only(['password_lama', 'password', 'password_confirmation']);
        $hash = (string) Database::value('SELECT password_hash FROM users WHERE id = ?', [$u['id']]);
        $this->validate($data, [
            'password_lama' => 'required',
            'password' => 'required|password|confirmed',
        ], ['password_lama' => 'Kata sandi lama', 'password' => 'Kata sandi baru'], function (Validator $v) use ($data, $hash) {
            if (!password_verify((string) $data['password_lama'], $hash)) {
                $v->addError('password_lama', 'Kata sandi lama tidak sesuai.');
            }
        }, '/admin/akun#sandi');
        Database::update('users', ['password_hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT)], 'id = ?', [$u['id']]);
        session_regenerate_id(true);
        ActivityLog::record('ubah_sandi', 'users', $u['id']);
        flash('success', 'Kata sandi berhasil diubah.');
        redirect('/admin/akun');
    }
}
