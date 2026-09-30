<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\Throttle;
use App\Core\Validator;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->render('auth/login', ['title' => 'Masuk'], 'layouts/auth');
    }

    public function login(): void
    {
        $data = $this->only(['email', 'password']);
        $v = Validator::make($data, ['email' => 'required|email', 'password' => 'required'], ['email' => 'Email', 'password' => 'Kata sandi']);
        if ($v->fails()) {
            $this->failWith($v->errors(), $data, '/login');
        }
        $result = Auth::attempt((string) $data['email'], (string) $data['password']);
        if ($result !== true) {
            $this->failWith([], $data, '/login', $result);
        }
        $intended = $_SESSION['_intended'] ?? null;
        unset($_SESSION['_intended']);
        flash('success', 'Selamat datang, ' . Auth::user()['nama'] . '.');
        // Hanya arahkan ke path internal
        if (is_string($intended) && str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
            header('Location: ' . $intended, true, 302);
            exit;
        }
        redirect('/admin');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', 'Anda telah keluar.');
        redirect('/login');
    }

    public function forgotForm(): void
    {
        $this->render('auth/forgot', ['title' => 'Lupa Kata Sandi'], 'layouts/auth');
    }

    public function forgot(): void
    {
        $data = $this->only(['email']);
        $v = Validator::make($data, ['email' => 'required|email'], ['email' => 'Email']);
        if ($v->fails()) {
            $this->failWith($v->errors(), $data, '/lupa-sandi');
        }
        if (!Throttle::allow('forgot:' . client_ip(), 5)) {
            $this->failWith([], $data, '/lupa-sandi', 'Terlalu banyak permintaan. Coba lagi dalam satu jam.');
        }
        $user = Database::one('SELECT id, nama, email FROM users WHERE email = ? AND aktif = 1', [mb_strtolower((string) $data['email'])]);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            Database::query('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL', [$user['id']]);
            Database::insert('password_resets', [
                'user_id' => $user['id'],
                'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                'created_at' => now(),
            ]);
            $link = absolute_url('reset-sandi/' . $token);
            Mailer::send($user['email'], 'Atur ulang kata sandi BARET', "Halo {$user['nama']},\n\nKami menerima permintaan atur ulang kata sandi akun Anda. Buka tautan berikut (berlaku 1 jam):\n\n$link\n\nAbaikan email ini bila Anda tidak memintanya.\n\nSekretariat BARET");
            ActivityLog::record('reset_diminta', 'users', $user['id']);
        }
        // Pesan sama untuk email terdaftar/tidak (mencegah enumerasi akun)
        flash('success', 'Jika email terdaftar, tautan atur ulang kata sandi telah dikirim. Periksa kotak masuk Anda.');
        redirect('/login');
    }

    public function resetForm(string $token): void
    {
        if (!$this->findReset($token)) {
            flash('error', 'Tautan atur ulang tidak valid atau sudah kedaluwarsa.');
            redirect('/lupa-sandi');
        }
        $this->render('auth/reset', ['title' => 'Atur Ulang Kata Sandi', 'token' => $token], 'layouts/auth');
    }

    public function reset(): void
    {
        $data = $this->only(['token', 'password', 'password_confirmation']);
        $reset = preg_match('/^[a-f0-9]{64}$/', (string) $data['token']) ? $this->findReset($data['token']) : null;
        if (!$reset) {
            flash('error', 'Tautan atur ulang tidak valid atau sudah kedaluwarsa.');
            redirect('/lupa-sandi');
        }
        $this->validate($data, ['password' => 'required|password|confirmed'], ['password' => 'Kata sandi baru'], null, '/reset-sandi/' . $data['token']);
        Database::transaction(function () use ($reset, $data) {
            Database::update('users', ['password_hash' => password_hash($data['password'], PASSWORD_DEFAULT)], 'id = ?', [$reset['user_id']]);
            Database::update('password_resets', ['used_at' => now()], 'id = ?', [$reset['id']]);
        });
        ActivityLog::record('reset_sandi', 'users', $reset['user_id']);
        flash('success', 'Kata sandi berhasil diperbarui. Silakan masuk.');
        redirect('/login');
    }

    private function findReset(string $token): ?array
    {
        return Database::one(
            'SELECT r.id, r.user_id FROM password_resets r JOIN users u ON u.id = r.user_id
             WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW() AND u.aktif = 1',
            [hash('sha256', $token)]
        );
    }
}
