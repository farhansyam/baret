<?php use App\Models\Setting; ?>
<?= partial('page-hero', ['heading' => 'Profil Organisasi', 'lead' => 'Mengenal lebih dekat Barisan Relawan Tangguh: sejarah, visi, misi, dan nilai yang kami pegang.', 'crumbs' => [['Profil', null]]]) ?>

<section class="section">
  <div class="container layout-aside">
    <div>
      <span class="section-kicker">Sejarah</span>
      <h2 class="section-title mb-6">Lahir dari Lapangan</h2>
      <div class="prose mt-6"><?= nl2br(e(setting('sejarah'))) ?></div>
    </div>
    <aside>
      <div class="aside-block">
        <h3>Dokumen Organisasi</h3>
        <ul class="doc-list">
          <li>
            <?= icon('file-text') ?>
            <div class="grow"><strong>AD/ART BARET</strong><div class="text-sm text-muted">Anggaran Dasar &amp; Anggaran Rumah Tangga</div></div>
            <?php if ($f = setting('ad_art_file')): ?>
              <a class="btn btn--sm btn--outline" href="<?= e(upload_url('docs', $f)) ?>" download><?= icon('download', 'icon-sm') ?> Unduh</a>
            <?php else: ?>
              <span class="badge badge--muted">Segera</span>
            <?php endif; ?>
          </li>
        </ul>
      </div>
      <?php if ($legal = Setting::lines('legalitas')): ?>
      <div class="aside-block">
        <h3>Legalitas</h3>
        <ul class="doc-list">
          <?php foreach ($legal as $l): ?><li><?= icon('scale') ?><span><?= e($l) ?></span></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </aside>
  </div>
</section>

<section class="section section--alt">
  <div class="container vm-grid">
    <div class="vision-card">
      <h3>Visi</h3>
      <p><?= e(setting('visi')) ?></p>
    </div>
    <div>
      <h2 class="section-title mb-6">Misi</h2>
      <ol class="mission-list mt-6">
        <?php foreach (Setting::lines('misi') as $m): ?><li><?= e($m) ?></li><?php endforeach; ?>
      </ol>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head"><h2 class="section-title"><span class="section-kicker">Karakter</span>Nilai-Nilai BARET</h2></div>
    <div class="values">
      <?php foreach (Setting::pairs('nilai') as [$t, $d]): ?>
        <div class="value-card"><h3><?= e($t) ?></h3><p><?= e($d) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
