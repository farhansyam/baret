<?php $crumbs = [['Berita', '/berita']]; if ($item['kategori']) $crumbs[] = [$item['kategori'], '/berita/kategori/' . $item['kategori_slug']]; ?>
<section class="page-hero">
  <div class="container">
    <ol class="breadcrumb" aria-label="Breadcrumb">
      <li><a href="<?= url('/') ?>">Beranda</a></li>
      <?php foreach ($crumbs as [$l, $h]): ?><li><a href="<?= url($h) ?>"><?= e($l) ?></a></li><?php endforeach; ?>
    </ol>
    <?php if ($item['kategori']): ?><a class="tag mb-4" href="<?= url('/berita/kategori/' . $item['kategori_slug']) ?>"><?= e($item['kategori']) ?></a><?php endif; ?>
    <h1 class="mt-2"><?= e($item['judul']) ?></h1>
    <div class="meta-line">
      <span><?= icon('calendar') ?><time datetime="<?= e(date('c', strtotime($item['published_at']))) ?>"><?= hari($item['published_at']) ?>, <?= tanggal($item['published_at'], true) ?></time></span>
      <?php if ($item['penulis']): ?><span><?= icon('user') ?><?= e($item['penulis']) ?></span><?php endif; ?>
      <span><?= icon('eye') ?><?= number($item['views'] + 1) ?> kali dibaca</span>
    </div>
  </div>
</section>

<section class="section">
  <div class="container layout-aside">
    <article>
      <?php if ($item['ringkasan']): ?><p class="lead"><?= e($item['ringkasan']) ?></p><?php endif; ?>
      <?php if ($item['gambar']): ?>
        <figure class="article-cover"><img src="<?= e(upload_url('news', $item['gambar'])) ?>" alt="<?= e($item['judul']) ?>" width="1200" height="675"></figure>
      <?php endif; ?>
      <div class="prose"><?= $item['isi'] /* sudah disaring HtmlSanitizer saat disimpan */ ?></div>
      <hr>
      <a class="section-link" href="<?= url('/berita') ?>"><?= icon('arrow-left', 'icon-sm') ?> Kembali ke daftar berita</a>
    </article>
    <aside>
      <?php if ($related): ?>
      <div class="aside-block">
        <h3>Berita Terkait</h3>
        <ul class="mini-list">
          <?php foreach ($related as $r): ?>
            <li><a href="<?= url('/berita/' . $r['slug']) ?>">
              <span class="thumb"><?php if ($r['gambar']): ?><img src="<?= e(upload_url('news', $r['gambar'])) ?>" alt="" loading="lazy" width="76" height="56"><?php else: ?><span class="media-fallback"><img src="<?= asset('img/logo-320.webp') ?>" alt="" width="40" height="38"></span><?php endif; ?></span>
              <span><?= e($r['judul']) ?><small><?= tanggal($r['published_at'], false, true) ?></small></span>
            </a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
      <div class="aside-block">
        <h3>Gabung BARET</h3>
        <p class="text-sm text-muted">Daftar sebagai anggota dan ikut bergerak bersama kami.</p>
        <a class="btn btn--primary btn--block" href="<?= url('/daftar') ?>">Daftar Anggota</a>
      </div>
    </aside>
  </div>
</section>
