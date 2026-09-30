<?php
/** @var App\Core\Router $router */

use App\Controllers\AspirasiController;
use App\Controllers\AuthController;
use App\Controllers\EventController;
use App\Controllers\NewsController;
use App\Controllers\PublicController;
use App\Controllers\RegistrationController;
use App\Controllers\Admin;

// ---------- Website publik ----------
$router->get('/', [PublicController::class, 'home']);
$router->get('/profil', [PublicController::class, 'profil']);
$router->get('/struktur', [PublicController::class, 'struktur']);
$router->get('/kontak', [PublicController::class, 'kontak']);
$router->get('/verifikasi', [PublicController::class, 'verifikasi']);

$router->get('/berita', [NewsController::class, 'index']);
$router->get('/berita/kategori/{slug:[a-z0-9-]+}', [NewsController::class, 'category']);
$router->get('/berita/{slug:[a-z0-9-]+}', [NewsController::class, 'show']);

$router->get('/kegiatan', [EventController::class, 'index']);
$router->get('/kegiatan/{slug:[a-z0-9-]+}', [EventController::class, 'show']);

$router->get('/daftar', [RegistrationController::class, 'form']);
$router->post('/daftar', [RegistrationController::class, 'store']);
$router->get('/daftar/berhasil', [RegistrationController::class, 'success']);
$router->get('/daftar/status', [RegistrationController::class, 'status']);

$router->get('/aspirasi', [AspirasiController::class, 'form']);
$router->post('/aspirasi', [AspirasiController::class, 'store']);
$router->get('/aspirasi/terkirim', [AspirasiController::class, 'success']);
$router->get('/aspirasi/status', [AspirasiController::class, 'status']);

// ---------- Autentikasi ----------
$router->get('/login', [AuthController::class, 'loginForm'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/lupa-sandi', [AuthController::class, 'forgotForm'], ['guest']);
$router->post('/lupa-sandi', [AuthController::class, 'forgot'], ['guest']);
$router->get('/reset-sandi/{token:[a-f0-9]{64}}', [AuthController::class, 'resetForm'], ['guest']);
$router->post('/reset-sandi', [AuthController::class, 'reset'], ['guest']);

// ---------- Dashboard internal ----------
$router->group('/admin', ['auth'], function ($r) {
    $r->get('', [Admin\DashboardController::class, 'index']);
    $r->get('/akun', [Admin\AccountController::class, 'edit']);
    $r->post('/akun', [Admin\AccountController::class, 'update']);
    $r->post('/akun/sandi', [Admin\AccountController::class, 'password']);
    $r->get('/file/{area:members|ktp|aspirasi|sk}/{name:[a-f0-9]{32}\.(?:webp|pdf)}', [Admin\FileController::class, 'show']);

    // Anggota
    $r->get('/anggota', [Admin\MemberController::class, 'index'], ['perm:members.view']);
    $r->get('/anggota/ekspor', [Admin\MemberController::class, 'export'], ['perm:members.export']);
    $r->get('/anggota/tambah', [Admin\MemberController::class, 'create'], ['perm:members.manage']);
    $r->post('/anggota', [Admin\MemberController::class, 'store'], ['perm:members.manage']);
    $r->get('/anggota/{id:\d+}', [Admin\MemberController::class, 'show'], ['perm:members.view']);
    $r->get('/anggota/{id:\d+}/ubah', [Admin\MemberController::class, 'edit'], ['perm:members.manage']);
    $r->post('/anggota/{id:\d+}', [Admin\MemberController::class, 'update'], ['perm:members.manage']);
    $r->post('/anggota/{id:\d+}/status', [Admin\MemberController::class, 'status'], ['perm:members.verify']);
    $r->post('/anggota/{id:\d+}/arsip', [Admin\MemberController::class, 'archive'], ['perm:members.manage']);
    $r->post('/anggota/{id:\d+}/pulihkan', [Admin\MemberController::class, 'restore'], ['perm:members.manage']);

    // Wilayah & pengurus
    $r->get('/wilayah', [Admin\WilayahController::class, 'index'], ['perm:wilayah.view']);
    $r->get('/wilayah/tambah', [Admin\WilayahController::class, 'create'], ['perm:wilayah.manage']);
    $r->post('/wilayah', [Admin\WilayahController::class, 'store'], ['perm:wilayah.manage']);
    $r->get('/wilayah/{id:\d+}/ubah', [Admin\WilayahController::class, 'edit'], ['perm:wilayah.manage']);
    $r->post('/wilayah/{id:\d+}', [Admin\WilayahController::class, 'update'], ['perm:wilayah.manage']);
    $r->post('/wilayah/{id:\d+}/hapus', [Admin\WilayahController::class, 'destroy'], ['perm:wilayah.manage']);

    $r->get('/pengurus', [Admin\PengurusController::class, 'index'], ['perm:pengurus.view']);
    $r->get('/pengurus/tambah', [Admin\PengurusController::class, 'create'], ['perm:pengurus.manage']);
    $r->post('/pengurus', [Admin\PengurusController::class, 'store'], ['perm:pengurus.manage']);
    $r->get('/pengurus/{id:\d+}/ubah', [Admin\PengurusController::class, 'edit'], ['perm:pengurus.manage']);
    $r->post('/pengurus/{id:\d+}', [Admin\PengurusController::class, 'update'], ['perm:pengurus.manage']);
    $r->post('/pengurus/{id:\d+}/hapus', [Admin\PengurusController::class, 'destroy'], ['perm:pengurus.manage']);

    // Berita
    $r->get('/berita', [Admin\NewsController::class, 'index'], ['perm:news.manage']);
    $r->get('/berita/tambah', [Admin\NewsController::class, 'create'], ['perm:news.manage']);
    $r->post('/berita', [Admin\NewsController::class, 'store'], ['perm:news.manage']);
    $r->get('/berita/{id:\d+}/ubah', [Admin\NewsController::class, 'edit'], ['perm:news.manage']);
    $r->post('/berita/{id:\d+}', [Admin\NewsController::class, 'update'], ['perm:news.manage']);
    $r->post('/berita/{id:\d+}/status', [Admin\NewsController::class, 'toggle'], ['perm:news.manage']);
    $r->post('/berita/{id:\d+}/hapus', [Admin\NewsController::class, 'destroy'], ['perm:news.manage']);
    $r->get('/kategori', [Admin\CategoryController::class, 'index'], ['perm:news.manage']);
    $r->post('/kategori', [Admin\CategoryController::class, 'store'], ['perm:news.manage']);
    $r->post('/kategori/{id:\d+}', [Admin\CategoryController::class, 'update'], ['perm:news.manage']);
    $r->post('/kategori/{id:\d+}/hapus', [Admin\CategoryController::class, 'destroy'], ['perm:news.manage']);

    // Kegiatan
    $r->get('/kegiatan', [Admin\EventController::class, 'index'], ['perm:events.manage']);
    $r->get('/kegiatan/tambah', [Admin\EventController::class, 'create'], ['perm:events.manage']);
    $r->post('/kegiatan', [Admin\EventController::class, 'store'], ['perm:events.manage']);
    $r->get('/kegiatan/{id:\d+}/ubah', [Admin\EventController::class, 'edit'], ['perm:events.manage']);
    $r->post('/kegiatan/{id:\d+}', [Admin\EventController::class, 'update'], ['perm:events.manage']);
    $r->post('/kegiatan/{id:\d+}/status', [Admin\EventController::class, 'toggle'], ['perm:events.manage']);
    $r->post('/kegiatan/{id:\d+}/hapus', [Admin\EventController::class, 'destroy'], ['perm:events.manage']);
    $r->post('/kegiatan/{id:\d+}/foto', [Admin\EventController::class, 'addPhotos'], ['perm:events.manage']);
    $r->post('/kegiatan/foto/{id:\d+}/hapus', [Admin\EventController::class, 'deletePhoto'], ['perm:events.manage']);

    // Aspirasi
    $r->get('/aspirasi', [Admin\AspirasiController::class, 'index'], ['perm:aspirasi.view']);
    $r->get('/aspirasi/{id:\d+}', [Admin\AspirasiController::class, 'show'], ['perm:aspirasi.view']);
    $r->post('/aspirasi/{id:\d+}', [Admin\AspirasiController::class, 'update'], ['perm:aspirasi.manage']);
    $r->post('/aspirasi/{id:\d+}/arsip', [Admin\AspirasiController::class, 'archive'], ['perm:aspirasi.manage']);

    // Pengguna, log, pengaturan
    $r->get('/pengguna', [Admin\UserController::class, 'index'], ['perm:users.manage']);
    $r->get('/pengguna/tambah', [Admin\UserController::class, 'create'], ['perm:users.manage']);
    $r->post('/pengguna', [Admin\UserController::class, 'store'], ['perm:users.manage']);
    $r->get('/pengguna/{id:\d+}/ubah', [Admin\UserController::class, 'edit'], ['perm:users.manage']);
    $r->post('/pengguna/{id:\d+}', [Admin\UserController::class, 'update'], ['perm:users.manage']);
    $r->post('/pengguna/{id:\d+}/aktif', [Admin\UserController::class, 'toggle'], ['perm:users.manage']);
    $r->post('/pengguna/{id:\d+}/sandi', [Admin\UserController::class, 'resetPassword'], ['perm:users.manage']);

    $r->get('/log', [Admin\LogController::class, 'index'], ['perm:logs.view']);
    $r->get('/pengaturan', [Admin\SettingController::class, 'edit'], ['perm:settings.manage']);
    $r->post('/pengaturan', [Admin\SettingController::class, 'update'], ['perm:settings.manage']);
});
