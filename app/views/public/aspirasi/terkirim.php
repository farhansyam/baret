<?= partial('page-hero', ['heading' => 'Aspirasi Terkirim', 'crumbs' => [['Aspirasi', '/aspirasi'], ['Terkirim', null]]]) ?>

<section class="section">
  <div class="container">
    <div class="card card__body result-card result-card--ok">
      <div class="empty__icon"><?= icon('check-circle', 'icon-lg') ?></div>
      <h2>Aspirasi Anda Kami Terima</h2>
      <p class="text-muted">Terima kasih telah menyampaikan aspirasi. Simpan nomor tiket berikut untuk memantau statusnya:</p>
      <div class="ticket-code" aria-label="Nomor tiket"><?= e($tiket) ?></div>
      <div class="row row--center mt-4">
        <a class="btn btn--primary" href="<?= url('/aspirasi/status?tiket=' . urlencode($tiket)) ?>"><?= icon('search', 'icon-sm') ?> Cek Status</a>
        <a class="btn btn--outline" href="<?= url('/') ?>">Kembali ke Beranda</a>
      </div>
    </div>
  </div>
</section>
