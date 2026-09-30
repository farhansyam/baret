-- Data awal wajib (peran, wilayah pusat, kategori, pengaturan).
-- Akun Super Admin dan data contoh dibuat oleh database/install.php.
SET NAMES utf8mb4;

INSERT INTO roles (id, slug, nama, deskripsi) VALUES
(1, 'super_admin', 'Super Admin', 'Konfigurasi sistem, pengguna, peran, seluruh modul'),
(2, 'ketua_umum', 'Ketua Umum', 'Memantau dashboard, laporan, dan memberi persetujuan'),
(3, 'sekretaris', 'Sekretaris', 'Anggota, kepengurusan, persuratan, dokumen, berita'),
(4, 'bendahara', 'Bendahara', 'Transaksi, bukti, laporan keuangan'),
(5, 'pengurus_wilayah', 'Pengurus Wilayah/Cabang', 'Anggota, kegiatan, laporan di wilayahnya saja'),
(6, 'anggota', 'Anggota', 'Profil, e-KTA, agenda, informasi');

INSERT INTO wilayah (id, kode, nama, tingkat, parent_id, alamat) VALUES
(1, 'PST', 'Dewan Pimpinan Pusat', 'pusat', NULL, 'Jakarta');

INSERT INTO news_categories (nama, slug) VALUES
('Organisasi', 'organisasi'), ('Kegiatan Sosial', 'kegiatan-sosial'), ('Pengumuman', 'pengumuman'), ('Opini', 'opini');

INSERT INTO settings (`key`, `value`) VALUES
('nama_organisasi', 'Barisan Relawan Tangguh'),
('singkatan', 'BARET'),
('tagline', 'Solid, tangguh, dan siap mengabdi untuk masyarakat.'),
('alamat', 'Jl. Contoh Raya No. 1, Jakarta'),
('telepon', '021-0000000'),
('whatsapp', '6281200000000'),
('email', 'sekretariat@baret.or.id'),
('instagram', ''),
('facebook', ''),
('youtube', ''),
('map_lat', '-6.200000'),
('map_lng', '106.816666'),
('nama_ketua', 'Ketua Umum BARET'),
('sambutan_ketua', 'Selamat datang di website resmi Barisan Relawan Tangguh. Kami hadir sebagai wadah bagi relawan yang siap bergerak cepat, bekerja dengan disiplin, dan berdiri bersama masyarakat.'),
('sejarah', 'Barisan Relawan Tangguh (BARET) lahir dari semangat sekelompok relawan yang terbiasa turun langsung membantu masyarakat saat bencana dan kegiatan sosial. Dari gerakan kecil, BARET tumbuh menjadi organisasi kemasyarakatan dengan struktur dari pusat hingga ranting.'),
('visi', 'Menjadi barisan relawan yang tangguh, berintegritas, dan dipercaya masyarakat.'),
('misi', "Membina anggota yang disiplin, solid, dan berjiwa pengabdian.\nMenggerakkan aksi sosial dan kemanusiaan secara cepat dan terorganisir.\nMenjadi mitra masyarakat dan pemerintah dalam menjaga persatuan.\nMengelola organisasi secara tertib, transparan, dan akuntabel."),
('nilai', "Tangguh|Tidak mudah menyerah menghadapi tantangan di lapangan.\nSolid|Satu komando, satu barisan, saling menguatkan.\nIntegritas|Jujur, amanah, dan bertanggung jawab.\nPengabdian|Hadir untuk masyarakat tanpa pamrih."),
('legalitas', "Akta Pendirian: (isi nomor akta)\nSK Kemenkumham: (isi nomor SK)\nNPWP Organisasi: (isi NPWP)"),
('ad_art_file', '');
