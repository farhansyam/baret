<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Throttle;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Member;
use App\Models\Wilayah;

final class RegistrationController extends Controller
{
    public function form(): void
    {
        $this->render('public/daftar/form', [
            'title' => 'Pendaftaran Anggota',
            'wilayahOptions' => Wilayah::options(),
        ]);
    }

    public function store(): void
    {
        if ($this->isBot()) {
            redirect('/daftar/berhasil');
        }
        $data = $this->only(['nama', 'nik', 'tempat_lahir', 'tgl_lahir', 'jk', 'pekerjaan', 'telepon', 'email', 'alamat', 'wilayah_id', 'persetujuan']);
        $data['nik'] = preg_replace('/\D/', '', (string) $data['nik']);
        $data['telepon'] = preg_replace('/[\s-]/', '', (string) $data['telepon']);

        $rules = Member::rules() + ['persetujuan' => 'accepted'];
        $v = Validator::make($data, $rules, Member::LABELS);

        if (!isset($v->errors()['nik']) && Member::nikTaken($data['nik'])) {
            $v->addError('nik', 'NIK ini sudah terdaftar. Gunakan menu "Cek Status Pendaftaran" atau hubungi sekretariat.');
        }
        if (!Upload::has('foto')) {
            $v->addError('foto', 'Pas foto wajib diunggah.');
        }
        if ($v->fails()) {
            $this->failWith($v->errors(), $data, '/daftar');
        }
        if (!Throttle::allow('daftar:' . client_ip(), (int) config('security.public_form_limit', 5))) {
            $this->failWith([], $data, '/daftar', 'Terlalu banyak pendaftaran dari jaringan Anda. Coba lagi dalam satu jam.');
        }

        [$foto, $err] = Upload::image($_FILES['foto'], 'private/members', 800);
        if ($err) {
            $this->failWith(['foto' => $err], $data, '/daftar');
        }
        $ktp = null;
        if (Upload::has('ktp')) {
            [$ktp, $err] = Upload::image($_FILES['ktp'], 'private/ktp', 1400);
            if ($err) {
                Upload::delete('private/members', $foto);
                $this->failWith(['ktp' => $err], $data, '/daftar');
            }
        }

        $row = array_intersect_key($data, array_flip(Member::FIELDS));
        $row['email'] = $row['email'] ?: null;
        $row['pekerjaan'] = $row['pekerjaan'] ?: null;
        $row['wilayah_id'] = (int) $row['wilayah_id'];
        $row['foto'] = $foto;
        $row['ktp_file'] = $ktp;
        $row['consent_at'] = now();

        $id = Member::create($row, $data['nik']);
        $no = \App\Core\Database::value('SELECT no_registrasi FROM members WHERE id = ?', [$id]);
        $_SESSION['registrasi_baru'] = ['no' => $no, 'nama' => $row['nama']];
        redirect('/daftar/berhasil');
    }

    public function success(): void
    {
        $reg = $_SESSION['registrasi_baru'] ?? null;
        if (!$reg) {
            redirect('/daftar');
        }
        $this->render('public/daftar/berhasil', ['title' => 'Pendaftaran Terkirim', 'reg' => $reg]);
    }

    public function status(): void
    {
        $no = strtoupper((string) query('no', ''));
        $tgl = (string) query('tgl_lahir', '');
        $result = null;
        $limited = false;
        if ($no !== '' && $tgl !== '') {
            if (!Throttle::allow('regstatus:' . client_ip(), 20)) {
                $limited = true;
            } elseif (preg_match('/^REG-\d{6}-[A-F0-9]{5}$/', $no) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
                $result = Member::registrationLookup($no, $tgl);
            }
        }
        $this->render('public/daftar/status', [
            'title' => 'Cek Status Pendaftaran',
            'no' => $no,
            'tgl' => $tgl,
            'result' => $result,
            'searched' => $no !== '' && $tgl !== '',
            'limited' => $limited,
        ]);
    }
}
