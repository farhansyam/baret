<?php
$lat = (float) setting('map_lat', '-6.2');
$lng = (float) setting('map_lng', '106.816666');
$bbox = sprintf('%F,%F,%F,%F', $lng - 0.01, $lat - 0.006, $lng + 0.01, $lat + 0.006);
$wa = setting('whatsapp');
?>
<?= partial('page-hero', ['heading' => 'Kontak', 'lead' => 'Hubungi sekretariat BARET untuk informasi keanggotaan, kerja sama, dan kegiatan.', 'crumbs' => [['Kontak', null]]]) ?>

<section class="section">
  <div class="container contact-grid">
    <div class="stack">
      <div class="contact-card">
        <span class="contact-card__icon"><?= icon('map-pin') ?></span>
        <div><h3>Sekretariat</h3><p><?= e(setting('alamat')) ?></p></div>
      </div>
      <?php if ($t = setting('telepon')): ?>
      <div class="contact-card">
        <span class="contact-card__icon"><?= icon('phone') ?></span>
        <div><h3>Telepon</h3><p><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $t)) ?>"><?= e($t) ?></a></p></div>
      </div>
      <?php endif; ?>
      <?php if ($wa): ?>
      <div class="contact-card">
        <span class="contact-card__icon"><?= icon('whatsapp') ?></span>
        <div><h3>WhatsApp</h3><p><a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener">+<?= e($wa) ?></a></p></div>
      </div>
      <?php endif; ?>
      <?php if ($m = setting('email')): ?>
      <div class="contact-card">
        <span class="contact-card__icon"><?= icon('mail') ?></span>
        <div><h3>Email</h3><p><a href="mailto:<?= e($m) ?>"><?= e($m) ?></a></p></div>
      </div>
      <?php endif; ?>
      <div class="contact-card">
        <span class="contact-card__icon"><?= icon('megaphone') ?></span>
        <div><h3>Aspirasi &amp; Pengaduan</h3><p>Sampaikan secara resmi dan dapatkan nomor tiket. <a href="<?= url('/aspirasi') ?>">Kirim aspirasi →</a></p></div>
      </div>
    </div>
    <div>
      <iframe class="map-frame" title="Peta lokasi sekretariat BARET" loading="lazy" referrerpolicy="no-referrer"
        src="https://www.openstreetmap.org/export/embed.html?bbox=<?= e($bbox) ?>&amp;layer=mapnik&amp;marker=<?= e(sprintf('%F,%F', $lat, $lng)) ?>"></iframe>
      <p class="text-sm mt-2"><a href="https://www.openstreetmap.org/?mlat=<?= e((string) $lat) ?>&amp;mlon=<?= e((string) $lng) ?>#map=17/<?= e((string) $lat) ?>/<?= e((string) $lng) ?>" target="_blank" rel="noopener">Buka peta lebih besar <?= icon('external', 'icon-sm') ?></a></p>
      <div class="socials">
        <?php foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube'] as $k => $lbl): if ($u = setting($k)): ?>
          <a class="btn btn--outline btn--sm" href="<?= e($u) ?>" target="_blank" rel="noopener"><?= icon($k, 'icon-sm') ?> <?= $lbl ?></a>
        <?php endif; endforeach; ?>
      </div>
    </div>
  </div>
</section>
