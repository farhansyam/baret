<?= partial('page-hero', ['heading' => 'Pendaftaran Terkirim', 'crumbs' => [['Pendaftaran Anggota', '/daftar'], ['Terkirim', null]]]) ?>

<section class="section">
  <div class="container">
    <div class="card card__body result-card result-card--ok">
      <div class="empty__icon"><?= icon('check-circle', 'icon-lg') ?></div>
      <h2>Terima kasih, <?= e($reg['nama']) ?>!</h2>
      <p class="text-muted">Pendaftaran Anda sudah kami terima dan sedang menunggu verifikasi pengurus. Simpan nomor pendaftaran berikut untuk mengecek status:</p>
      <div class="ticket-code" aria-label="Nomor pendaftaran"><?= e($reg['no']) ?></div>
      <p class="text-sm text-muted">Cek status memerlukan nomor pendaftaran dan tanggal lahir Anda. Setelah disetujui, Anda akan mendapatkan Nomor Induk Anggota (NIA).</p>
      <div class="row row--center mt-6">
        <a class="btn btn--primary" href="<?= url('/daftar/status?no=' . urlencode($reg['no'])) ?>"><?= icon('search', 'icon-sm') ?> Cek Status</a>
        <a class="btn btn--outline" href="<?= url('/') ?>">Kembali ke Beranda</a>
      </div>
    </div>
  </div>
</section>
