<?= partial('page-hero', ['heading' => 'Cek Status Aspirasi', 'lead' => 'Masukkan nomor tiket yang Anda terima saat mengirim aspirasi.', 'crumbs' => [['Aspirasi', '/aspirasi'], ['Cek Status', null]]]) ?>

<section class="section">
  <div class="container lookup">
    <form class="card card__body" method="get" action="<?= url('/aspirasi/status') ?>" role="search">
      <div class="field">
        <label class="label" for="tiket">Nomor tiket</label>
        <div class="row">
          <div class="input-group grow"><?= icon('ticket') ?><input class="input mono" id="tiket" name="tiket" value="<?= e($tiket) ?>" placeholder="ASP-260930-ABC123" required maxlength="24" autocomplete="off"></div>
          <button class="btn btn--primary" type="submit"><?= icon('search', 'icon-sm') ?> Cek</button>
        </div>
      </div>
    </form>

    <?php if ($limited): ?>
      <div class="alert alert--warning mt-6"><?= icon('alert') ?><div>Terlalu banyak pencarian. Silakan coba lagi nanti.</div></div>
    <?php elseif ($searched && $result): ?>
      <div class="card mt-6">
        <div class="card__head"><h2 class="mono"><?= e($result['tiket']) ?></h2><?= status_badge($result['status']) ?></div>
        <div class="card__body">
          <dl class="dl">
            <dt>Judul</dt><dd><?= e($result['judul']) ?></dd>
            <dt>Kategori</dt><dd><?= e($result['kategori']) ?></dd>
            <dt>Dikirim</dt><dd><?= tanggal($result['created_at'], true) ?></dd>
            <dt>Pembaruan terakhir</dt><dd><?= tanggal($result['updated_at'] ?: $result['created_at'], true) ?></dd>
          </dl>
          <h3 class="mt-6">Tanggapan Pengurus</h3>
          <?php if ($result['tanggapan']): ?>
            <div class="prose"><blockquote><?= nl2br(e($result['tanggapan'])) ?></blockquote></div>
          <?php else: ?>
            <p class="text-muted">Belum ada tanggapan. Aspirasi Anda <?= $result['status'] === 'baru' ? 'sudah diterima dan menunggu ditinjau' : 'sedang diproses' ?>.</p>
          <?php endif; ?>
        </div>
      </div>
    <?php elseif ($searched): ?>
      <div class="card mt-6"><?= partial('empty', ['icon' => 'x-circle', 'title' => 'Tiket tidak ditemukan', 'text' => 'Periksa kembali nomor tiket Anda (contoh: ASP-260930-ABC123).']) ?></div>
    <?php endif; ?>
  </div>
</section>
