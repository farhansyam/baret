<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ActivityLog;
use App\Core\Database;
use App\Core\Throttle;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Aspirasi;

final class AspirasiController extends Controller
{
    private const LABELS = ['nama' => 'Nama', 'kontak' => 'Email/No. HP', 'kategori' => 'Kategori', 'judul' => 'Judul', 'isi' => 'Isi aspirasi', 'lampiran' => 'Lampiran'];

    public function form(): void
    {
        $this->render('public/aspirasi/form', ['title' => 'Aspirasi & Pengaduan']);
    }

    public function store(): void
    {
        if ($this->isBot()) {
            redirect('/aspirasi/terkirim');
        }
        $data = $this->only(['nama', 'kontak', 'kategori', 'judul', 'isi']);
        $v = Validator::make($data, [
            'nama' => 'required|min:3|max:120',
            'kontak' => 'required|max:160',
            'kategori' => 'required|in:' . implode(',', Aspirasi::KATEGORI),
            'judul' => 'required|min:5|max:160',
            'isi' => 'required|min:20|max:5000',
        ], self::LABELS);
        $kontak = (string) $data['kontak'];
        if (!$v->errors() && !filter_var($kontak, FILTER_VALIDATE_EMAIL) && !preg_match('/^(\+62|62|0)8[0-9]{7,12}$/', preg_replace('/[\s-]/', '', $kontak))) {
            $v->addError('kontak', 'Isi dengan alamat email atau nomor HP yang valid.');
        }
        if ($v->fails()) {
            $this->failWith($v->errors(), $data, '/aspirasi');
        }
        if (!Throttle::allow('aspirasi:' . client_ip(), (int) config('security.public_form_limit', 5))) {
            $this->failWith([], $data, '/aspirasi', 'Terlalu banyak kiriman dari jaringan Anda. Coba lagi dalam satu jam.');
        }

        $lampiran = null;
        if (Upload::has('lampiran')) {
            [$lampiran, $err] = Upload::document($_FILES['lampiran'], 'private/aspirasi', true);
            if ($err) {
                $this->failWith(['lampiran' => $err], $data, '/aspirasi');
            }
        }

        $tiket = Aspirasi::newTicket();
        $row = $data + ['tiket' => $tiket, 'lampiran' => $lampiran, 'status' => 'baru', 'ip' => client_ip(), 'created_at' => now()];
        $id = Database::insert('aspirasi', $row);
        ActivityLog::record('create', 'aspirasi', $id, null, ['tiket' => $tiket, 'kategori' => $data['kategori'], 'judul' => $data['judul']]);

        $_SESSION['aspirasi_baru'] = $tiket;
        redirect('/aspirasi/terkirim');
    }

    public function success(): void
    {
        $tiket = $_SESSION['aspirasi_baru'] ?? null;
        if (!$tiket) {
            redirect('/aspirasi');
        }
        $this->render('public/aspirasi/terkirim', ['title' => 'Aspirasi Terkirim', 'tiket' => $tiket]);
    }

    public function status(): void
    {
        $tiket = strtoupper((string) query('tiket', ''));
        $result = null;
        $limited = false;
        if ($tiket !== '') {
            if (!Throttle::allow('aspstatus:' . client_ip(), 30)) {
                $limited = true;
            } elseif (preg_match('/^ASP-\d{6}-[A-Z0-9]{6}$/', $tiket)) {
                $result = Aspirasi::findByTicket($tiket);
            }
        }
        $this->render('public/aspirasi/status', [
            'title' => 'Cek Status Aspirasi',
            'tiket' => $tiket,
            'result' => $result,
            'searched' => $tiket !== '',
            'limited' => $limited,
        ]);
    }
}
