<?php
/**
 * Instalasi database BARET.
 *
 *   php database/install.php                         # buat .env (bila belum), database, tabel, data awal, Super Admin
 *   php database/install.php --demo                  # + data contoh (wilayah, anggota, berita, kegiatan, akun per peran)
 *   php database/install.php --admin-email=a@b.id --admin-password=Rahasia123
 *
 * PERINGATAN: skema memakai DROP TABLE — menjalankan ulang akan MENGHAPUS data yang ada.
 * Tambahkan --force untuk mengonfirmasi bila tabel sudah ada.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$opts = getopt('', ['demo', 'force', 'admin-email:', 'admin-password:']);
$envFile = $root . '/app/config/.env';

function out(string $msg): void { fwrite(STDOUT, $msg . PHP_EOL); }
function fail(string $msg): never { fwrite(STDERR, "✗ $msg" . PHP_EOL); exit(1); }
function randomPassword(): string {
    $a = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $p = '';
    for ($i = 0; $i < 12; $i++) $p .= $a[random_int(0, strlen($a) - 1)];
    return $p . random_int(10, 99);
}

// 1) .env + APP_KEY
if (!is_file($envFile)) {
    copy($root . '/app/config/.env.example', $envFile);
    out('• app/config/.env dibuat dari .env.example — sesuaikan koneksi database bila perlu.');
}
$env = file_get_contents($envFile);
if (!preg_match('/^APP_KEY=base64:.{40,}$/m', $env)) {
    $key = 'base64:' . base64_encode(random_bytes(32));
    $env = preg_match('/^APP_KEY=.*$/m', $env) ? preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $env) : $env . "\nAPP_KEY=$key\n";
    file_put_contents($envFile, $env);
    out('• APP_KEY dibuat. Simpan salinan .env dengan aman — kunci ini diperlukan untuk membaca NIK terenkripsi.');
}

require $root . '/app/bootstrap.php';

use App\Core\Database;
use App\Models\Member;
use App\Models\News;

// 2) Database
$c = config('db');
$dsn = $c['socket'] ? "mysql:unix_socket={$c['socket']};charset=utf8mb4" : "mysql:host={$c['host']};port={$c['port']};charset=utf8mb4";
try {
    $server = new PDO($dsn, $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    fail('Tidak dapat terhubung ke MySQL: ' . $e->getMessage() . "\n  Periksa DB_HOST/DB_PORT/DB_USERNAME/DB_PASSWORD di app/config/.env");
}
if (!preg_match('/^\w+$/', $c['name'])) {
    fail('Nama database tidak valid.');
}
$server->exec("CREATE DATABASE IF NOT EXISTS `{$c['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$server->exec("USE `{$c['name']}`");
$existing = (int) $server->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $server->quote($c['name']) . " AND table_name = 'members'")->fetchColumn();
if ($existing && !isset($opts['force'])) {
    fail("Tabel sudah ada di database `{$c['name']}`. Menjalankan ulang akan MENGHAPUS SEMUA DATA.\n  Tambahkan --force bila Anda yakin.");
}
$server->exec(file_get_contents(__DIR__ . '/schema.sql'));
$server->exec(file_get_contents(__DIR__ . '/seed.sql'));
out("• Database `{$c['name']}`: tabel & data awal dibuat.");

// 3) Super Admin
$email = strtolower($opts['admin-email'] ?? 'admin@baret.or.id');
$pass = $opts['admin-password'] ?? randomPassword();
if (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) {
    fail('Kata sandi admin minimal 8 karakter dan mengandung huruf serta angka.');
}
Database::insert('users', [
    'nama' => 'Super Admin', 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
    'role_id' => 1, 'wilayah_id' => null, 'aktif' => 1, 'created_at' => now(),
]);

$summary = [['Super Admin', $email, $pass]];

// 4) Data contoh
if (isset($opts['demo'])) {
    $w = [];
    $w['PST'] = 1;
    $add = function (string $kode, string $nama, string $tingkat, int $parent) use (&$w) {
        $w[$kode] = Database::insert('wilayah', ['kode' => $kode, 'nama' => $nama, 'tingkat' => $tingkat, 'parent_id' => $parent, 'created_at' => now()]);
    };
    $add('JKT', 'DPW DKI Jakarta', 'wilayah', $w['PST']);
    $add('JBR', 'DPW Jawa Barat', 'wilayah', $w['PST']);
    $add('JTM', 'DPW Jawa Timur', 'wilayah', $w['PST']);
    $add('JKS', 'DPC Jakarta Selatan', 'cabang', $w['JKT']);
    $add('JKT-T', 'DPC Jakarta Timur', 'cabang', $w['JKT']);
    $add('BDG', 'DPC Kota Bandung', 'cabang', $w['JBR']);
    $add('BKS', 'DPC Kota Bekasi', 'cabang', $w['JBR']);
    $add('SBY', 'DPC Kota Surabaya', 'cabang', $w['JTM']);
    $add('JKS-01', 'Ranting Tebet', 'ranting', $w['JKS']);

    $roles = [
        ['Ketua Umum', 'ketua@baret.or.id', 2, null],
        ['Sekretaris Umum', 'sekretaris@baret.or.id', 3, null],
        ['Bendahara Umum', 'bendahara@baret.or.id', 4, null],
        ['Pengurus DPW Jakarta', 'jakarta@baret.or.id', 5, $w['JKT']],
        ['Pengurus DPC Bandung', 'bandung@baret.or.id', 5, $w['BDG']],
    ];
    $demoPass = $opts['admin-password'] ?? randomPassword();
    foreach ($roles as [$nama, $mail, $role, $wid]) {
        Database::insert('users', ['nama' => $nama, 'email' => $mail, 'password_hash' => password_hash($demoPass, PASSWORD_DEFAULT), 'role_id' => $role, 'wilayah_id' => $wid, 'aktif' => 1, 'created_at' => now()]);
        $summary[] = [$nama, $mail, $demoPass];
    }

    $first = ['Andi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fajar', 'Gilang', 'Hana', 'Indra', 'Joko', 'Kartika', 'Lukman', 'Maya', 'Nanda', 'Oki', 'Putri', 'Rizky', 'Sari', 'Taufik', 'Wulan', 'Yoga', 'Zaki', 'Bayu', 'Rina'];
    $last = ['Pratama', 'Santoso', 'Wijaya', 'Lestari', 'Saputra', 'Hidayat', 'Kusuma', 'Nugroho', 'Permana', 'Rahmawati', 'Siregar', 'Hasibuan'];
    $kota = ['Jakarta', 'Bandung', 'Surabaya', 'Bekasi', 'Bogor', 'Depok', 'Malang', 'Garut'];
    $cab = ['JKS', 'JKT-T', 'BDG', 'BKS', 'SBY', 'JKS-01', 'JKT', 'BDG'];
    $memberIds = [];
    for ($i = 0; $i < 34; $i++) {
        $nama = $first[$i % count($first)] . ' ' . $last[($i * 7 + intdiv($i, count($first))) % count($last)];
        $nik = '3174' . str_pad((string) (100000000000 + $i * 7919), 12, '0', STR_PAD_LEFT);
        $wk = $cab[$i % count($cab)];
        $id = Member::create([
            'nama' => $nama, 'tempat_lahir' => $kota[$i % count($kota)], 'tgl_lahir' => date('Y-m-d', strtotime('-' . (20 + $i % 25) . ' years -' . ($i * 11) . ' days')),
            'jk' => $i % 3 === 0 ? 'P' : 'L', 'pekerjaan' => ['Wiraswasta', 'Karyawan', 'Mahasiswa', 'Guru', 'Pedagang'][$i % 5],
            'telepon' => '0812' . str_pad((string) (3000000 + $i * 137), 8, '0', STR_PAD_LEFT), 'email' => null,
            'alamat' => 'Jl. Contoh No. ' . ($i + 1) . ', RT 0' . ($i % 9 + 1) . '/RW 02, ' . $kota[$i % count($kota)],
            'wilayah_id' => $w[$wk], 'consent_at' => now(),
        ], $nik);
        // Sebar tanggal daftar ke 12 bulan terakhir untuk grafik
        $created = date('Y-m-d H:i:s', strtotime('-' . (($i * 37) % 340) . ' days -' . ($i % 12) . ' hours'));
        Database::update('members', ['created_at' => $created], 'id = ?', [$id]);
        Database::update('member_status_history', ['waktu' => $created], 'member_id = ?', [$id]);
        $memberIds[$i] = $id;
        if ($i < 26) {
            Member::changeStatus(Member::find($id) ?? [], 'aktif', 'Data lengkap (data contoh)');
        } elseif ($i === 26) {
            Member::changeStatus(Member::find($id) ?? [], 'ditolak', 'Data tidak lengkap (data contoh)');
        }
    }
    Database::query("UPDATE members SET verified_at = LEAST(DATE_ADD(created_at, INTERVAL 2 DAY), NOW()) WHERE verified_at IS NOT NULL");

    $jab = [[0, 'PST', 'Ketua Umum', 1], [1, 'PST', 'Sekretaris Jenderal', 2], [2, 'PST', 'Bendahara Umum', 3],
        [3, 'JKT', 'Ketua', 1], [4, 'JKT', 'Sekretaris', 2], [5, 'BDG', 'Ketua', 1], [6, 'JKS', 'Ketua', 1], [7, 'SBY', 'Ketua', 1]];
    foreach ($jab as [$mi, $wk, $j, $u]) {
        Database::insert('pengurus', ['member_id' => $memberIds[$mi], 'wilayah_id' => $w[$wk], 'jabatan' => $j, 'urutan' => $u, 'periode_mulai' => date('Y') . '-01-01', 'periode_selesai' => (date('Y') + 4) . '-12-31', 'sk_nomor' => sprintf('SK/%03d/BRT/%s', $mi + 1, date('Y')), 'created_at' => now()]);
    }
    // Akun anggota contoh tertaut ke data anggota
    $uid = Database::insert('users', ['nama' => 'Anggota Contoh', 'email' => 'anggota@baret.or.id', 'password_hash' => password_hash($demoPass, PASSWORD_DEFAULT), 'role_id' => 6, 'aktif' => 1, 'created_at' => now()]);
    Database::update('members', ['user_id' => $uid], 'id = ?', [$memberIds[8]]);
    $summary[] = ['Anggota (tertaut)', 'anggota@baret.or.id', $demoPass];

    // Gambar contoh bernuansa merah-hitam
    $makeImage = function (int $seed): ?string {
        if (!function_exists('imagecreatetruecolor')) return null;
        $im = imagecreatetruecolor(1280, 720);
        for ($y = 0; $y < 720; $y++) {
            $t = $y / 720;
            $col = imagecolorallocate($im, (int) (13 + 17 * $t), (int) (13 + 17 * $t), (int) (13 + 21 * $t));
            imageline($im, 0, $y, 1279, $y, $col);
        }
        $red = imagecolorallocatealpha($im, 217, 20, 28, 20 + ($seed * 13) % 60);
        $x0 = 300 + ($seed * 170) % 600;
        imagefilledpolygon($im, [$x0, 0, $x0 + 260, 0, $x0 - 140, 720, $x0 - 400, 720], $red);
        $thin = imagecolorallocatealpha($im, 217, 20, 28, 70);
        imagefilledpolygon($im, [$x0 + 320, 0, $x0 + 350, 0, $x0 - 50, 720, $x0 - 80, 720], $thin);
        $logo = @imagecreatefrompng(PUBLIC_PATH . '/assets/img/logo-320.png');
        if ($logo) {
            imagecopyresampled($im, $logo, 1280 - 360, 720 - 360, 0, 0, 300, (int) (300 * imagesy($logo) / imagesx($logo)), imagesx($logo), imagesy($logo));
        }
        $name = bin2hex(random_bytes(16)) . '.webp';
        imagewebp($im, PUBLIC_PATH . '/uploads/news/' . $name, 80);
        return $name;
    };

    $isi = '<p>BARET kembali menunjukkan komitmennya untuk hadir di tengah masyarakat. Kegiatan ini diikuti oleh puluhan relawan dari berbagai cabang dengan semangat kebersamaan.</p><h2>Solid di Lapangan</h2><p>Para relawan dibagi ke dalam beberapa tim dengan tugas yang jelas: logistik, dapur umum, dan layanan kesehatan dasar. Koordinasi dilakukan satu komando agar bantuan tepat sasaran.</p><ul><li>Distribusi paket sembako</li><li>Layanan cek kesehatan gratis</li><li>Pendataan warga terdampak</li></ul><blockquote>Relawan tangguh bukan yang paling kuat, tetapi yang paling siap hadir ketika dibutuhkan.</blockquote><p>Pengurus menyampaikan terima kasih kepada seluruh anggota dan mitra yang telah mendukung kegiatan ini.</p>';
    $news = [
        ['BARET Salurkan Bantuan untuk Warga Terdampak Banjir', 2, 'Relawan BARET bergerak cepat menyalurkan logistik dan membuka dapur umum bagi warga terdampak banjir.', 3],
        ['Rapat Kerja Nasional BARET Tetapkan Program Prioritas', 1, 'Rakernas menetapkan penguatan kaderisasi dan digitalisasi pendataan anggota sebagai program prioritas.', 12],
        ['Pendaftaran Anggota Kini Bisa Dilakukan Secara Online', 3, 'Calon anggota kini dapat mendaftar melalui website resmi dalam tiga langkah mudah.', 20],
        ['Pelatihan Tanggap Darurat Bagi Relawan Cabang Bandung', 2, 'Puluhan relawan mengikuti pelatihan pertolongan pertama dan evakuasi bersama instruktur berpengalaman.', 35],
        ['Semangat Gotong Royong di Era Digital', 4, 'Refleksi tentang peran relawan di tengah perubahan zaman dan pentingnya organisasi yang tertib.', 50],
        ['Bakti Sosial dan Donor Darah Sambut Hari Jadi BARET', 2, 'Rangkaian hari jadi diisi dengan donor darah dan bakti sosial di sejumlah cabang.', 70],
    ];
    foreach ($news as $i => [$judul, $kat, $ring, $daysAgo]) {
        Database::insert('news', [
            'judul' => $judul, 'slug' => News::uniqueSlug($judul), 'kategori_id' => $kat, 'ringkasan' => $ring, 'isi' => $isi,
            'gambar' => $i % 3 === 2 ? null : $makeImage($i), 'status' => 'publish', 'author_id' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime("-$daysAgo days")), 'views' => 40 + $i * 23, 'created_at' => now(),
        ]);
    }
    Database::insert('news', ['judul' => 'Draf: Laporan Kegiatan Triwulan', 'slug' => 'draf-laporan-kegiatan-triwulan', 'kategori_id' => 1, 'ringkasan' => null, 'isi' => '<p>Draf laporan — belum untuk publik.</p>', 'status' => 'draft', 'author_id' => 1, 'published_at' => null, 'created_at' => now()]);

    $events = [
        ['Pelatihan Dasar Relawan Angkatan III', 'Aula DPW DKI Jakarta', '+6 days 08:00', '+6 days 16:00', 'JKT'],
        ['Bakti Sosial Ramadhan Berbagi', 'Lapangan Tebet, Jakarta Selatan', '+13 days 07:00', '+13 days 12:00', 'JKS'],
        ['Rapat Koordinasi Cabang Jawa Barat', 'Sekretariat DPC Bandung', '+20 days 13:00', '+20 days 17:00', 'BDG'],
        ['Donor Darah Hari Jadi BARET', 'PMI Kota Surabaya', '-2 days 08:00', '+1 days 15:00', 'SBY'],
        ['Aksi Bersih Sungai Ciliwung', 'Bantaran Ciliwung, Jakarta Timur', '-25 days 06:30', '-25 days 11:00', 'JKT-T'],
        ['Rakernas BARET ' . date('Y'), 'Jakarta', '-40 days 09:00', '-38 days 16:00', null],
    ];
    foreach ($events as $i => [$judul, $lok, $mulai, $selesai, $wk]) {
        $eid = Database::insert('events', [
            'judul' => $judul, 'slug' => News::uniqueSlug($judul, null, 'events'),
            'deskripsi' => "Kegiatan ini terbuka bagi seluruh anggota BARET dan masyarakat umum.\n\nPeserta diharapkan hadir tepat waktu dan mengenakan atribut organisasi. Informasi lebih lanjut dapat ditanyakan kepada sekretariat penyelenggara.",
            'lokasi' => $lok, 'mulai' => date('Y-m-d H:i:s', strtotime($mulai)), 'selesai' => date('Y-m-d H:i:s', strtotime($selesai)),
            'wilayah_id' => $wk ? $w[$wk] : null, 'status' => 'publish', 'penanggung_jawab' => 'Sekretariat ' . ($wk ?? 'Pusat'), 'created_by' => 1, 'created_at' => now(),
        ]);
        if ($i >= 4) {
            for ($k = 0; $k < 3; $k++) {
                $img = $makeImage($i * 3 + $k);
                if ($img) {
                    rename(PUBLIC_PATH . '/uploads/news/' . $img, PUBLIC_PATH . '/uploads/events/' . $img);
                    Database::insert('event_photos', ['event_id' => $eid, 'file' => $img, 'caption' => 'Dokumentasi ' . ($k + 1), 'created_at' => now()]);
                }
            }
        }
    }

    $asp = [
        ['Rudi', '081299990001', 'Saran', 'Usul pelatihan pertolongan pertama untuk warga', 'Mohon BARET mengadakan pelatihan pertolongan pertama untuk warga RW kami karena sering terjadi kecelakaan kecil.', 'baru', null],
        ['Siti', 'siti@example.com', 'Permohonan Bantuan', 'Permohonan bantuan kerja bakti lingkungan', 'Kami memohon dukungan relawan untuk kerja bakti membersihkan saluran air di lingkungan kami menjelang musim hujan.', 'diproses', 'Terima kasih. Permohonan diteruskan ke DPC terdekat untuk dijadwalkan.'],
        ['Anonim', '081299990003', 'Pengaduan', 'Atribut organisasi dipakai pihak tidak dikenal', 'Saya melihat ada pihak yang memakai atribut BARET untuk meminta sumbangan di jalan. Mohon ditindaklanjuti.', 'selesai', 'Sudah ditindaklanjuti. BARET tidak pernah meminta sumbangan di jalan; laporan telah diteruskan ke pihak berwenang.'],
    ];
    foreach ($asp as $i => [$n, $k, $kat, $jd, $isiA, $st, $tg]) {
        Database::insert('aspirasi', ['tiket' => \App\Models\Aspirasi::newTicket(), 'nama' => $n, 'kontak' => $k, 'kategori' => $kat, 'judul' => $jd, 'isi' => $isiA, 'status' => $st, 'tanggapan' => $tg, 'ditangani_oleh' => $tg ? 1 : null, 'created_at' => date('Y-m-d H:i:s', strtotime('-' . ($i * 4 + 1) . ' days'))]);
    }
    out('• Data contoh dibuat: 10 wilayah, 34 anggota, 7 berita, 6 kegiatan, 3 aspirasi, akun per peran.');
}

out('');
out('✓ Instalasi selesai. Akun yang dibuat:');
foreach ($summary as [$role, $mail, $pw]) {
    out(sprintf('  %-22s %-26s %s', $role, $mail, $pw));
}
out('');
out('  Segera ganti kata sandi setelah masuk pertama kali (menu Akun Saya).');
