<a class="news-card" href="<?= url('/berita/' . $n['slug']) ?>">
  <div class="news-card__media">
    <?php if ($n['gambar']): ?>
      <img src="<?= e(upload_url('news', $n['gambar'])) ?>" alt="" loading="lazy" width="640" height="360">
    <?php else: ?>
      <div class="media-fallback"><img src="<?= asset('img/logo-320.webp') ?>" alt="" loading="lazy" width="120" height="116"></div>
    <?php endif; ?>
    <?php if (!empty($n['kategori'])): ?><span class="tag"><?= e($n['kategori']) ?></span><?php endif; ?>
  </div>
  <div class="news-card__body">
    <div class="news-card__meta"><?= icon('calendar', 'icon-sm') ?><time datetime="<?= e(date('c', strtotime($n['published_at']))) ?>"><?= tanggal($n['published_at']) ?></time></div>
    <h3 class="news-card__title"><?= e($n['judul']) ?></h3>
    <p class="news-card__excerpt"><?= e(str_limit($n['ringkasan'] ?: $n['isi'], 130)) ?></p>
    <span class="news-card__more">Baca selengkapnya <?= icon('arrow-right', 'icon-sm') ?></span>
  </div>
</a>
