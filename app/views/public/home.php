<section class="hero">
  <div class="container hero__grid">
    <div>
      <span class="eyebrow">Organisasi Kemasyarakatan</span>
      <h1>Barisan Relawan <span class="text-red">Tangguh</span></h1>
      <p class="hero__lead"><?= e(setting('tagline', 'Solid, tangguh, dan siap mengabdi untuk masyarakat.')) ?></p>
      <div class="hero__cta">
        <a class="btn btn--primary" href="<?= url('/daftar') ?>"><?= icon('user-plus', 'icon-sm') ?> Daftar Anggota</a>
        <a class="btn btn--outline-light" href="<?= url('/profil') ?>">Kenali BARET</a>
      </div>
    </div>
    <div class="hero__logo">
      <picture>
        <source srcset="<?= asset('img/logo.webp') ?>" type="image/webp">
        <img src="<?= asset('img/logo.png') ?>" alt="Logo BARET — Barisan Relawan Tangguh" width="420" height="407" fetchpriority="high">
      </picture>
    </div>
  </div>
</section>

<div class="stats-band">
  <div class="container">
    <div class="stats" role="list">
      <div class="stat" role="listitem"><div class="stat__num"><?= number($stats['anggota']) ?></div><div class="stat__label">Anggota Terverifikasi</div></div>
      <div class="stat" role="listitem"><div class="stat__num"><?= number($stats['wilayah']) ?></div><div class="stat__label">Wilayah</div></div>
      <div class="stat" role="listitem"><div class="stat__num"><?= number($stats['cabang']) ?></div><div class="stat__label">Cabang &amp; Ranting</div></div>
      <div class="stat" role="listitem"><div class="stat__num"><?= number($stats['kegiatan']) ?></div><div class="stat__label">Kegiatan</div></div>
    </div>
  </div>
</div>

<section class="section">
  <div class="container welcome">
    <div class="welcome__figure">
      <div class="welcome__photo"><img src="<?= asset('img/logo-320.webp') ?>" alt="" width="192" height="186" loading="lazy"></div>
    </div>
    <div>
      <span class="section-kicker">Sambutan Ketua Umum</span>
      <blockquote>
        <?= icon('quote') ?>
        <p><?= nl2br(e(setting('sambutan_ketua'))) ?></p>
      </blockquote>
      <div class="welcome__sign"><?= e(setting('nama_ketua', 'Ketua Umum')) ?></div>
      <div class="welcome__role">Ketua Umum <?= e(setting('nama_organisasi', 'Barisan Relawan Tangguh')) ?></div>
    </div>
  </div>
</section>

<div class="divider-slash" aria-hidden="true"></div>

<section class="section section--alt">
  <div class="container">
    <div class="section-head">
      <h2 class="section-title"><span class="section-kicker">Kabar Terkini</span>Berita Terbaru</h2>
      <a class="section-link" href="<?= url('/berita') ?>">Semua berita <?= icon('arrow-right', 'icon-sm') ?></a>
    </div>
    <?php if ($news): ?>
      <div class="grid-cards">
        <?php foreach ($news as $n): ?><?= partial('news-card', ['n' => $n]) ?><?php endforeach; ?>
      </div>
    <?php else: ?>
      <?= partial('empty', ['icon' => 'newspaper', 'title' => 'Belum ada berita', 'text' => 'Berita dan kabar organisasi akan tampil di sini.']) ?>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2 class="section-title"><span class="section-kicker">Agenda</span>Kegiatan Mendatang</h2>
      <a class="section-link" href="<?= url('/kegiatan') ?>">Semua kegiatan <?= icon('arrow-right', 'icon-sm') ?></a>
    </div>
    <?php if ($events): ?>
      <div class="agenda-list">
        <?php foreach ($events as $ev): ?><?= partial('agenda-item', ['e' => $ev]) ?><?php endforeach; ?>
      </div>
    <?php else: ?>
      <?= partial('empty', ['icon' => 'calendar', 'title' => 'Belum ada agenda', 'text' => 'Agenda kegiatan terdekat akan diumumkan di sini.', 'action' => ['Lihat arsip kegiatan', url('/kegiatan?waktu=lampau'), 'history'], 'actionClass' => 'btn--outline']) ?>
    <?php endif; ?>
  </div>
</section>

<section class="section section--tight">
  <div class="container">
    <div class="cta-band">
      <div class="cta-band__inner">
        <div>
          <h2>Bergabung Bersama BARET</h2>
          <p>Jadilah bagian dari barisan relawan yang siap bergerak untuk masyarakat. Pendaftaran online hanya 3 langkah.</p>
        </div>
        <div class="row">
          <a class="btn btn--primary" href="<?= url('/daftar') ?>">Daftar Sekarang <?= icon('arrow-right', 'icon-sm') ?></a>
          <a class="btn btn--outline-light" href="<?= url('/aspirasi') ?>">Sampaikan Aspirasi</a>
        </div>
      </div>
    </div>
  </div>
</section>
