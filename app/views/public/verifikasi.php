<?= partial('page-hero', ['heading' => 'Verifikasi Anggota', 'lead' => 'Pastikan keabsahan anggota BARET dengan memasukkan Nomor Induk Anggota (NIA) yang tertera di kartu anggota.', 'crumbs' => [['Verifikasi Anggota', null]]]) ?>

<section class="section">
  <div class="container lookup">
    <form class="card card__body" method="get" action="<?= url('/verifikasi') ?>" role="search">
      <div class="field">
        <label class="label" for="nia">Nomor Induk Anggota (NIA)</label>
        <div class="row">
          <div class="input-group grow"><?= icon('id-card') ?><input class="input mono" id="nia" name="nia" value="<?= e($nia) ?>" placeholder="BRT-JKT-2026-00001" required maxlength="40" autocomplete="off"></div>
          <button class="btn btn--primary" type="submit"><?= icon('search', 'icon-sm') ?> Periksa</button>
        </div>
      </div>
    </form>

    <?php if ($limited): ?>
      <div class="alert alert--warning mt-6"><?= icon('alert') ?><div>Terlalu banyak pencarian. Silakan coba lagi nanti.</div></div>
    <?php elseif ($searched && $result): ?>
      <div class="card mt-6">
        <div class="card__body">
          <div class="row mb-4">
            <span class="kpi__icon"><?= icon($result['status'] === 'aktif' ? 'shield-check' : 'shield') ?></span>
            <div>
              <h2 class="mb-0"><?= $result['status'] === 'aktif' ? 'Anggota Terverifikasi' : 'Keanggotaan Tidak Aktif' ?></h2>
              <?= status_badge($result['status']) ?>
            </div>
          </div>
          <dl class="dl">
            <dt>NIA</dt><dd class="mono"><?= e($result['nia']) ?></dd>
            <dt>Nama</dt><dd><?= e($result['nama']) ?></dd>
            <dt>Wilayah/Cabang</dt><dd><?= e($result['wilayah_nama']) ?></dd>
            <dt>Terverifikasi sejak</dt><dd><?= tanggal($result['verified_at']) ?></dd>
          </dl>
        </div>
      </div>
    <?php elseif ($searched): ?>
      <div class="card mt-6"><?= partial('empty', ['icon' => 'x-circle', 'title' => 'NIA tidak ditemukan', 'text' => 'Periksa kembali penulisan NIA (contoh: BRT-JKT-2026-00001). Bila yakin benar, hubungi sekretariat.']) ?></div>
    <?php endif; ?>
    <p class="text-sm text-muted mt-6"><?= icon('lock', 'icon-sm') ?> Demi pelindungan data pribadi, halaman ini hanya menampilkan nama, wilayah, dan status keanggotaan.</p>
  </div>
</section>
