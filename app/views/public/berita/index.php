<?php
$heading = $category ? $category['nama'] : 'Berita';
$crumbs = $category ? [['Berita', '/berita'], [$category['nama'], null]] : [['Berita', null]];
$action = $category ? url('/berita/kategori/' . $category['slug']) : url('/berita');
?>
<?= partial('page-hero', ['heading' => $heading, 'lead' => 'Kabar kegiatan, pengumuman, dan informasi resmi organisasi.', 'crumbs' => $crumbs]) ?>

<section class="section">
  <div class="container layout-aside">
    <div>
      <form class="toolbar" method="get" action="<?= $action ?>" role="search">
        <div class="input-group">
          <?= icon('search') ?>
          <label class="sr-only" for="q">Cari berita</label>
          <input class="input" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Cari judul berita…" maxlength="100">
        </div>
        <button class="btn btn--dark" type="submit">Cari</button>
        <?php if ($q): ?><a class="btn btn--ghost" href="<?= $action ?>">Reset</a><?php endif; ?>
      </form>

      <?php if ($q): ?>
        <p class="text-muted mb-4">Hasil pencarian untuk <strong>“<?= e($q) ?>”</strong>: <?= number($pager->total) ?> berita.</p>
      <?php endif; ?>

      <?php if ($items): ?>
        <div class="grid-cards">
          <?php foreach ($items as $n): ?><?= partial('news-card', ['n' => $n]) ?><?php endforeach; ?>
        </div>
        <?= partial('pagination', ['pager' => $pager, 'label' => 'berita']) ?>
      <?php else: ?>
        <?= partial('empty', [
            'icon' => $q ? 'search' : 'newspaper',
            'title' => $q ? 'Berita tidak ditemukan' : 'Belum ada berita',
            'text' => $q ? 'Coba kata kunci lain atau lihat semua berita.' : 'Berita untuk kategori ini belum tersedia.',
            'action' => $q || $category ? ['Lihat semua berita', url('/berita'), 'newspaper'] : null,
            'actionClass' => 'btn--outline',
        ]) ?>
      <?php endif; ?>
    </div>

    <aside>
      <div class="aside-block">
        <h3>Kategori</h3>
        <ul class="cat-list">
          <li><a class="<?= !$category ? 'is-active' : '' ?>" href="<?= url('/berita') ?>">Semua berita</a></li>
          <?php foreach ($categories as $c): ?>
            <li><a class="<?= $category && $category['id'] == $c['id'] ? 'is-active' : '' ?>" href="<?= url('/berita/kategori/' . $c['slug']) ?>"><?= e($c['nama']) ?> <span class="count"><?= (int) $c['jumlah'] ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="aside-block">
        <h3>Punya Aspirasi?</h3>
        <p class="text-sm text-muted">Sampaikan saran, laporan, atau pengaduan kepada pengurus BARET.</p>
        <a class="btn btn--primary btn--block" href="<?= url('/aspirasi') ?>"><?= icon('megaphone', 'icon-sm') ?> Kirim Aspirasi</a>
      </div>
    </aside>
  </div>
</section>
