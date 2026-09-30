<?php
$titles = [
    403 => ['Akses Ditolak', 'Anda tidak memiliki hak akses untuk membuka halaman ini.'],
    404 => ['Halaman Tidak Ditemukan', 'Halaman yang Anda cari tidak ada, sudah dipindahkan, atau belum diterbitkan.'],
    405 => ['Metode Tidak Diizinkan', 'Permintaan ini tidak dapat diproses.'],
    419 => ['Sesi Kedaluwarsa', 'Sesi formulir Anda telah berakhir. Muat ulang halaman lalu coba lagi.'],
    429 => ['Terlalu Banyak Permintaan', 'Silakan tunggu beberapa saat lalu coba lagi.'],
    500 => ['Terjadi Kesalahan', 'Terjadi gangguan pada server. Silakan coba beberapa saat lagi.'],
];
[$heading, $text] = $titles[$code] ?? $titles[500];
?>
<section class="section">
  <div class="container error-page">
    <div class="error-page__code"><?= substr((string) $code, 0, 1) ?><span><?= substr((string) $code, 1, 1) ?></span><?= substr((string) $code, 2) ?></div>
    <h1><?= e($heading) ?></h1>
    <p class="text-muted"><?= e($message ?: $text) ?></p>
    <div class="row row--center mt-6">
      <a class="btn btn--primary" href="<?= url('/') ?>"><?= icon('home', 'icon-sm') ?> Ke Beranda</a>
      <?php if (auth()): ?><a class="btn btn--outline" href="<?= url('/admin') ?>">Dashboard</a><?php endif; ?>
    </div>
  </div>
</section>
