<?= partial('page-hero', ['heading' => 'Cek Status Pendaftaran', 'lead' => 'Masukkan nomor pendaftaran dan tanggal lahir untuk melihat status verifikasi keanggotaan Anda.', 'crumbs' => [['Pendaftaran Anggota', '/daftar'], ['Cek Status', null]]]) ?>

<section class="section">
  <div class="container lookup">
    <form class="card card__body" method="get" action="<?= url('/daftar/status') ?>">
      <div class="form-grid form-grid--2">
        <div class="field">
          <label class="label" for="no">Nomor pendaftaran</label>
          <input class="input mono" id="no" name="no" value="<?= e($no) ?>" placeholder="REG-260930-A1B2C" required maxlength="24" autocomplete="off">
        </div>
        <div class="field">
          <label class="label" for="tgl_lahir">Tanggal lahir</label>
          <input class="input" type="date" id="tgl_lahir" name="tgl_lahir" value="<?= e($tgl) ?>" required>
        </div>
      </div>
      <div class="form-actions"><button class="btn btn--primary" type="submit"><?= icon('search', 'icon-sm') ?> Cek Status</button></div>
    </form>

    <?php if ($limited): ?>
      <div class="alert alert--warning mt-6"><?= icon('alert') ?><div>Terlalu banyak pencarian. Silakan coba lagi nanti.</div></div>
    <?php elseif ($searched && $result): ?>
      <div class="card mt-6">
        <div class="card__head"><h2>Status Pendaftaran</h2><?= status_badge($result['status']) ?></div>
        <div class="card__body">
          <dl class="dl">
            <dt>No. pendaftaran</dt><dd class="mono"><?= e($result['no_registrasi']) ?></dd>
            <dt>Nama</dt><dd><?= e($result['nama']) ?></dd>
            <dt>Wilayah/Cabang</dt><dd><?= e($result['wilayah_nama']) ?></dd>
            <dt>Tanggal daftar</dt><dd><?= tanggal($result['created_at']) ?></dd>
            <?php if ($result['nia']): ?><dt>NIA</dt><dd><span class="nia-chip"><?= e($result['nia']) ?></span></dd><?php endif; ?>
          </dl>
          <?php if ($result['status'] === 'menunggu'): ?>
            <div class="alert alert--info mt-4"><?= icon('clock') ?><div>Data Anda sedang diverifikasi oleh pengurus. Proses biasanya memerlukan 1–7 hari kerja.</div></div>
          <?php elseif ($result['status'] === 'aktif'): ?>
            <div class="alert alert--success mt-4"><?= icon('check-circle') ?><div>Selamat! Anda resmi menjadi anggota BARET. Gunakan NIA di atas untuk keperluan keanggotaan.</div></div>
          <?php elseif ($result['status'] === 'ditolak'): ?>
            <div class="alert alert--error mt-4"><?= icon('x-circle') ?><div>Pendaftaran belum dapat disetujui.<?= $result['catatan_verifikasi'] ? ' Catatan: ' . e($result['catatan_verifikasi']) : '' ?> Hubungi sekretariat untuk informasi lebih lanjut.</div></div>
          <?php endif; ?>
        </div>
      </div>
    <?php elseif ($searched): ?>
      <div class="card mt-6"><?= partial('empty', ['icon' => 'x-circle', 'title' => 'Data tidak ditemukan', 'text' => 'Pastikan nomor pendaftaran dan tanggal lahir sesuai dengan yang Anda isi saat mendaftar.']) ?></div>
    <?php endif; ?>
  </div>
</section>
