<?php use App\Models\Event; ?>
<?= partial('page-hero', ['heading' => 'Kegiatan', 'lead' => 'Agenda dan dokumentasi kegiatan BARET di seluruh wilayah.', 'crumbs' => [['Kegiatan', null]]]) ?>

<section class="section">
  <div class="container">
    <div class="toolbar row--between">
      <nav class="seg" aria-label="Tampilan">
        <a class="<?= $view === 'daftar' ? 'is-active' : '' ?>" href="<?= url('/kegiatan') ?>"<?= $view === 'daftar' ? ' aria-current="page"' : '' ?>><?= icon('list', 'icon-sm') ?> Daftar</a>
        <a class="<?= $view === 'kalender' ? 'is-active' : '' ?>" href="<?= url('/kegiatan?tampilan=kalender') ?>"<?= $view === 'kalender' ? ' aria-current="page"' : '' ?>><?= icon('calendar', 'icon-sm') ?> Kalender</a>
      </nav>
      <?php if ($view === 'daftar'): ?>
        <nav class="seg" aria-label="Filter waktu">
          <a class="<?= !$waktu ? 'is-active' : '' ?>" href="<?= url('/kegiatan') ?>">Semua</a>
          <?php foreach (Event::WAKTU as $k => $l): ?>
            <a class="<?= $waktu === $k ? 'is-active' : '' ?>" href="<?= url('/kegiatan?waktu=' . $k) ?>"><?= e($l) ?></a>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>
    </div>

    <?php if ($view === 'kalender'):
        $first = mktime(0, 0, 0, $month, 1, $year);
        $daysIn = (int) date('t', $first);
        $lead = ((int) date('N', $first)) - 1; // Senin = kolom pertama
        $prev = date('Y-m', strtotime('-1 month', $first));
        $next = date('Y-m', strtotime('+1 month', $first));
        $byDay = [];
        foreach ($monthEvents as $ev) {
            $s = max(strtotime($ev['mulai']), $first);
            $eTs = min(strtotime($ev['selesai']), mktime(23, 59, 59, $month, $daysIn, $year));
            for ($d = (int) date('j', $s); $d <= (int) date('j', $eTs); $d++) {
                $byDay[$d][] = $ev;
            }
        }
        $today = date('Y-m') === sprintf('%04d-%02d', $year, $month) ? (int) date('j') : 0;
        $cells = (int) ceil(($lead + $daysIn) / 7) * 7;
    ?>
      <div class="calendar">
        <div class="calendar__head">
          <a class="btn btn--sm btn--icon" href="<?= url('/kegiatan?tampilan=kalender&bulan=' . $prev) ?>" aria-label="Bulan sebelumnya"><?= icon('chevron-left', 'icon-sm') ?></a>
          <h2 aria-live="polite"><?= bulan($month) ?> <?= $year ?></h2>
          <a class="btn btn--sm btn--icon" href="<?= url('/kegiatan?tampilan=kalender&bulan=' . $next) ?>" aria-label="Bulan berikutnya"><?= icon('chevron-right', 'icon-sm') ?></a>
        </div>
        <div class="calendar__grid" role="grid" aria-label="Kalender kegiatan">
          <?php foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $d): ?><div class="calendar__dow" role="columnheader"><?= $d ?></div><?php endforeach; ?>
          <?php for ($i = 0; $i < $cells; $i++): $day = $i - $lead + 1; $in = $day >= 1 && $day <= $daysIn; ?>
            <div class="calendar__cell<?= $in ? '' : ' is-out' ?><?= $day === $today ? ' is-today' : '' ?>" role="gridcell">
              <?php if ($in): ?>
                <span class="calendar__day"><?= $day ?></span>
                <?php foreach ($byDay[$day] ?? [] as $ev): ?>
                  <a class="calendar__ev" href="<?= url('/kegiatan/' . $ev['slug']) ?>" title="<?= e($ev['judul']) ?>"><?= e($ev['judul']) ?></a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          <?php endfor; ?>
        </div>
      </div>
      <?php if ($monthEvents): ?>
        <h2 class="h-sm mt-8 mb-4">Kegiatan <?= bulan($month) ?> <?= $year ?></h2>
        <div class="agenda-list">
          <?php foreach ($monthEvents as $ev): ?><?= partial('agenda-item', ['e' => $ev]) ?><?php endforeach; ?>
        </div>
      <?php else: ?>
        <?= partial('empty', ['icon' => 'calendar', 'title' => 'Tidak ada kegiatan', 'text' => 'Belum ada kegiatan terjadwal pada bulan ini.']) ?>
      <?php endif; ?>

    <?php else: ?>
      <?php if ($items): ?>
        <div class="agenda-list">
          <?php foreach ($items as $ev): ?><?= partial('agenda-item', ['e' => $ev]) ?><?php endforeach; ?>
        </div>
        <?= partial('pagination', ['pager' => $pager, 'label' => 'kegiatan']) ?>
      <?php else: ?>
        <?= partial('empty', ['icon' => 'calendar', 'title' => 'Belum ada kegiatan', 'text' => $waktu ? 'Tidak ada kegiatan dengan status ini.' : 'Agenda kegiatan akan diumumkan di sini.', 'action' => $waktu ? ['Lihat semua kegiatan', url('/kegiatan'), 'list'] : null, 'actionClass' => 'btn--outline']) ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
