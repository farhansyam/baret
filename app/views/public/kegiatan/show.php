<?php use App\Models\Event; $ws = Event::timeStatus($item); ?>
<section class="page-hero">
  <div class="container">
    <ol class="breadcrumb" aria-label="Breadcrumb">
      <li><a href="<?= url('/') ?>">Beranda</a></li>
      <li><a href="<?= url('/kegiatan') ?>">Kegiatan</a></li>
    </ol>
    <?= status_badge($ws) ?>
    <h1 class="mt-2"><?= e($item['judul']) ?></h1>
  </div>
</section>

<section class="section">
  <div class="container layout-aside">
    <article>
      <?php if ($item['gambar']): ?>
        <figure class="article-cover mt-0"><img src="<?= e(upload_url('events', $item['gambar'])) ?>" alt="<?= e($item['judul']) ?>" width="1200" height="675"></figure>
      <?php endif; ?>
      <div class="prose"><?= nl2br(e($item['deskripsi'])) ?></div>

      <h2 class="section-title mt-8 mb-6" id="galeri">Dokumentasi</h2>
      <?php if ($photos): ?>
        <div class="gallery mt-6">
          <?php foreach ($photos as $p): ?>
            <figure>
              <a href="<?= e(upload_url('events', $p['file'])) ?>" data-lightbox data-caption="<?= e($p['caption'] ?? '') ?>">
                <img src="<?= e(upload_url('events', $p['file'])) ?>" alt="<?= e($p['caption'] ?: 'Dokumentasi ' . $item['judul']) ?>" loading="lazy" width="400" height="300">
              </a>
              <?php if ($p['caption']): ?><figcaption><?= e($p['caption']) ?></figcaption><?php endif; ?>
            </figure>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="card mt-6"><?= partial('empty', ['icon' => 'image', 'title' => 'Belum ada dokumentasi', 'text' => $ws === 'lampau' ? 'Foto kegiatan akan segera diunggah.' : 'Dokumentasi akan tersedia setelah kegiatan berlangsung.']) ?></div>
      <?php endif; ?>
    </article>

    <aside>
      <div class="aside-block">
        <h3>Detail Kegiatan</h3>
        <dl class="dl">
          <dt>Mulai</dt><dd><?= hari($item['mulai']) ?>, <?= tanggal($item['mulai'], true) ?></dd>
          <dt>Selesai</dt><dd><?= hari($item['selesai']) ?>, <?= tanggal($item['selesai'], true) ?></dd>
          <dt>Lokasi</dt><dd><?= e($item['lokasi']) ?></dd>
          <dt>Penyelenggara</dt><dd><?= e($item['wilayah_nama'] ?: 'Dewan Pimpinan Pusat') ?></dd>
          <?php if ($item['penanggung_jawab']): ?><dt>Penanggung jawab</dt><dd><?= e($item['penanggung_jawab']) ?></dd><?php endif; ?>
        </dl>
      </div>
      <div class="aside-block">
        <a class="btn btn--outline btn--block" href="<?= url('/kegiatan') ?>"><?= icon('arrow-left', 'icon-sm') ?> Semua kegiatan</a>
      </div>
    </aside>
  </div>
</section>
