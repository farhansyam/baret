<?php
$nav = [
    ['/', 'Beranda', 'home', true],
    ['/profil', 'Profil', 'flag', false],
    ['/struktur', 'Struktur', 'network', false],
    ['/berita', 'Berita', 'newspaper', false],
    ['/kegiatan', 'Kegiatan', 'calendar', false],
    ['/aspirasi', 'Aspirasi', 'megaphone', false],
    ['/kontak', 'Kontak', 'phone', false],
];
$wa = setting('whatsapp');
?>
<!doctype html>
<html lang="id" class="no-js">
<head>
<?= partial('head', get_defined_vars()) ?>
</head>
<body>
<a class="skip-link" href="#konten">Lewati ke konten utama</a>

<header class="site-header">
  <div class="container site-header__inner">
    <a class="brand" href="<?= url('/') ?>" aria-label="BARET — Beranda">
      <img src="<?= asset('img/logo-320.webp') ?>" alt="" width="52" height="52">
      <span class="brand__text">
        <span class="brand__name">BARET</span>
        <span class="brand__tag">Barisan Relawan Tangguh</span>
      </span>
    </a>
    <nav class="nav" aria-label="Menu utama">
      <ul>
        <?php foreach ($nav as [$href, $label, , $exact]): ?>
          <li><a class="<?= trim(nav_active($href, $exact)) ?>" href="<?= url($href) ?>"<?= nav_active($href, $exact) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="header-actions">
      <a class="header-login" href="<?= url(auth() ? '/admin' : '/login') ?>"><?= icon(auth() ? 'dashboard' : 'log-in', 'icon-sm') ?><?= auth() ? 'Dashboard' : 'Masuk' ?></a>
      <a class="btn btn--primary header-cta" href="<?= url('/daftar') ?>">Daftar Anggota</a>
      <button type="button" class="nav-toggle" data-drawer-open aria-controls="drawer" aria-expanded="false" aria-label="Buka menu"><?= icon('menu') ?></button>
    </div>
  </div>
</header>

<div class="drawer" id="drawer" aria-hidden="true">
  <div class="drawer__backdrop" data-drawer-close></div>
  <div class="drawer__panel" role="dialog" aria-modal="true" aria-label="Menu">
    <div class="drawer__head">
      <span class="brand"><img src="<?= asset('img/logo-320.webp') ?>" alt="" width="44" height="44"><span class="brand__name">BARET</span></span>
      <button type="button" class="nav-toggle" data-drawer-close aria-label="Tutup menu"><?= icon('x') ?></button>
    </div>
    <ul class="drawer__nav">
      <?php foreach ($nav as [$href, $label, $ic, $exact]): ?>
        <li><a class="<?= trim(nav_active($href, $exact)) ?>" href="<?= url($href) ?>"><?= icon($ic) ?><?= e($label) ?></a></li>
      <?php endforeach; ?>
    </ul>
    <div class="drawer__foot">
      <a class="btn btn--primary btn--block" href="<?= url('/daftar') ?>">Daftar Anggota</a>
      <a class="btn btn--outline-light btn--block" href="<?= url(auth() ? '/admin' : '/login') ?>"><?= auth() ? 'Dashboard' : 'Masuk Pengurus' ?></a>
    </div>
  </div>
</div>

<?= partial('flash') ?>

<main id="konten" tabindex="-1">
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="container site-footer__grid">
    <div class="footer-brand">
      <img src="<?= asset('img/logo-320.webp') ?>" alt="Logo BARET" width="96" height="96" loading="lazy">
      <p><?= e(setting('nama_organisasi', 'Barisan Relawan Tangguh')) ?> — <?= e(setting('tagline')) ?></p>
      <div class="socials">
        <?php foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube'] as $k => $lbl): if ($u = setting($k)): ?>
          <a href="<?= e($u) ?>" target="_blank" rel="noopener" aria-label="<?= $lbl ?>"><?= icon($k, 'icon-sm') ?></a>
        <?php endif; endforeach; ?>
        <?php if ($wa): ?><a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?= icon('whatsapp', 'icon-sm') ?></a><?php endif; ?>
      </div>
    </div>
    <div>
      <h3>Organisasi</h3>
      <ul>
        <li><a href="<?= url('/profil') ?>">Profil &amp; Visi Misi</a></li>
        <li><a href="<?= url('/struktur') ?>">Struktur Organisasi</a></li>
        <li><a href="<?= url('/berita') ?>">Berita</a></li>
        <li><a href="<?= url('/kegiatan') ?>">Kegiatan</a></li>
      </ul>
    </div>
    <div>
      <h3>Layanan</h3>
      <ul>
        <li><a href="<?= url('/daftar') ?>">Pendaftaran Anggota</a></li>
        <li><a href="<?= url('/daftar/status') ?>">Cek Status Pendaftaran</a></li>
        <li><a href="<?= url('/verifikasi') ?>">Verifikasi Anggota</a></li>
        <li><a href="<?= url('/aspirasi') ?>">Kirim Aspirasi</a></li>
        <li><a href="<?= url('/aspirasi/status') ?>">Cek Status Aspirasi</a></li>
      </ul>
    </div>
    <div>
      <h3>Sekretariat</h3>
      <ul class="contact-list">
        <li><?= icon('map-pin') ?><span><?= e(setting('alamat')) ?></span></li>
        <?php if ($t = setting('telepon')): ?><li><?= icon('phone') ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $t)) ?>"><?= e($t) ?></a></li><?php endif; ?>
        <?php if ($m = setting('email')): ?><li><?= icon('mail') ?><a href="mailto:<?= e($m) ?>"><?= e($m) ?></a></li><?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="container site-footer__bottom">
    <span>&copy; <?= date('Y') ?> <?= e(setting('nama_organisasi', 'Barisan Relawan Tangguh')) ?>. Hak cipta dilindungi.</span>
    <a href="<?= url('/login') ?>">Masuk Pengurus</a>
  </div>
</footer>

<?= partial('confirm-modal') ?>
</body>
</html>
