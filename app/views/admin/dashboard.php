<?php use App\Core\Auth; ?>
<?= partial('admin-head', [
    'heading' => 'Dashboard',
    'crumbs' => [['Selamat datang, ' . auth()['nama'], null]],
    'actions' => (can('members.manage') ? '<a class="btn btn--primary" href="' . url('/admin/anggota/tambah') . '">' . icon('user-plus', 'icon-sm') . ' Tambah Anggota</a>' : '')
        . (can('news.manage') ? '<a class="btn btn--outline" href="' . url('/admin/berita/tambah') . '">' . icon('pencil', 'icon-sm') . ' Tulis Berita</a>' : ''),
]) ?>

<?php if (Auth::isScoped()): ?>
  <div class="alert alert--info mb-6"><?= icon('map-pin') ?><div>Data yang ditampilkan dibatasi untuk <strong><?= e(auth()['wilayah_nama'] ?? '—') ?></strong> dan unit di bawahnya.</div></div>
<?php endif; ?>

<div class="kpis">
  <a class="kpi" href="<?= url('/admin/anggota?status=aktif') ?>">
    <span class="kpi__icon"><?= icon('user-check') ?></span>
    <span><span class="kpi__num"><?= number($stats['aktif']) ?></span><span class="kpi__label">Anggota aktif</span></span>
  </a>
  <a class="kpi" href="<?= url('/admin/anggota?status=menunggu') ?>">
    <span class="kpi__icon"><?= icon('clock') ?></span>
    <span><span class="kpi__num"><?= number($stats['menunggu']) ?></span><span class="kpi__label">Menunggu verifikasi</span></span>
  </a>
  <a class="kpi" href="<?= url('/admin/wilayah') ?>">
    <span class="kpi__icon"><?= icon('network') ?></span>
    <span><span class="kpi__num"><?= number($stats['cabang']) ?></span><span class="kpi__label">Cabang &amp; ranting</span></span>
  </a>
  <?php if (can('events.manage')): ?><a class="kpi" href="<?= url('/admin/kegiatan?waktu=akan_datang') ?>"><?php else: ?><div class="kpi"><?php endif; ?>
    <span class="kpi__icon"><?= icon('calendar') ?></span>
    <span><span class="kpi__num"><?= number($stats['kegiatan']) ?></span><span class="kpi__label">Kegiatan mendatang</span></span>
  <?= can('events.manage') ? '</a>' : '</div>' ?>
  <?php if ($stats['aspirasi'] !== null): ?>
  <a class="kpi" href="<?= url('/admin/aspirasi') ?>">
    <span class="kpi__icon"><?= icon('megaphone') ?></span>
    <span><span class="kpi__num"><?= number($stats['aspirasi']) ?></span><span class="kpi__label">Aspirasi terbuka</span></span>
  </a>
  <?php endif; ?>
</div>

<div class="dash-grid">
  <section class="card">
    <div class="card__head"><h2>Pendaftaran per Bulan</h2><span class="text-sm text-muted">12 bulan terakhir</span></div>
    <div class="card__body">
      <?= partial('bar-chart', ['data' => $chart, 'title' => 'Jumlah pendaftar anggota per bulan']) ?>
    </div>
  </section>

  <section class="card">
    <div class="card__head"><h2>Perlu Verifikasi</h2><?php if ($pending): ?><a class="text-sm" href="<?= url('/admin/anggota?status=menunggu') ?>">Semua</a><?php endif; ?></div>
    <?php if ($pending): ?>
      <ul class="list-plain">
        <?php foreach ($pending as $p): ?>
          <li>
            <span class="avatar" aria-hidden="true"><?= e(initials($p['nama'])) ?></span>
            <div class="grow">
              <a class="table__title" href="<?= url('/admin/anggota/' . $p['id']) ?>"><?= e($p['nama']) ?></a>
              <div class="table__sub"><?= e($p['wilayah_nama']) ?> · <?= time_ago($p['created_at']) ?></div>
            </div>
            <a class="btn btn--sm btn--outline" href="<?= url('/admin/anggota/' . $p['id']) ?>">Tinjau</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <?= partial('empty', ['icon' => 'check-circle', 'title' => 'Semua beres', 'text' => 'Tidak ada pendaftar yang menunggu verifikasi.']) ?>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card__head"><h2>Kegiatan Mendatang</h2><?php if (can('events.manage')): ?><a class="text-sm" href="<?= url('/admin/kegiatan') ?>">Kelola</a><?php endif; ?></div>
    <?php if ($upcoming): ?>
      <ul class="list-plain">
        <?php foreach ($upcoming as $e): $t = strtotime($e['mulai']); ?>
          <li>
            <div class="date-block" aria-hidden="true"><span class="date-block__d"><?= date('d', $t) ?></span><span class="date-block__m"><?= bulan((int) date('n', $t), true) ?></span></div>
            <div class="grow">
              <div class="table__title"><?= e($e['judul']) ?></div>
              <div class="table__sub"><?= e($e['lokasi']) ?> · <?= date('H.i', $t) ?> WIB</div>
            </div>
            <?= status_badge($e['status']) ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <?= partial('empty', ['icon' => 'calendar', 'title' => 'Belum ada agenda', 'action' => can('events.manage') ? ['Tambah kegiatan', url('/admin/kegiatan/tambah')] : null]) ?>
    <?php endif; ?>
  </section>
</div>
