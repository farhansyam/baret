# Panduan Pasang BARET di Hosting (cPanel / shared hosting)

## Syarat hosting
- PHP **8.1 atau lebih baru** (disarankan 8.2+) dengan ekstensi: `pdo_mysql`, `gd` (dukungan WebP), `fileinfo`, `openssl`, `mbstring`, `iconv`
- MySQL 5.7+/8 atau MariaDB 10.4+
- Apache dengan `mod_rewrite` (standar di cPanel)
- HTTPS aktif (AutoSSL/Let's Encrypt di cPanel)

## Langkah

### 1. Buat database
cPanel → **MySQL® Databases**:
1. Buat database, mis. `akun_baret`
2. Buat user database + kata sandi kuat
3. Tambahkan user ke database dengan **ALL PRIVILEGES**

### 2. Impor data
cPanel → **phpMyAdmin** → pilih database tadi → tab **Import** → pilih `baret_demo.sql` → **Go**.

### 3. Unggah file
cPanel → **File Manager** → buka `public_html` → **Upload** `baret-hosting.zip` → klik kanan → **Extract**.
Pastikan isi zip (folder `app`, `public`, `storage`, `database`, file `.htaccess`) langsung berada di dalam `public_html`,
bukan di `public_html/baret-hosting/`.

Struktur akhir harus seperti ini:
```
public_html/
├── .htaccess        ← wajib ada (meneruskan request ke public/ & memblokir folder internal)
├── app/
├── database/
├── public/
└── storage/
```
Document root **tidak perlu diubah** — tetap `public_html`. URL website tetap bersih
(`domain-anda.com/berita`, bukan `domain-anda.com/public/berita`), dan folder `app/`, `storage/`, `database/`
otomatis tertutup (403) dari akses web. Konfigurasi ini sudah diuji di Apache dengan PHP FastCGI.

### 4. Atur koneksi
Edit `app/config/.env` (aktifkan "Show Hidden Files" di File Manager):
```
APP_URL=https://domain-anda.com
DB_HOST=localhost
DB_DATABASE=akun_baret
DB_USERNAME=akun_userdb
DB_PASSWORD=kata-sandi-db
```
**Jangan ubah `APP_KEY`** — kunci ini dipakai untuk mengenkripsi NIK anggota di data demo.
Simpan salinan `.env` di tempat aman (tanpa APP_KEY, NIK tidak bisa dibaca).

### 5. Izin folder
Folder berikut harus bisa ditulis PHP (permission `755`, atau `775` bila gagal unggah):
`storage/` (beserta isinya) dan `public/uploads/` (beserta isinya).

### 6. Selesai — uji
- Buka `https://domain-anda.com` → beranda tampil
- Buka `https://domain-anda.com/login` → masuk dengan akun Super Admin
- Coba akses `https://domain-anda.com/app/config/.env` → harus **403 Forbidden**

## Setelah online
- **Ganti semua kata sandi demo** (menu Akun Saya), atau nonaktifkan akun demo yang tidak dipakai (menu Pengguna & Peran)
- Ubah identitas, kontak, sambutan, visi-misi di **Pengaturan Situs**
- Hapus berita/kegiatan/anggota contoh bila tidak dipakai
- Email lupa-sandi memakai fungsi `mail()` hosting (`MAIL_DRIVER=mail`). Bila email tidak terkirim, cek pengaturan email di cPanel
- **Backup**: cPanel → Cron Jobs, jalankan harian:
  `/bin/bash /home/USERNAME/public_html/database/backup.sh >> /home/USERNAME/public_html/storage/logs/backup.log 2>&1`
  (atau gunakan fitur Backup bawaan cPanel)

## Masalah umum
| Gejala | Penyebab & solusi |
|---|---|
| Halaman putih / error 500 | Versi PHP < 8.1 → cPanel **Select PHP Version** / MultiPHP Manager, pilih 8.2 |
| "Layanan sedang mengalami gangguan" | Koneksi database salah → periksa `DB_*` di `.env` |
| Halaman selain beranda 404 | `mod_rewrite` / `.htaccess` tidak aktif, atau file `.htaccess` tidak ikut terekstrak |
| Gagal unggah gambar | Izin folder `public/uploads` / `storage`, atau ekstensi `gd`/`fileinfo` belum aktif |
| "Sesi formulir kedaluwarsa" | Cookie diblokir, atau pindah antara `http`/`https` — paksa HTTPS |
