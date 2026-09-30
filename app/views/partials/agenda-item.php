<?php $t = strtotime($e['mulai']); $ws = App\Models\Event::timeStatus($e); ?>
<a class="agenda-item" href="<?= url('/kegiatan/' . $e['slug']) ?>">
  <div class="date-block" aria-hidden="true">
    <span class="date-block__d"><?= date('d', $t) ?></span>
    <span class="date-block__m"><?= bulan((int) date('n', $t), true) ?></span>
    <span class="date-block__y"><?= date('Y', $t) ?></span>
  </div>
  <div class="agenda-item__body">
    <div><?= status_badge($ws) ?></div>
    <h3 class="agenda-item__title"><?= e($e['judul']) ?></h3>
    <div class="meta-line">
      <span><?= icon('clock') ?><?= hari($e['mulai']) ?>, <?= tanggal($e['mulai'], true, true) ?></span>
      <span><?= icon('map-pin') ?><?= e($e['lokasi']) ?></span>
    </div>
  </div>
</a>
