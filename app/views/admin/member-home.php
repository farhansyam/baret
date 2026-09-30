<?= partial('admin-head', ['heading' => 'Beranda Anggota', 'crumbs' => [['Selamat datang, ' . auth()['nama'], null]]]) ?>

<div class="dash-grid">
  <section class="card">
    <div class="card__head"><h2>Keanggotaan Saya</h2><?php if ($member): ?><?= status_badge($member['status']) ?><?php endif; ?></div>
    <div class="card__body">
      <?php if ($member): ?>
        <dl class="dl">
          <dt>NIA</dt><dd><?= $member['nia'] ? '<span class="nia-chip">' . e($member['nia']) . '</span>' : '<span class="text-muted">Belum terbit</span>' ?></dd>
          <dt>Nama</dt><dd><?= e($member['nama']) ?></dd>
          <dt>Wilayah/Cabang</dt><dd><?= e($member['wilayah_nama']) ?></dd>
          <dt>No. HP</dt><dd><?= e($member['telepon']) ?></dd>
          <dt>Email</dt><dd><?= e($member['email'] ?: '—') ?></dd>
          <dt>Terverifikasi</dt><dd><?= tanggal($member['verified_at']) ?></dd>
        </dl>
        <p class="text-sm text-muted mt-4"><?= icon('info', 'icon-sm') ?> Kartu anggota digital (e-KTA) akan tersedia pada tahap berikutnya. Untuk perubahan data, hubungi sekretariat.</p>
      <?php else: ?>
        <?= partial('empty', ['icon' => 'id-card', 'title' => 'Data anggota belum tertaut', 'text' => 'Akun Anda belum ditautkan ke data keanggotaan. Hubungi sekretariat.']) ?>
      <?php endif; ?>
    </div>
  </section>
  <section class="card">
    <div class="card__head"><h2>Agenda Terdekat</h2></div>
    <?php if ($events): ?>
      <ul class="list-plain">
        <?php foreach ($events as $e): $t = strtotime($e['mulai']); ?>
          <li>
            <div class="date-block" aria-hidden="true"><span class="date-block__d"><?= date('d', $t) ?></span><span class="date-block__m"><?= bulan((int) date('n', $t), true) ?></span></div>
            <div class="grow"><a class="table__title" href="<?= url('/kegiatan/' . $e['slug']) ?>" target="_blank"><?= e($e['judul']) ?></a><div class="table__sub"><?= e($e['lokasi']) ?></div></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <?= partial('empty', ['icon' => 'calendar', 'title' => 'Belum ada agenda']) ?>
    <?php endif; ?>
  </section>
</div>
